<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WarrantyCode;
use App\Models\WarrantyCodeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_upgrade_window_and_activation_date_is_listed(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Password1']);
        $code = WarrantyCode::create([
            'code' => '12345', 'is_active' => true, 'validity_months' => 12,
            'activated_at' => '2026-08-21 10:00:00', 'expires_at' => '2027-08-21 10:00:00',
        ]);

        $this->actingAs($admin, 'admin')->put(route('admin.masters.upgrade-window.update'), [
            'upgrade_window_months' => 2,
        ])->assertRedirect();

        $this->assertSame(2, WarrantyCodeSetting::findOrFail(1)->upgrade_window_months);
        $this->actingAs($admin, 'admin')->get(route('admin.warranty-codes.index'))
            ->assertOk()->assertSee('Activation date')->assertSee('21 Aug 2026');
    }

    public function test_expired_warranty_card_cannot_create_an_upgrade_payment(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $current = $this->plan('Current', 'current', 50000);
        $upgrade = $this->plan('Upgrade', 'upgrade', 90000);
        $payment = Payment::create([
            'user_id' => $user->id, 'plan_id' => $current->id, 'gateway' => 'stripe',
            'gateway_order_id' => 'current-payment', 'amount' => $current->price,
            'currency' => 'AUD', 'status' => 'paid', 'paid_at' => now(),
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id, 'plan_id' => $current->id, 'payment_id' => $payment->id,
            'status' => 'active', 'starts_at' => now()->subYear(), 'ends_at' => now()->addYear(),
        ]);
        WarrantyCodeSetting::query()->updateOrCreate(['id' => 1], ['validity_months' => 12, 'upgrade_window_months' => 2]);
        $code = WarrantyCode::create([
            'code' => '54321', 'subscription_id' => $subscription->id, 'used_by_user_id' => $user->id,
            'used_at' => now()->subMonths(4), 'is_active' => true, 'validity_months' => 12,
            'activated_at' => now()->subMonths(3), 'expires_at' => now()->addMonths(9),
        ]);

        $this->actingAs($user)->postJson(route('payments.order'), [
            'plan_id' => $upgrade->id, 'warranty_code_id' => $code->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('warranty_code_id')
            ->assertJsonPath('errors.warranty_code_id.0', fn (string $message) => str_contains($message, 'expired on'));
    }

    public function test_subscription_page_disables_the_current_plan_and_shows_video_and_cards(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $plan = $this->plan('Silver Plan', 'silver', 54900);
        $payment = Payment::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'gateway' => 'stripe', 'gateway_order_id' => 'paid', 'amount' => $plan->price, 'currency' => 'AUD']);
        Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'payment_id' => $payment->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear()]);
        WarrantyCode::create(['code' => '10101', 'used_by_user_id' => $user->id, 'used_at' => now(), 'is_active' => true, 'validity_months' => 12, 'activated_at' => now(), 'expires_at' => now()->addYear()]);

        $this->actingAs($user)->get(route('subscription.index'))->assertOk()
            ->assertSee('Protection you can see.')->assertSee('Current plan')->assertSee('proflect-video-poster.jpg');
    }

    private function plan(string $name, string $slug, int $price): Plan
    {
        return Plan::create([
            'name' => $name, 'slug' => $slug, 'description' => 'Protection', 'price' => $price,
            'currency' => 'AUD', 'duration_years' => 2, 'coverage_sqm' => 5,
            'features' => ['Damage cover'], 'accent' => 'gold', 'is_active' => true, 'sort_order' => 1,
        ]);
    }
}
