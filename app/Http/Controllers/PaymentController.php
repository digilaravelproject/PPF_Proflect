<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Services\PaymentFulfillmentService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class PaymentController extends Controller
{
    public function order(Request $request, StripeService $stripe): JsonResponse
    {
        $validated = $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id']]);
        $plan = Plan::query()->whereKey($validated['plan_id'])->where('is_active', true)->where('price', '>', 0)->firstOrFail();
        try {
            $session = $stripe->createCheckoutSession($plan, $request->user());
            Payment::create([
                'user_id' => $request->user()->id, 'plan_id' => $plan->id, 'gateway' => 'stripe', 'gateway_order_id' => $session['id'],
                'amount' => $plan->price, 'currency' => $plan->currency, 'status' => 'created',
            ]);

            return response()->json(['checkout_url' => $session['url']]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function complete(Request $request, StripeService $stripe, PaymentFulfillmentService $fulfillment): RedirectResponse
    {
        $attributes = $request->validate(['session_id' => ['required', 'string', 'max:255']]);
        $payment = Payment::query()->where('gateway', 'stripe')->where('user_id', $request->user()->id)
            ->where('gateway_order_id', $attributes['session_id'])->firstOrFail();
        if ($payment->status === 'paid' && $payment->subscription()->exists()) {
            return redirect()->route('subscription.success');
        }

        try {
            $gatewayPayment = $stripe->verifyCheckoutSession($attributes['session_id'], $payment);
            $fulfillment->fulfill($payment, $gatewayPayment);
        } catch (Throwable $exception) {
            report($exception);
            $payment->update(['status' => 'verification_failed']);

            return redirect()->route('subscription.checkout', $payment->plan_id)
                ->withErrors(['payment' => 'Payment verification failed. No subscription was activated.']);
        }

        return redirect()->route('subscription.success');
    }

    public function webhook(Request $request, StripeService $stripe, PaymentFulfillmentService $fulfillment): Response
    {
        try {
            $session = $stripe->checkoutSessionFromWebhook($request->getContent(), (string) $request->header('Stripe-Signature'));
            if (! $session) {
                return response('Webhook ignored');
            }
            $payment = Payment::query()->where('gateway', 'stripe')->where('gateway_order_id', $session->id)->firstOrFail();
            $gatewayPayment = $stripe->verifyCheckoutSession($session->id, $payment);
            $fulfillment->fulfill($payment, $gatewayPayment);

            return response('Webhook handled');
        } catch (Throwable $exception) {
            report($exception);

            return response('Webhook rejected', 400);
        }
    }

    public function success(Request $request)
    {
        $subscription = $request->user()->subscriptions()->with(['plan', 'payment'])->latest()->firstOrFail();

        return view('subscription.success', compact('subscription'));
    }
}
