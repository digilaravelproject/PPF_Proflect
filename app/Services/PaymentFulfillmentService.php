<?php

namespace App\Services;

use App\Mail\PaymentSuccessfulMail;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentFulfillmentService
{
    public function __construct(
        private readonly CustomerNotificationService $notifications,
        private readonly WarrantyCodeService $codes,
    ) {}

    public function fulfill(Payment $payment, array $gatewayPayment): Subscription
    {
        $subscription = DB::transaction(function () use ($payment, $gatewayPayment): Subscription {
            $locked = Payment::query()->with('plan')->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $existing = Subscription::query()->where('payment_id', $locked->id)->first();

            if ($existing) {
                return $existing;
            }

            $locked->update([
                'gateway_payment_id' => $gatewayPayment['payment_intent_id'],
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => ['method' => $gatewayPayment['method']],
            ]);

            $subscription = Subscription::create([
                'user_id' => $locked->user_id,
                'plan_id' => $locked->plan_id,
                'payment_id' => $locked->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $locked->plan->subscriptionEndsAt(),
            ]);
            $locked->user()->update(['onboarding_completed_at' => now()]);

            return $subscription;
        });

        $paidPayment = $payment->fresh(['plan', 'user']);
        $code = null;
        try {
            $code = $this->codes->issueForSubscription($subscription);
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($this->notifications->paymentSuccessful($paidPayment, $subscription) && $code) {
            try {
                Mail::to($paidPayment->user)->send(new PaymentSuccessfulMail($paidPayment, $code));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $subscription;
    }
}
