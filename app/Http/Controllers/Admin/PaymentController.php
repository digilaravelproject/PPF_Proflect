<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.payments.index', ['payments' => $this->query($request)->paginate(15)->withQueryString()]);
    }

    public function show(Payment $payment): View
    {
        return view('admin.payments.show', ['payment' => $payment->load(['user', 'plan'])]);
    }

    public function report(Request $request): Response
    {
        $payments = $this->query($request)->get();

        return Pdf::loadView('admin.reports.payments', compact('payments'))->setPaper('a4', 'landscape')
            ->download('payments-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function query(Request $request): Builder
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['created', 'paid', 'verification_failed'])],
            'gateway' => ['nullable', Rule::in(['stripe', 'razorpay', 'free'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return Payment::query()->with(['user', 'plan'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(fn (Builder $query) => $query->where('gateway_order_id', 'like', "%{$search}%")
                    ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('gateway'), fn (Builder $query) => $query->where('gateway', $request->string('gateway')))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest();
    }
}
