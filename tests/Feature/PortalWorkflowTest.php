<?php

namespace Tests\Feature;

use App\Mail\PaymentSuccessfulMail;
use App\Models\Admin;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

class PortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

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
        $this->post(route('admin.plans.store'), ['name' => 'Platinum Plan', 'price_rupees' => 1299, 'duration_years' => 5, 'coverage_sqm' => 8, 'features_text' => "Damage cover\nLabour included", 'accent' => 'black', 'is_active' => 1, 'sort_order' => 3])->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', ['slug' => 'platinum-plan', 'price' => 129900]);
        $this->put(route('admin.profile.update'), ['name' => 'Primary Admin', 'email' => 'admin@ppf.com'])->assertSessionHas('status');
        $this->assertDatabaseHas('admins', ['id' => $admin->id, 'name' => 'Primary Admin']);
    }

    public function test_verified_razorpay_payment_activates_subscription_once_and_sends_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Gold', 'slug' => 'gold', 'price' => 89900, 'duration_years' => 5, 'coverage_sqm' => 5, 'is_active' => true]);
        $payment = Payment::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'gateway_order_id' => 'order_test', 'amount' => 89900, 'currency' => 'INR']);
        $this->mock(RazorpayService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verifyPayment')->once()->andReturn(['method' => 'upi']);
        });
        $payload = ['razorpay_payment_id' => 'pay_test', 'razorpay_order_id' => 'order_test', 'razorpay_signature' => str_repeat('a', 64)];
        $this->actingAs($user)->postJson(route('payments.verify'), $payload)->assertOk()->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id, 'payment_id' => $payment->id, 'status' => 'active']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'gateway_payment_id' => 'pay_test']);
        Mail::assertSent(PaymentSuccessfulMail::class);
        $this->actingAs($user)->postJson(route('payments.verify'), $payload)->assertOk();
        $this->assertDatabaseCount('subscriptions', 1);
    }
}
