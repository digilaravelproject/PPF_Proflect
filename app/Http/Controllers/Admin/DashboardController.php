<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', ['customers' => User::count(), 'plans' => Plan::count(), 'subscriptions' => Subscription::where('status', 'active')->count(), 'revenue' => Payment::where('status', 'paid')->sum('amount')]);
    }
}
