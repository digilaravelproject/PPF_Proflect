<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WarrantyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyCardSubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_added_card_redirects_to_its_subscription_flow_and_stays_available_until_claimed(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $code = WarrantyCode::create([
            'code' => '45678', 'is_active' => true, 'validity_months' => 12,
            'activated_at' => now(), 'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($user)->post(route('claims.warranty.store'), ['warranty_code' => $code->code])
            ->assertRedirect(route('subscription.index', ['warranty_code_id' => $code->id]));

        $this->assertSame('available', $code->fresh()->status);
        $this->get(route('subscription.index', ['warranty_code_id' => $code->id]))
            ->assertOk()->assertSee('SUBSCRIPTION FOR WARRANTY CARD')->assertSee('45678');
    }

    public function test_same_plan_on_a_second_card_reports_the_existing_card_number(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $plan = Plan::create(['name' => 'Silver Plan', 'slug' => 'silver-card-test', 'price' => 54900, 'currency' => 'AUD', 'duration_years' => 2, 'coverage_sqm' => 5, 'is_active' => true]);
        $subscription = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear()]);
        WarrantyCode::create(['code' => '11111', 'subscription_id' => $subscription->id, 'used_by_user_id' => $user->id, 'used_at' => now(), 'is_active' => true, 'validity_months' => 12, 'activated_at' => now(), 'expires_at' => now()->addYear()]);
        $second = WarrantyCode::create(['code' => '22222', 'used_by_user_id' => $user->id, 'used_at' => now(), 'is_active' => true, 'validity_months' => 12, 'activated_at' => now(), 'expires_at' => now()->addYear()]);

        $this->actingAs($user)->get(route('subscription.checkout', ['plan' => $plan, 'warranty_code_id' => $second->id]))
            ->assertRedirect(route('subscription.index', ['warranty_code_id' => $second->id]))
            ->assertSessionHasErrors(['plan' => 'You already have Silver Plan on warranty card 11111.']);
    }

    public function test_claim_page_lists_all_available_subscribed_cards_and_dashboard_has_card_actions(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $plan = Plan::create(['name' => 'Gold', 'slug' => 'gold-card-test', 'price' => 10000, 'currency' => 'AUD', 'duration_years' => 1, 'coverage_sqm' => 5, 'is_active' => true]);
        foreach (['31001', '31002', '31003'] as $value) {
            $subscription = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear()]);
            WarrantyCode::create(['code' => $value, 'subscription_id' => $subscription->id, 'used_by_user_id' => $user->id, 'used_at' => now(), 'is_active' => true, 'validity_months' => 12, 'activated_at' => now(), 'expires_at' => now()->addYear()]);
        }

        $this->actingAs($user)->get(route('claims.create'))->assertOk()
            ->assertSee('31001')->assertSee('31002')->assertSee('31003')->assertSee('Which card is this claim for?');
        $this->get(route('dashboard'))->assertOk()->assertSee('Upgrade')->assertSee('Make a claim')->assertDontSee('>Used<', false);
    }
}
