<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public const TYPES = ['all', 'customers', 'plans', 'subscriptions', 'claims', 'payments'];

    public function index(): View
    {
        return view('admin.reports.index');
    }

    public function export(Request $request): Response
    {
        $filters = $request->validate([
            'type' => ['required', Rule::in(self::TYPES)],
            'format' => ['required', Rule::in(['pdf', 'excel'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $sections = $this->sections($filters['type'], $filters['from'] ?? null, $filters['to'] ?? null);
        $filename = 'proflect-'.$filters['type'].'-report-'.now()->format('Y-m-d');

        if ($filters['format'] === 'pdf') {
            return Pdf::loadView('admin.reports.complete', compact('sections', 'filters'))->setPaper('a4', 'landscape')->download($filename.'.pdf');
        }

        return response()->view('admin.reports.complete', compact('sections', 'filters'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'.xls"');
    }

    private function sections(string $type, ?string $from, ?string $to): array
    {
        $include = fn (string $name): bool => $type === 'all' || $type === $name;
        $sections = [];
        if ($include('customers')) {
            $rows = $this->dated(User::query()->withCount('claims'), $from, $to)->get()->map(fn (User $user) => [$user->name, $user->email, $user->phone ?: '—', $user->claims_count, $user->created_at->format('Y-m-d')]);
            $sections[] = ['title' => 'Customers', 'headers' => ['Name', 'Email', 'Phone', 'Claims', 'Joined'], 'rows' => $rows];
        }
        if ($include('plans')) {
            $rows = $this->dated(Plan::query(), $from, $to)->get()->map(fn (Plan $plan) => [$plan->name, $plan->formatted_price, $plan->currency, $plan->duration_label, $plan->coverage_sqm.' m²', $plan->is_active ? 'Active' : 'Inactive']);
            $sections[] = ['title' => 'Plans', 'headers' => ['Plan', 'Price', 'Currency', 'Term', 'Coverage', 'Status'], 'rows' => $rows];
        }
        if ($include('subscriptions')) {
            $rows = $this->dated(Subscription::query()->with(['user', 'plan']), $from, $to)->get()->map(fn (Subscription $subscription) => ['PF-'.str_pad($subscription->id, 6, '0', STR_PAD_LEFT), $subscription->user->name, $subscription->plan->name, $subscription->starts_at->format('Y-m-d'), $subscription->ends_at->format('Y-m-d'), ucfirst($subscription->status)]);
            $sections[] = ['title' => 'Subscriptions', 'headers' => ['Warranty', 'Customer', 'Plan', 'Start', 'End', 'Status'], 'rows' => $rows];
        }
        if ($include('claims')) {
            $rows = $this->dated(Claim::query()->with(['user', 'subscription.plan', 'warrantyCode']), $from, $to)->get()->map(fn (Claim $claim) => [$claim->claim_number, $claim->warrantyCode?->code ?: '—', $claim->user->name, trim($claim->vehicle_make.' '.$claim->vehicle_model), $claim->registration_number, $claim->subscription->plan->name, count($claim->panels), ucfirst($claim->status), $claim->created_at->format('Y-m-d')]);
            $sections[] = ['title' => 'Claims', 'headers' => ['Claim', 'Warranty Code', 'Customer', 'Vehicle', 'Registration', 'Plan', 'Panels', 'Status', 'Submitted'], 'rows' => $rows];
        }
        if ($include('payments')) {
            $rows = $this->dated(Payment::query()->with(['user', 'plan']), $from, $to)->get()->map(fn (Payment $payment) => [$payment->gateway_payment_id ?: $payment->gateway_order_id, $payment->user->name, $payment->plan->name, $payment->amount ? '$'.number_format($payment->amount / 100, 2) : 'Free', $payment->currency, ucfirst($payment->gateway), ucfirst(str_replace('_', ' ', $payment->status)), ($payment->paid_at ?? $payment->created_at)->format('Y-m-d')]);
            $sections[] = ['title' => 'Payments', 'headers' => ['Reference', 'Customer', 'Plan', 'Amount', 'Currency', 'Gateway', 'Status', 'Date'], 'rows' => $rows];
        }

        return $sections;
    }

    private function dated(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query->when($from, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('created_at', '<=', $to))->latest();
    }
}
