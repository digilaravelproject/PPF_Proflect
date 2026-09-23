<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $months = collect(range(5, 0))->map(function (int $monthsAgo): array {
            $month = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'claims' => Claim::whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count(),
            ];
        });

        $claimStatuses = collect(['pending', 'approved', 'disapproved'])
            ->mapWithKeys(fn (string $status) => [$status => Claim::where('status', $status)->count()]);
        $claimTotal = max(1, $claimStatuses->sum());

        return view('admin.dashboard', [
            'customers' => User::count(),
            'newCustomers' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'plans' => Plan::count(),
            'subscriptions' => Subscription::where('status', 'active')->where('ends_at', '>', now())->count(),
            'expiringSubscriptions' => Subscription::where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(30)])->count(),
            'revenueByCurrency' => Payment::where('status', 'paid')->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total', 'currency'),
            'pendingClaims' => $claimStatuses['pending'],
            'claimStatuses' => $claimStatuses,
            'months' => $months,
            'maxMonthlyClaims' => max(1, $months->max('claims')),
            'approvedDegrees' => round(($claimStatuses['approved'] / $claimTotal) * 360),
            'pendingDegrees' => round(($claimStatuses['pending'] / $claimTotal) * 360),
            'recentClaims' => Claim::with('user')->latest()->limit(5)->get(),
            'recentPayments' => Payment::with(['user', 'plan'])->latest()->limit(5)->get(),
        ]);
    }
}
