<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    private function client(): StripeClient
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            throw new RuntimeException('Stripe is not configured. Add the Stripe secret key to the environment.');
        }

        return new StripeClient($secret);
    }

    public function createCheckoutSession(Plan $plan, User $user): array
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($plan->currency),
                    'unit_amount' => $plan->price,
                    'product_data' => [
                        'name' => $plan->name,
                        'description' => 'Proflect PPF Replacement Program · '.$plan->duration_label,
                    ],
                ],
            ]],
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'plan_id' => (string) $plan->id,
                ],
            ],
            'success_url' => route('payments.complete').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('subscription.checkout', $plan).'?cancelled=1',
        ]);

        return $session->toArray();
    }

    public function verifyCheckoutSession(string $sessionId, Payment $payment): array
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent.payment_method'],
        ]);

        return $this->validateSession($session, $payment);
    }

    public function checkoutSessionFromWebhook(string $payload, string $signature): ?Session
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret) {
            throw new RuntimeException('Stripe webhook signing secret is not configured.');
        }

        $event = Webhook::constructEvent($payload, $signature, $secret);
        if ($event->type !== 'checkout.session.completed' && $event->type !== 'checkout.session.async_payment_succeeded') {
            return null;
        }

        return $event->data->object;
    }

    private function validateSession(Session $session, Payment $payment): array
    {
        $metadata = $session->metadata?->toArray() ?? [];
        $paymentIntent = $session->payment_intent;
        $paymentIntentId = is_string($paymentIntent) ? $paymentIntent : $paymentIntent?->id;

        if ($session->id !== $payment->gateway_order_id
            || $session->client_reference_id !== (string) $payment->user_id
            || ($metadata['user_id'] ?? null) !== (string) $payment->user_id
            || ($metadata['plan_id'] ?? null) !== (string) $payment->plan_id
            || (int) $session->amount_total !== $payment->amount
            || strtoupper((string) $session->currency) !== strtoupper($payment->currency)
            || $session->payment_status !== 'paid'
            || ! $paymentIntentId) {
            throw new RuntimeException('Stripe could not confirm the payment details.');
        }

        $paymentMethod = is_object($paymentIntent) ? $paymentIntent->payment_method : null;
        $method = is_object($paymentMethod) ? ($paymentMethod->type ?? null) : null;

        return ['payment_intent_id' => $paymentIntentId, 'method' => $method];
    }
}
