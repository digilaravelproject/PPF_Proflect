<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        return view('subscription.index', ['plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function checkout(Plan $plan): View
    {
        abort_unless($plan->is_active, 404);

        return view('subscription.checkout', compact('plan'));
    }

    public function skip(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return redirect()->route('dashboard')->with('status', 'You can choose a protection plan whenever you are ready.');
    }
}
