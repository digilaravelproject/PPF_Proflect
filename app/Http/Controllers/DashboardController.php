<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'subscription' => auth()->user()->subscriptions()->with(['plan', 'payment'])->latest()->first(),
        ]);
    }
}
