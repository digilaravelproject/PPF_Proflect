<?php

namespace App\Http\Controllers;

use App\Mail\PaymentSuccessfulMail;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\CustomerNotificationService;
use App\Services\RazorpayService;
use App\Services\WarrantyCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentController extends Controller
{
    public function order(Request $request, RazorpayService $razorpay): JsonResponse
    {
        $validated = $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id']]);
        $plan = Plan::query()->whereKey($validated['plan_id'])->where('is_active', true)->where('price', '>', 0)->firstOrFail();
        try {
            $order = $razorpay->createOrder($plan, $request->user());
            Payment::create([
                'user_id' => $request->user()->id, 'plan_id' => $plan->id, 'gateway_order_id' => $order['id'],
                'amount' => $plan->price, 'currency' => $plan->currency, 'status' => 'created',
            ]);

            return response()->json(['order_id' => $order['id'], 'amount' => $plan->price, 'currency' => $plan->currency, 'key' => config('services.razorpay.key_id')]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function verify(Request $request, RazorpayService $razorpay, CustomerNotificationService $notifications, WarrantyCodeService $codes): JsonResponse
    {
        $attributes = $request->validate([
            'razorpay_payment_id' => ['required', 'string'], 'razorpay_order_id' => ['required', 'string'], 'razorpay_signature' => ['required', 'string'],
        ]);
        $payment = Payment::with('plan')->where('user_id', $request->user()->id)->where('gateway_order_id', $attributes['razorpay_order_id'])->firstOrFail();
        if ($payment->status === 'paid') {
            return response()->json(['redirect' => route('subscription.success')]);
        }
        try {
            $gatewayPayment = $razorpay->verifyPayment($attributes, $payment->gateway_order_id, $payment->amount, $payment->currency);
            DB::transaction(function () use ($payment, $attributes, $gatewayPayment, $request): void {
                $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'paid') {
                    return;
                }
                $locked->update(['gateway_payment_id' => $attributes['razorpay_payment_id'], 'status' => 'paid', 'paid_at' => now(), 'metadata' => ['method' => $gatewayPayment['method'] ?? null]]);
                Subscription::create(['user_id' => $request->user()->id, 'plan_id' => $locked->plan_id, 'payment_id' => $locked->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => $payment->plan->subscriptionEndsAt()]);
                $request->user()->update(['onboarding_completed_at' => now()]);
            });
        } catch (Throwable $exception) {
            report($exception);
            $payment->update(['status' => 'verification_failed']);

            return response()->json(['message' => 'Payment verification failed. No subscription was activated.'], 422);
        }

        $paidPayment = $payment->fresh(['plan', 'user']);
        $subscription = Subscription::query()->where('payment_id', $payment->id)->firstOrFail();
        $code = null;
        try {
            $code = $codes->issueForSubscription($subscription);
        } catch (Throwable $exception) {
            report($exception);
        }
        if ($notifications->paymentSuccessful($paidPayment, $subscription)) {
            if ($code) {
                try {
                    Mail::to($request->user())->send(new PaymentSuccessfulMail($paidPayment, $code));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }

        return response()->json(['redirect' => route('subscription.success')]);
    }

    public function success(Request $request)
    {
        $subscription = $request->user()->subscriptions()->with(['plan', 'payment'])->latest()->firstOrFail();

        return view('subscription.success', compact('subscription'));
    }
}
