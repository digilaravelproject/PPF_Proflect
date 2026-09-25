<?php

namespace App\Http\Controllers;

use App\Services\WarrantyCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function overview(WarrantyCodeService $codes): View
    {
        $subscription = $this->activeSubscription();
        $warrantyCode = $subscription ? $codes->issueForSubscription($subscription) : null;

        return view('dashboard', [
            'subscription' => $subscription,
            'warrantyCode' => $warrantyCode,
        ]);
    }

    public function emailWarrantyCode(Request $request, WarrantyCodeService $codes): RedirectResponse
    {
        $subscription = $this->activeSubscription();
        abort_unless($subscription && $subscription->user_id === $request->user()->id, 404);

        try {
            $codes->emailForSubscription($subscription->load(['user', 'plan']));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['warranty_code' => 'Your code could not be emailed. Please try again or contact contact@proflect.com.au.']);
        }

        return back()->with('status', 'Your available warranty code has been sent to your email.');
    }

    public function warranty(WarrantyCodeService $codes): View
    {
        $subscription = $this->activeSubscription();
        $warrantyCode = $subscription ? $codes->issueForSubscription($subscription) : null;

        return view('warranty', [
            'subscription' => $subscription,
            'warrantyCode' => $warrantyCode,
        ]);
    }

    public function vehicles(): View
    {
        $vehicles = auth()->user()->claims()->latest()->get()
            ->unique(fn ($claim) => strtoupper($claim->registration_number));

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'subscription' => $this->activeSubscription(),
        ]);
    }

    private function activeSubscription()
    {
        return auth()->user()->subscriptions()->with(['plan', 'payment'])
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
    }
}
