<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = $request->user()->subscriptions()->with(['plan', 'payment'])->latest()->get();

        return view('documents.index', compact('subscriptions'));
    }

    public function certificate(Request $request, Subscription $subscription): Response
    {
        abort_unless($subscription->user_id === $request->user()->id, 404);

        return Pdf::loadView('documents.certificate', ['subscription' => $subscription->load(['plan', 'user'])])
            ->download('proflect-certificate-PF-'.str_pad($subscription->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    public function invoice(Request $request, Payment $payment): Response
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->status === 'paid', 404);

        return Pdf::loadView('documents.invoice', ['payment' => $payment->load(['plan', 'user'])])
            ->download('proflect-tax-invoice-'.str_pad($payment->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }
}
