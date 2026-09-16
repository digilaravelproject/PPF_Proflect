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
        return view('admin.claims.show', ['claim' => $claim->load(['user', 'subscription.plan', 'warrantyCode']), 'panels' => Claim::PANELS]);
    }

    public function update(Request $request, Claim $claim, CustomerNotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'disapproved'])],
            'admin_notes' => ['nullable', 'required_if:status,disapproved', 'string', 'max:2000'],
            'booking_date' => ['nullable', 'required_if:status,approved', 'date', 'after_or_equal:today'],
        ]);
        $claim->update(array_merge($validated, ['booking_date' => $validated['status'] === 'approved' ? $validated['booking_date'] : null, 'reviewed_at' => now()]));
        $notifications->claimDecision($claim->fresh('user'));

        return back()->with('status', 'Claim marked as '.$validated['status'].'.');
    }

    public function destroy(Claim $claim): RedirectResponse
    {
        Storage::disk('public')->delete($claim->photos ?? []);
        $claim->delete();

        return redirect()->route('admin.claims.index')->with('status', 'Claim deleted.');
    }

    public function photo(Claim $claim, int $index): StreamedResponse
    {
        abort_unless(isset($claim->photos[$index]), 404);

        return Storage::disk('public')->response($claim->photos[$index]);
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
