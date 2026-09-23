<?php

namespace Tests\Feature;

use App\Mail\PaymentSuccessfulMail;
use App\Mail\WarrantyCodeMail;
use App\Models\Admin;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WarrantyCode;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

class PortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_is_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Premium PPF')->assertSee('Get protected');
    }

    public function test_overview_and_warranty_are_separate_customer_pages(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $plan = Plan::query()->where('slug', 'free')->firstOrFail();
        $this->actingAs($user)->post(route('subscription.free', $plan));

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('GOOD TO SEE YOU')->assertDontSee('WARRANTY #PF-');
        $this->actingAs($user)->get(route('warranty.show'))->assertOk()->assertSee('WARRANTY #PF-')->assertSee('Customer navigation')->assertDontSee('customer-sidebar');
        $this->actingAs($user)->get(route('vehicles.index'))->assertOk()->assertSee('Your protected vehicles');
    }

    public function test_new_customer_is_routed_to_plans_and_can_skip(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => null]);
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('subscription.index'));
        $this->actingAs($user)->get(route('subscription.index'))->assertOk()->assertSee('Choose the cover');
        $this->actingAs($user)->post(route('subscription.skip'))->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }

    public function test_customer_can_update_profile(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Updated Customer', 'email' => 'updated@example.com', 'phone' => '9876543210'])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Customer', 'phone' => '9876543210']);
    }

    public function test_admin_can_login_manage_plans_and_update_profile(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@ppf.com', 'password' => 'admin123']);
        $this->post(route('admin.login.store'), ['email' => 'admin@ppf.com', 'password' => 'admin123'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.vehicles.index'))->assertOk()->assertSee('Vehicle catalog')->assertSee('Warranty Codes')->assertSee('Subscription Plans');
        $this->get(route('admin.warranty-codes.index'))->assertOk()->assertSee('Warranty codes');
        $this->post(route('admin.plans.store'), ['name' => 'Platinum Plan', 'price_aud' => 1299, 'duration_years' => 5, 'coverage_sqm' => 8, 'features_text' => "Damage cover\nLabour included", 'accent' => 'black', 'is_active' => 1, 'sort_order' => 3])->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', ['slug' => 'platinum-plan', 'price' => 129900, 'currency' => 'AUD']);
        $this->put(route('admin.profile.update'), ['name' => 'Primary Admin', 'email' => 'admin@ppf.com'])->assertSessionHas('status');
        $this->assertDatabaseHas('admins', ['id' => $admin->id, 'name' => 'Primary Admin']);
    }

    public function test_verified_razorpay_payment_activates_subscription_once_and_sends_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Gold', 'slug' => 'gold', 'price' => 89900, 'duration_years' => 5, 'coverage_sqm' => 5, 'is_active' => true]);
        $payment = Payment::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'gateway_order_id' => 'order_test', 'amount' => 89900, 'currency' => 'AUD']);
        $this->mock(RazorpayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verifyPayment')->once()->andReturn(['method' => 'upi']);
        });
        $payload = ['razorpay_payment_id' => 'pay_test', 'razorpay_order_id' => 'order_test', 'razorpay_signature' => str_repeat('a', 64)];
        $this->actingAs($user)->postJson(route('payments.verify'), $payload)->assertOk()->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id, 'payment_id' => $payment->id, 'status' => 'active']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'gateway_payment_id' => 'pay_test']);
        $issuedCode = WarrantyCode::where('subscription_id', Subscription::firstOrFail()->id)->firstOrFail();
        Mail::assertSent(PaymentSuccessfulMail::class, fn ($mail) => $mail->warrantyCode->id === $issuedCode->id && str_contains($mail->render(), $issuedCode->code));
        Mail::assertNotSent(WarrantyCodeMail::class);
        $this->actingAs($user)->postJson(route('payments.verify'), $payload)->assertOk();
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_paid_checkout_order_uses_aud(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Gold', 'slug' => 'gold', 'price' => 89900, 'currency' => 'AUD', 'duration_years' => 5, 'coverage_sqm' => 5, 'is_active' => true]);
        $this->mock(RazorpayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'order_aud_test']);
        });

        $this->actingAs($user)->postJson(route('payments.order'), ['plan_id' => $plan->id])
            ->assertOk()->assertJsonPath('currency', 'AUD');
        $this->assertDatabaseHas('payments', ['gateway_order_id' => 'order_aud_test', 'currency' => 'AUD']);
    }

    public function test_customer_can_activate_the_free_plan_without_payment_for_fifteen_days_only_once(): void
    {
        Mail::fake();
        $user = User::factory()->create(['onboarding_completed_at' => null]);
        $plan = Plan::query()->where('slug', 'free')->firstOrFail();

        $this->actingAs($user)->post(route('subscription.free', $plan))
            ->assertRedirect(route('subscription.success'));

        $subscription = $user->subscriptions()->firstOrFail();
        $user = $user->fresh();
        $issuedCode = WarrantyCode::where('subscription_id', $subscription->id)->firstOrFail();
        Mail::assertSent(PaymentSuccessfulMail::class, fn ($mail) => $mail->warrantyCode->id === $issuedCode->id && str_contains($mail->render(), $issuedCode->code));
        Mail::assertNotSent(WarrantyCodeMail::class);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Get warranty code by email');
        $this->actingAs($user)->post(route('warranty-code.email'))->assertSessionHas('status');
        Mail::assertSent(WarrantyCodeMail::class, fn ($mail) => $mail->warrantyCode->id === $issuedCode->id && str_contains($mail->render(), $issuedCode->code));
        $this->assertDatabaseCount('warranty_codes', 100);
        $this->assertNotNull($subscription->payment_id);
        $this->assertEquals(15, $subscription->starts_at->diffInDays($subscription->ends_at));
        $this->assertNotNull($user->fresh()->onboarding_completed_at);
        $this->assertDatabaseHas('payments', [
            'id' => $subscription->payment_id,
            'gateway' => 'free',
            'gateway_payment_id' => null,
            'amount' => 0,
            'status' => 'paid',
        ]);

        $this->actingAs($user)->get(route('subscription.success'))
            ->assertOk()
            ->assertSee('SUBSCRIPTION SUMMARY')
            ->assertSee('No payment required');

        $this->actingAs($user)->post(route('subscription.free', $plan));
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('payments', 1);
        Mail::assertSent(WarrantyCodeMail::class, 1);
        $other = User::factory()->create(['onboarding_completed_at' => now()]);
        Subscription::create(['user_id' => $other->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(15)]);
        $this->actingAs($other)->postJson(route('claims.warranty-code.check'), ['warranty_code' => $issuedCode->code])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'invalid']);

        $user = $user->fresh();
        $this->travel(16)->days();
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('subscription.index'));
        $this->actingAs($user)->get(route('profile.edit'))->assertRedirect(route('subscription.index'));
        $this->actingAs($user)->get(route('claims.index'))->assertRedirect(route('subscription.index'));
        $this->actingAs($user)->get(route('documents.index'))->assertRedirect(route('subscription.index'));
        $this->actingAs($user)->get(route('subscription.index'))
            ->assertOk()
            ->assertSee('subscription ended on')
            ->assertSee('Renew your protection to continue.')
            ->assertSee('Free trial already used')
            ->assertSee('Sign out');
        $this->travelBack();
    }

    public function test_admin_can_create_update_list_and_delete_customers(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@ppf.com', 'password' => 'admin123']);

        $this->actingAs($admin, 'admin')->post(route('admin.customers.store'), [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'onboarding_completed' => 1,
        ])->assertRedirect(route('admin.customers.index'));

        $customer = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $this->actingAs($admin, 'admin')->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Test Customer');

        $this->actingAs($admin, 'admin')->put(route('admin.customers.update', $customer), [
            'name' => 'Updated Customer',
            'email' => 'customer@example.com',
            'phone' => '9999999999',
            'onboarding_completed' => 1,
        ])->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'name' => 'Updated Customer']);

        $this->actingAs($admin, 'admin')->delete(route('admin.customers.destroy', $customer))
            ->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }
}
