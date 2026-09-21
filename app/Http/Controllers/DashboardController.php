<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function overview(): View
    {
        return view('dashboard', ['subscription' => $this->activeSubscription()]);
    }

    public function warranty(): View
    {
        return view('warranty', ['subscription' => $this->activeSubscription()]);
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
