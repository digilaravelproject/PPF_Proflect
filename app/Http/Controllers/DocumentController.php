<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = $request->user()->subscriptions()->with('plan')->latest()->get();

        return view('documents.index', compact('subscriptions'));
    }

    public function certificate(Request $request, Subscription $subscription): Response
    {
        abort_unless($subscription->user_id === $request->user()->id, 404);

        return Pdf::loadView('documents.certificate', ['subscription' => $subscription->load(['plan', 'user'])])
            ->download('proflect-certificate-PF-'.str_pad($subscription->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }
}
