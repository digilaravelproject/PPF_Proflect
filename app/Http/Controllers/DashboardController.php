<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'subscription' => auth()->user()->subscriptions()->with(['plan', 'payment'])
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->latest()
                ->first(),
        ]);
    }
}
