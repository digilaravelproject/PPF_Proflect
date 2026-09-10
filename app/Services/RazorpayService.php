<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use RuntimeException;

class RazorpayService
{
    private function api(): Api
    {
        $key = config('services.razorpay.key_id');
        $secret = config('services.razorpay.key_secret');
        if (! $key || ! $secret) {
            throw new RuntimeException('Razorpay is not configured. Add test or live API keys to the environment.');
        }

        return new Api($key, $secret);
    }

    public function createOrder(Plan $plan, User $user): array
    {
        $order = $this->api()->order->create([
            'receipt' => 'pf_'.$user->id.'_'.Str::random(12),
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'notes' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id],
        ]);

        return $order->toArray();
    }

    public function verifyPayment(array $attributes, string $storedOrderId, int $amount, string $currency): array
    {
        if (! hash_equals($storedOrderId, $attributes['razorpay_order_id'])) {
            throw new RuntimeException('The payment order does not match.');
        }
        $api = $this->api();
        $api->utility->verifyPaymentSignature($attributes);
        $payment = $api->payment->fetch($attributes['razorpay_payment_id']);
        if ($payment['order_id'] !== $storedOrderId || (int) $payment['amount'] !== $amount || $payment['currency'] !== $currency || ! in_array($payment['status'], ['authorized', 'captured'], true)) {
            throw new RuntimeException('Razorpay could not confirm the payment details.');
        }

        return $payment->toArray();
    }
}
