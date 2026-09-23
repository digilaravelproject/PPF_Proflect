<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Services\CustomerNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.claims.index', ['claims' => $this->query($request)->paginate(15)->withQueryString()]);
    }

    public function show(Claim $claim): View
    {
        $claim->load(['user', 'subscription.plan', 'warrantyCode', 'vehicleModel.panels']);
        $snapshot = $claim->vehicle_model_photo_path && Storage::disk('public')->exists($claim->vehicle_model_photo_path);
        $currentPhoto = $claim->vehicleModel?->photo_path && Storage::disk('public')->exists($claim->vehicleModel->photo_path);
        $photoUrl = $snapshot ? route('admin.claims.model-photo', $claim)
            : ($currentPhoto ? route('catalog.models.photo', $claim->vehicleModel) : null);
        $selectedAreas = collect($claim->panels)->map(function ($key) use ($claim) {
            $detail = collect($claim->panel_details ?? [])->firstWhere('key', $key);
            $polygon = $detail['photo_polygon'] ?? (! $claim->vehicle_model_photo_path
                ? $claim->vehicleModel?->panels->firstWhere('key', $key)?->photo_polygon : null);
            return is_array($polygon) && count($polygon) === 4 ? ['name' => $detail['name'] ?? $key, 'points' => $polygon] : null;
        })->filter()->values();

        return view('admin.claims.show', ['claim' => $claim, 'panels' => Claim::PANELS,
            'vehiclePhotoUrl' => $photoUrl, 'selectedAreas' => $selectedAreas]);
    }

    public function update(Request $request, Claim $claim, CustomerNotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'disapproved'])],
            'admin_notes' => ['nullable', 'required_if:status,disapproved', 'string', 'max:2000'],
            'booking_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $bookingDate = $validated['booking_date'] ?? ($claim->available_date?->isToday() || $claim->available_date?->isFuture() ? $claim->available_date->toDateString() : null);
        if ($validated['status'] === 'approved' && ! $bookingDate) {
            throw ValidationException::withMessages(['booking_date' => 'Choose a booking date or ask the customer for a new available date.']);
        }
        $claim->update(array_merge($validated, ['booking_date' => $validated['status'] === 'approved' ? $bookingDate : null, 'reviewed_at' => now()]));
        $notifications->claimDecision($claim->fresh('user'));

        return back()->with('status', 'Claim marked as '.$validated['status'].'.');
    }

    public function destroy(Claim $claim): RedirectResponse
    {
        Storage::disk('public')->delete(array_filter(array_merge($claim->photos ?? [], [$claim->vehicle_model_photo_path])));
        $claim->delete();

        return redirect()->route('admin.claims.index')->with('status', 'Claim deleted.');
    }

    public function photo(Claim $claim, int $index): StreamedResponse
    {
        abort_unless(isset($claim->photos[$index]), 404);

        return Storage::disk('public')->response($claim->photos[$index]);
    }

    public function modelPhoto(Claim $claim): StreamedResponse
    {
        abort_unless($claim->vehicle_model_photo_path && Storage::disk('public')->exists($claim->vehicle_model_photo_path), 404);

        return Storage::disk('public')->response($claim->vehicle_model_photo_path);
    }

    public function report(Request $request): Response
    {
        $claims = $this->query($request)->get();

        return Pdf::loadView('admin.reports.claims', compact('claims'))->setPaper('a4', 'landscape')
            ->download('claims-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function query(Request $request): Builder
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'disapproved'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return Claim::query()->with(['user', 'subscription.plan', 'warrantyCode'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(fn (Builder $query) => $query->where('claim_number', 'like', "%{$search}%")
                    ->orWhereHas('warrantyCode', fn (Builder $query) => $query->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest();
    }
}
