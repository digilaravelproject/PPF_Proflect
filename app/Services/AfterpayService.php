<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AfterpayService
{
    private function client(): PendingRequest
    {
        $merchantId = config('services.afterpay.merchant_id');
        $secret = config('services.afterpay.secret');

        if (! $merchantId || ! $secret) {
            throw new RuntimeException('Afterpay is not configured. Add the merchant credentials to the environment.');
        }

        return Http::baseUrl(rtrim((string) config('services.afterpay.base_url'), '/'))
            ->withBasicAuth($merchantId, $secret)
            ->withHeaders(['User-Agent' => 'Proflect/1.0'])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(75);
    }

    public function createCheckout(Plan $plan, User $user, string $merchantReference): array
    {
        $nameParts = preg_split('/\s+/', trim($user->name), 2) ?: [];
        $consumer = array_filter([
            'email' => $user->email,
            'givenNames' => $nameParts[0] ?? null,
            'surname' => $nameParts[1] ?? null,
            'phoneNumber' => $user->phone,
        ], fn ($value) => $value !== null && $value !== '');

        $money = $this->money($plan->price, $plan->currency);
        $response = $this->client()->post('/v2/checkouts', [
            'amount' => $money,
            'consumer' => $consumer,
            'merchantReference' => $merchantReference,
            'merchant' => [
                'redirectConfirmUrl' => route('payments.afterpay.complete'),
                'redirectCancelUrl' => route('payments.afterpay.cancel'),
                'name' => (string) config('app.name'),
            ],
            'items' => [[
                'name' => $plan->name,
                'sku' => 'plan-'.$plan->id,
                'quantity' => 1,
                'price' => $money,
            ]],
        ]);

        $data = $this->successfulJson($response, 'create the checkout');
        if (! is_string($data['token'] ?? null) || ! is_string($data['redirectCheckoutUrl'] ?? null)) {
            throw new RuntimeException('Afterpay returned an incomplete checkout response.');
        }

        return $data;
    }

    public function capture(Payment $payment): array
    {
        $merchantReference = $payment->metadata['merchant_reference'] ?? null;
        if (! is_string($merchantReference) || $merchantReference === '') {
            throw new RuntimeException('The Afterpay merchant reference is missing.');
        }

        $response = $this->client()->post('/v2/payments/capture', [
            'token' => $payment->gateway_order_id,
            'merchantReference' => $merchantReference,
        ]);
        $data = $this->successfulJson($response, 'capture the payment');

        $amount = $data['originalAmount'] ?? [];
        $consumerEmail = data_get($data, 'orderDetails.consumer.email');
        if (($data['token'] ?? null) !== $payment->gateway_order_id
            || ($data['merchantReference'] ?? null) !== $merchantReference
            || ($data['status'] ?? null) !== 'APPROVED'
            || ($data['paymentState'] ?? null) !== 'CAPTURED'
            || ! is_string($data['id'] ?? null)
            || $this->cents((string) ($amount['amount'] ?? '')) !== $payment->amount
            || strtoupper((string) ($amount['currency'] ?? '')) !== strtoupper($payment->currency)
            || ($consumerEmail && strcasecmp($consumerEmail, $payment->user->email) !== 0)) {
            throw new RuntimeException('Afterpay could not confirm the payment details.');
        }

        return ['gateway_payment_id' => $data['id'], 'method' => 'afterpay'];
    }

    private function successfulJson(Response $response, string $operation): array
    {
        if (! $response->successful()) {
            $message = $response->json('message') ?: $response->json('errorCode') ?: 'unexpected API response';
            throw new RuntimeException("Afterpay could not {$operation}: {$message}.");
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Afterpay returned an invalid response.');
        }

        return $data;
    }

    private function money(int $cents, string $currency): array
    {
        return ['amount' => number_format($cents / 100, 2, '.', ''), 'currency' => strtoupper($currency)];
    }

    private function cents(string $amount): ?int
    {
        if (! preg_match('/^\d+(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            return null;
        }

        return ((int) strstr($amount.'.', '.', true) * 100) + (int) str_pad($matches[1] ?? '', 2, '0');
    }
}
