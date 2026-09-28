<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Claim;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WarrantyCode;
use App\Notifications\CustomerEventNotification;
use App\Services\CustomerNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarrantyCodeAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registers_with_an_active_five_digit_code_and_claim_needs_no_code(): void
    {
        Notification::fake();
        Storage::fake('public');
        $code = WarrantyCode::create(['code' => '30383', 'validity_months' => 3, 'is_active' => true, 'activated_at' => now(), 'expires_at' => now()->addMonths(3)]);

        $this->post(route('register'), [
            'name' => 'Registered Customer', 'email' => 'registered@example.com', 'phone' => '9876543210',
            'warranty_code' => '30383', 'password' => 'Protection123', 'password_confirmation' => 'Protection123', 'terms' => '1',
        ])->assertRedirect(route('subscription.index'));
        $user = User::where('email', 'registered@example.com')->firstOrFail();
        $this->assertSame('used', $code->fresh()->status);
        $this->assertSame($user->id, $code->fresh()->used_by_user_id);

        $plan = Plan::create(['name' => 'Warranty Test', 'slug' => 'registration-claim', 'price' => 10000, 'duration_years' => 2, 'coverage_sqm' => 5, 'is_active' => true]);
        $subscription = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear()]);
        $user->update(['onboarding_completed_at' => now()]);

        $this->actingAs($user)->post(route('claims.store'), [
            'vehicle_make' => 'Toyota', 'vehicle_model' => 'Fortuner', 'registration_number' => 'MH12AB1234',
            'panels' => ['roof'], 'photos' => [UploadedFile::fake()->image('damage.jpg')],
        ])->assertRedirect();

        $claim = Claim::firstOrFail();
        $this->assertNull($claim->warranty_code_id);
        $this->assertSame($subscription->id, $claim->subscription_id);
        $this->assertSame(['roof'], $claim->panels);
        Notification::assertSentTo($user, CustomerEventNotification::class, fn ($notification) => $notification->event === 'claim_received');
    }

    public function test_admin_generates_a_batch_or_manually_adds_an_inactive_code_with_expiration(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'codes@example.com', 'password' => 'password123']);
        $this->actingAs($admin, 'admin')->post(route('admin.warranty-codes.store'), ['count' => 3, 'validity_months' => 7])->assertSessionHas('status');
        $generated = WarrantyCode::query()->get();
        $this->assertCount(3, $generated);
        $this->assertCount(3, $generated->pluck('code')->unique());
        $this->assertTrue($generated->every(fn (WarrantyCode $code) => preg_match('/^[0-9]{5}$/', $code->code) && ! $code->is_active && $code->validity_months === 7));

        $this->actingAs($admin, 'admin')->post(route('admin.warranty-codes.store-manual'), ['manual_code' => '22005', 'manual_validity_months' => 6])->assertSessionHas('status');
        $code = WarrantyCode::where('code', '22005')->firstOrFail();
        $this->assertFalse($code->is_active);
        $this->assertNull($code->expires_at);
        $this->assertSame('inactive', $code->status);

        $this->actingAs($admin, 'admin')->patch(route('admin.warranty-codes.toggle', $code->id))->assertSessionHas('status');
        $code->refresh();
        $this->assertTrue($code->is_active);
        $this->assertSame('available', $code->status);
        $this->assertTrue($code->expires_at->isSameDay(now()->addMonths(6)));
        $this->actingAs($admin, 'admin')->get(route('admin.warranty-codes.index', ['status' => 'available']))->assertOk()->assertSee('22005')->assertSee('Expires');

        $this->actingAs($admin, 'admin')->post(route('admin.warranty-codes.store-manual'), ['manual_code' => 'ABC12', 'manual_validity_months' => 13])->assertSessionHasErrors(['manual_code', 'manual_validity_months']);
        $this->actingAs($admin, 'admin')->delete(route('admin.warranty-codes.destroy', $code->id))->assertRedirect(route('admin.warranty-codes.index'));
        $this->assertSoftDeleted('warranty_codes', ['id' => $code->id]);
        $this->assertSame(4, WarrantyCode::withTrashed()->count());
    }

    public function test_registration_code_check_reports_every_availability_state(): void
    {
        $available = WarrantyCode::create(['code' => '11111', 'validity_months' => 1, 'is_active' => true, 'activated_at' => now(), 'expires_at' => now()->addMonth()]);
        $used = WarrantyCode::create(['code' => '22222', 'validity_months' => 1, 'is_active' => true, 'activated_at' => now(), 'expires_at' => now()->addMonth(), 'used_at' => now(), 'used_by_user_id' => User::factory()->create()->id]);
        $inactive = WarrantyCode::create(['code' => '33333', 'validity_months' => 1, 'is_active' => false]);
        $expired = WarrantyCode::create(['code' => '44444', 'validity_months' => 1, 'is_active' => true, 'activated_at' => now()->subMonths(2), 'expires_at' => now()->subMonth()]);

        $this->postJson(route('register.warranty-code.check'), ['warranty_code' => $available->code])
            ->assertOk()->assertJson(['valid' => true, 'status' => 'available']);
        $this->postJson(route('register.warranty-code.check'), ['warranty_code' => '123'])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'invalid']);
        $this->postJson(route('register.warranty-code.check'), ['warranty_code' => $used->code])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'used']);
        $this->postJson(route('register.warranty-code.check'), ['warranty_code' => $inactive->code])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'inactive']);
        $this->postJson(route('register.warranty-code.check'), ['warranty_code' => $expired->code])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'expired']);
    }

    public function test_claim_decisions_create_customer_notifications_with_required_context(): void
    {
        Notification::fake();
        $admin = Admin::create(['name' => 'Admin', 'email' => 'review@example.com', 'password' => 'password123']);
        [$user, $subscription] = $this->customerWithSubscription();
        $claim = Claim::create(['claim_number' => 'CLM-DECISION', 'user_id' => $user->id, 'subscription_id' => $subscription->id, 'panels' => ['bonnet'], 'photos' => [], 'status' => 'pending']);

        $this->actingAs($admin, 'admin')->put(route('admin.claims.update', $claim), ['status' => 'approved'])->assertSessionHasErrors('booking_date');
        $booking = now()->addWeek()->toDateString();
        $this->actingAs($admin, 'admin')->put(route('admin.claims.update', $claim), ['status' => 'approved', 'booking_date' => $booking])->assertSessionHas('status');
        Notification::assertSentTo($user, CustomerEventNotification::class, fn ($notification) => $notification->event === 'claim_approved' && str_contains($notification->message, now()->addWeek()->format('d M Y')));

        $declined = Claim::create(['claim_number' => 'CLM-DECLINED', 'user_id' => $user->id, 'subscription_id' => $subscription->id, 'panels' => ['bonnet'], 'photos' => [], 'status' => 'pending']);
        $this->actingAs($admin, 'admin')->put(route('admin.claims.update', $declined), ['status' => 'disapproved'])->assertSessionHasErrors('admin_notes');
        $this->actingAs($admin, 'admin')->put(route('admin.claims.update', $declined), ['status' => 'disapproved', 'admin_notes' => 'Damage is outside the covered area.'])->assertSessionHas('status');
        Notification::assertSentTo($user, CustomerEventNotification::class, fn ($notification) => $notification->event === 'claim_declined' && str_contains($notification->message, 'outside the covered area'));
    }

    public function test_bell_lists_database_notifications_and_can_mark_them_read(): void
    {
        [$user] = $this->customerWithSubscription();
        app(CustomerNotificationService::class)->send($user, 'test_bell', 'test', 'A bell update', 'This appears in the scrolling list.', route('dashboard'), 'Open', [], false);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('A bell update')->assertSee('notification-bell');
        $this->actingAs($user)->postJson(route('notifications.read-all'))->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_generic_customer_email_template_renders_and_delivery_is_recorded(): void
    {
        $user = User::factory()->create();
        $sent = app(CustomerNotificationService::class)->send($user, 'render_test', 'test', 'Email rendering test', 'This message must render without colliding with Laravel mail variables.', route('login'), 'Open Proflect');

        $this->assertTrue($sent);
        $this->assertDatabaseHas('customer_notification_events', ['user_id' => $user->id, 'event_key' => 'render_test']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
    }

    public function test_scheduled_day_14_offer_48_hour_and_term_notices_are_sent_once(): void
    {
        Notification::fake();
        $day14 = User::factory()->create(['created_at' => now()->subDays(14)]);
        $expiringOffer = User::factory()->create(['created_at' => now()->subDays(29)]);
        [$termUser, $subscription] = $this->customerWithSubscription();
        $subscription->update(['ends_at' => now()->addDays(20)]);

        $this->artisan('proflect:send-lifecycle-notifications')->assertSuccessful();
        $this->artisan('proflect:send-lifecycle-notifications')->assertSuccessful();

        Notification::assertSentTo($day14, CustomerEventNotification::class, fn ($notification) => $notification->event === 'offer_day_14');
        Notification::assertSentTo($expiringOffer, CustomerEventNotification::class, fn ($notification) => $notification->event === 'offer_48_hours');
        Notification::assertSentTo($termUser, CustomerEventNotification::class, fn ($notification) => $notification->event === 'term_expiry');
        $this->assertDatabaseCount('customer_notification_events', 4);
    }

    private function customerWithSubscription(): array
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $plan = Plan::create(['name' => 'Warranty Test', 'slug' => 'warranty-test-'.uniqid(), 'price' => 10000, 'duration_years' => 2, 'coverage_sqm' => 5, 'is_active' => true]);
        $subscription = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear()]);

        return [$user, $subscription];
    }
}
