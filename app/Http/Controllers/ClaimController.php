<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Subscription;
use App\Models\WarrantyCode;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Services\CustomerNotificationService;
use App\Services\WarrantyCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimController extends Controller
{
    public function checkWarrantyCode(Request $request): JsonResponse
    {
        $code = strtoupper(trim((string) $request->input('warranty_code')));
        if (! preg_match('/^CLM-[0-9]{6}-[A-Z0-9]{6}$/', $code)) {
            return response()->json(['valid' => false, 'status' => 'invalid', 'message' => 'Enter a valid warranty code in the format CLM-260901-ZM9JVS.']);
        }

        $warrantyCode = WarrantyCode::withTrashed()->where('code', $code)->first();
        if (! $warrantyCode || $warrantyCode->trashed()) {
            return response()->json(['valid' => false, 'status' => $warrantyCode?->trashed() ? 'deleted' : 'invalid', 'message' => 'This warranty code is invalid or has been deleted.']);
        }
        if ($warrantyCode->used_at) {
            return response()->json(['valid' => false, 'status' => 'used', 'message' => 'This warranty code has already been used for a claim.']);
        }
        if (! $warrantyCode->is_active) {
            return response()->json(['valid' => false, 'status' => 'inactive', 'message' => 'This warranty code is inactive. Please contact Proflect support.']);
        }
        if ($warrantyCode->subscription_id && $warrantyCode->subscription_id !== $this->activeSubscription($request)->id) {
            return response()->json(['valid' => false, 'status' => 'invalid', 'message' => 'This warranty code belongs to another subscription.']);
        }

        return response()->json(['valid' => true, 'status' => 'available', 'message' => 'Warranty code is valid and available for this claim.']);
    }

    public function index(Request $request): View
    {
        $claims = $request->user()->claims()->with(['subscription.plan', 'warrantyCode'])->latest()->paginate(10);

        return view('claims.index', compact('claims'));
    }

    public function create(Request $request): View
    {
        $subscription = $this->activeSubscription($request);

        $vehicleMakes = VehicleMake::where('is_active', true)->whereHas('models', fn ($query) => $query->where('is_active', true))->orderBy('name')->get(['id', 'name']);

        return view('claims.create', ['subscription' => $subscription, 'vehicleMakes' => $vehicleMakes]);
    }

    public function store(Request $request, WarrantyCodeService $codes, CustomerNotificationService $notifications): RedirectResponse
    {
        $subscription = $this->activeSubscription($request);
        $request->merge(['warranty_code' => strtoupper(trim((string) $request->input('warranty_code')))]);
        $catalogRequired = VehicleMake::where('is_active', true)->whereHas('models', fn ($query) => $query->where('is_active', true))->exists();
        $validated = $request->validate([
            'warranty_code' => ['required', 'string', 'max:30', 'regex:/^CLM-[0-9]{6}-[A-Z0-9]{6}$/'],
            'vehicle_make_id' => [$catalogRequired ? 'required' : 'nullable', 'integer', Rule::exists('vehicle_makes', 'id')->where('is_active', true)],
            'vehicle_model_id' => [$catalogRequired ? 'required' : 'nullable', 'integer', Rule::exists('vehicle_models', 'id')->where('is_active', true)],
            'vehicle_make' => [$catalogRequired ? 'nullable' : 'required', 'string', 'max:100'],
            'vehicle_model' => [$catalogRequired ? 'nullable' : 'required', 'string', 'max:100'],
            'registration_number' => ['required', 'string', 'max:30'],
            'vehicle_year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'panels' => ['required', 'array', 'min:1'],
            'panels.*' => ['required', 'string'],
            'photos' => ['required', 'array', 'min:1', 'max:6', function ($attribute, $files, $fail) {
                if (is_array($files) && array_sum(array_map(fn ($file) => $file instanceof \Illuminate\Http\UploadedFile ? $file->getSize() : 0, $files)) > 35 * 1024 * 1024) {
                    $fail('The combined photos and videos must be 35 MB or smaller.');
                }
            }],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:30720', function ($attribute, $file, $fail) {
                if (! $file instanceof \Illuminate\Http\UploadedFile) return;
                if (str_starts_with($file->getMimeType(), 'image/') && $file->getSize() > 5 * 1024 * 1024) $fail('Each photo must be 5 MB or smaller.');
            }],
            'available_date' => ['nullable', 'date', 'after_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = null;
        if ($catalogRequired) {
            $model = VehicleModel::with(['make', 'panels'])->where('id', $validated['vehicle_model_id'])
                ->where('vehicle_make_id', $validated['vehicle_make_id'])->where('is_active', true)
                ->whereHas('make', fn ($query) => $query->where('is_active', true))->first();
            if (! $model) throw ValidationException::withMessages(['vehicle_model_id' => 'Select a model belonging to the selected vehicle make.']);
            $allowed = $model->panels->pluck('key')->all();
            if (array_diff($validated['panels'], $allowed)) throw ValidationException::withMessages(['panels' => 'Select panels available for this vehicle model.']);
        } elseif (array_diff($validated['panels'], array_keys(Claim::PANELS))) {
            throw ValidationException::withMessages(['panels' => 'Select valid vehicle panels.']);
        }
        $panelDetails = $model
            ? $model->panels->whereIn('key', $validated['panels'])->map(fn ($panel) => ['key' => $panel->key, 'name' => $panel->name, 'photo_polygon' => $panel->photo_polygon])->values()->all()
            : collect($validated['panels'])->map(fn ($key) => ['key' => $key, 'name' => Claim::PANELS[$key]])->all();

        $paths = [];
        $modelPhotoPath = null;
        try {
            if ($model?->photo_path && Storage::disk('public')->exists($model->photo_path)) {
                $modelPhotoPath = 'claims/model-photos/'.Str::uuid().'.'.pathinfo($model->photo_path, PATHINFO_EXTENSION);
                if (! Storage::disk('public')->copy($model->photo_path, $modelPhotoPath)) {
                    throw new \RuntimeException('The vehicle model photo could not be saved with the claim.');
                }
            }
            foreach ($request->file('photos') as $photo) {
                $paths[] = $photo->store('claims/'.$request->user()->id, 'public');
            }

            $claim = DB::transaction(function () use ($validated, $request, $subscription, $paths, $codes, $model, $modelPhotoPath, $panelDetails): Claim {
                $warrantyCode = WarrantyCode::withTrashed()->where('code', $validated['warranty_code'])->lockForUpdate()->first();
                if (! $warrantyCode || $warrantyCode->trashed()) {
                    throw ValidationException::withMessages(['warranty_code' => 'This warranty code is invalid or has been deleted.']);
                }
                if (! $warrantyCode->is_active) {
                    throw ValidationException::withMessages(['warranty_code' => 'This warranty code is inactive. Please contact Proflect support.']);
                }
                if ($warrantyCode->used_at) {
                    throw ValidationException::withMessages(['warranty_code' => 'This warranty code has already been used for a claim.']);
                }
                if ($warrantyCode->subscription_id && $warrantyCode->subscription_id !== $subscription->id) {
                    throw ValidationException::withMessages(['warranty_code' => 'This warranty code belongs to another subscription.']);
                }

                $claim = Claim::create([
                    'claim_number' => $this->claimNumber(), 'user_id' => $request->user()->id,
                    'subscription_id' => $subscription->id, 'warranty_code_id' => $warrantyCode->id,
                    'vehicle_make' => $model?->make->name ?? $validated['vehicle_make'], 'vehicle_model' => $model?->name ?? $validated['vehicle_model'],
                    'vehicle_model_id' => $model?->id,
                    'model_coverage_sqm' => $model?->coverage_sqm,
                    'vehicle_model_photo_path' => $modelPhotoPath,
                    'registration_number' => strtoupper($validated['registration_number']),
                    'vehicle_year' => $validated['vehicle_year'] ?? null,
                    'panels' => array_values(array_unique($validated['panels'])), 'panel_details' => $panelDetails, 'photos' => $paths,
                    'description' => $validated['description'] ?? null, 'available_date' => $validated['available_date'] ?? null, 'status' => 'pending',
                ]);
                $warrantyCode->update(['used_by_user_id' => $request->user()->id, 'used_at' => now()]);

                return $claim;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($paths);
            if ($modelPhotoPath) Storage::disk('public')->delete($modelPhotoPath);
            throw $exception;
        }

        $notifications->claimReceived($claim->load('user'));
        try {
            $codes->issueForSubscription($subscription);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('claims.show', $claim)->with('status', 'Your claim was submitted successfully.');
    }

    public function show(Request $request, Claim $claim): View
    {
        abort_unless($claim->user_id === $request->user()->id, 404);

        return view('claims.show', ['claim' => $claim->load(['subscription.plan', 'warrantyCode']), 'panels' => Claim::PANELS]);
    }

    public function photo(Request $request, Claim $claim, int $index): StreamedResponse
    {
        abort_unless($claim->user_id === $request->user()->id && isset($claim->photos[$index]), 404);

        return Storage::disk('public')->response($claim->photos[$index]);
    }

    private function activeSubscription(Request $request): Subscription
    {
        return $request->user()->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->firstOrFail();
    }

    private function claimNumber(): string
    {
        do {
            $number = 'CLM-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Claim::where('claim_number', $number)->exists());

        return $number;
    }
}
