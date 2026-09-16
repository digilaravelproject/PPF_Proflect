<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Subscription;
use App\Models\WarrantyCode;
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

        return view('claims.create', ['subscription' => $subscription, 'panels' => Claim::PANELS]);
    }

    public function store(Request $request, WarrantyCodeService $codes, CustomerNotificationService $notifications): RedirectResponse
    {
        $subscription = $this->activeSubscription($request);
        $request->merge(['warranty_code' => strtoupper(trim((string) $request->input('warranty_code')))]);
        $validated = $request->validate([
            'warranty_code' => ['required', 'string', 'max:30', 'regex:/^CLM-[0-9]{6}-[A-Z0-9]{6}$/'],
            'vehicle_make' => ['required', 'string', 'max:100'],
            'vehicle_model' => ['required', 'string', 'max:100'],
            'registration_number' => ['required', 'string', 'max:30'],
            'vehicle_year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'panels' => ['required', 'array', 'min:1'],
            'panels.*' => ['required', Rule::in(array_keys(Claim::PANELS))],
            'photos' => ['required', 'array', 'min:1', 'max:6'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $paths = [];
        try {
            foreach ($request->file('photos') as $photo) {
                $paths[] = $photo->store('claims/'.$request->user()->id, 'public');
            }

            $claim = DB::transaction(function () use ($validated, $request, $subscription, $paths, $codes): Claim {
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

                $claim = Claim::create([
                    'claim_number' => $this->claimNumber(), 'user_id' => $request->user()->id,
                    'subscription_id' => $subscription->id, 'warranty_code_id' => $warrantyCode->id,
                    'vehicle_make' => $validated['vehicle_make'], 'vehicle_model' => $validated['vehicle_model'],
                    'registration_number' => strtoupper($validated['registration_number']),
                    'vehicle_year' => $validated['vehicle_year'] ?? null,
                    'panels' => array_values(array_unique($validated['panels'])), 'photos' => $paths,
                    'description' => $validated['description'] ?? null, 'status' => 'pending',
                ]);
                $warrantyCode->update(['used_by_user_id' => $request->user()->id, 'used_at' => now()]);
                $codes->generate();

                return $claim;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }

        $notifications->claimReceived($claim->load('user'));

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
