<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\AfterpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class AfterpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.afterpay.merchant_id' => 'merchant-test',
            'services.afterpay.secret' => 'secret-test',
            'services.afterpay.base_url' => 'https://global-api-sandbox.afterpay.com',
        ]);
    }

    public function test_afterpay_checkout_sends_the_expected_order_data(): void
    {
        Http::fake([
            'global-api-sandbox.afterpay.com/v2/checkouts' => Http::response([
                'token' => '002.checkout-token',
                'expires' => '2026-10-07T00:00:00Z',
                'redirectCheckoutUrl' => 'https://portal.sandbox.afterpay.com/checkout?token=002.checkout-token',
            ], 201),
        ]);
        $user = User::factory()->create(['name' => 'Jane Customer', 'email' => 'jane@example.com', 'phone' => '0400000000']);
        $plan = $this->paidPlan();

        $checkout = app(AfterpayService::class)->createCheckout($plan, $user, 'proflect-reference');

        $this->assertSame('002.checkout-token', $checkout['token']);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://global-api-sandbox.afterpay.com/v2/checkouts'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('merchant-test:secret-test'))
                && $request['amount'] === ['amount' => '899.00', 'currency' => 'AUD']
                && $request['consumer']['email'] === 'jane@example.com'
                && $request['consumer']['givenNames'] === 'Jane'
                && $request['consumer']['surname'] === 'Customer'
                && $request['merchantReference'] === 'proflect-reference'
                && $request['items'][0]['price'] === ['amount' => '899.00', 'currency' => 'AUD']
                && $request['merchant']['redirectConfirmUrl'] === route('payments.afterpay.complete')
                && $request['merchant']['redirectCancelUrl'] === route('payments.afterpay.cancel');
        });
    }

    public function test_customer_can_create_and_complete_an_afterpay_payment_once(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $this->mock(AfterpayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckout')->once()->andReturn([
                'token' => '002.order-token',
                'expires' => '2026-10-07T00:00:00Z',
                'redirectCheckoutUrl' => 'https://portal.afterpay.com/checkout?token=002.order-token',
            ]);
            $mock->shouldReceive('capture')->once()->andReturn([
                'gateway_payment_id' => '300000016189',
                'method' => 'afterpay',
            ]);
        });

        $this->actingAs($user)->postJson(route('payments.afterpay.order'), ['plan_id' => $plan->id])
            ->assertOk()
            ->assertJsonPath('checkout_url', 'https://portal.afterpay.com/checkout?token=002.order-token');
        $payment = Payment::query()->where('gateway_order_id', '002.order-token')->firstOrFail();
        $this->assertSame('afterpay', $payment->gateway);
        $this->assertNotEmpty($payment->metadata['merchant_reference']);

        $callback = ['orderToken' => '002.order-token', 'status' => 'SUCCESS'];
        $this->actingAs($user)->get(route('payments.afterpay.complete', $callback))
            ->assertRedirect(route('subscription.success'));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'gateway' => 'afterpay',
            'gateway_payment_id' => '300000016189',
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('subscriptions', ['payment_id' => $payment->id, 'status' => 'active']);

        $this->actingAs($user)->get(route('payments.afterpay.complete', $callback))
            ->assertRedirect(route('subscription.success'));
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_afterpay_capture_rejects_mismatched_payment_details(): void
    {
        Http::fake([
            'global-api-sandbox.afterpay.com/v2/payments/capture' => Http::response([
                'id' => '300000016189',
                'token' => '002.order-token',
                'status' => 'APPROVED',
                'paymentState' => 'CAPTURED',
                'merchantReference' => 'proflect-reference',
                'originalAmount' => ['amount' => '1.00', 'currency' => 'AUD'],
                'orderDetails' => ['consumer' => ['email' => 'customer@example.com']],
            ], 201),
        ]);
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $plan = $this->paidPlan();
        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'afterpay',
            'gateway_order_id' => '002.order-token',
            'amount' => $plan->price,
            'currency' => 'AUD',
            'status' => 'created',
            'metadata' => ['merchant_reference' => 'proflect-reference'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not confirm');
        app(AfterpayService::class)->capture($payment);
    }

    public function test_cancelled_afterpay_checkout_does_not_activate_a_subscription(): void
    {
        $user = User::factory()->create();
        $plan = $this->paidPlan();
        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'afterpay',
            'gateway_order_id' => '002.cancel-token',
            'amount' => $plan->price,
            'currency' => 'AUD',
            'status' => 'created',
            'metadata' => ['merchant_reference' => 'proflect-reference'],
        ]);

        $this->actingAs($user)->get(route('payments.afterpay.cancel', [
            'orderToken' => '002.cancel-token',
            'status' => 'CANCELLED',
        ]))->assertRedirect(route('subscription.checkout', ['plan' => $plan->id, 'cancelled' => 1]));

        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    private function paidPlan(): Plan
    {
        return Plan::create([
            'name' => 'Gold',
            'slug' => 'gold-'.uniqid(),
            'price' => 89900,
            'currency' => 'AUD',
            'duration_years' => 5,
            'coverage_sqm' => 5,
            'is_active' => true,
        ]);
    }
}
