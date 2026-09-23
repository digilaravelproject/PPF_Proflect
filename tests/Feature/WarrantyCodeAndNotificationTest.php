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

    public function test_claim_consumes_a_warranty_code_and_issues_another_to_the_subscription(): void
    {
        Notification::fake();
        Storage::fake('public');
        [$user] = $this->customerWithSubscription();
        $code = WarrantyCode::query()->where('is_active', true)->whereNull('used_at')->firstOrFail();

        $this->actingAs($user)->post(route('claims.store'), [
            'warranty_code' => $code->code,
            'vehicle_make' => 'Toyota', 'vehicle_model' => 'Fortuner', 'registration_number' => 'MH12AB1234',
            'panels' => ['roof'], 'photos' => [UploadedFile::fake()->image('damage.jpg')],
        ])->assertRedirect();

        $claim = Claim::firstOrFail();
        $this->assertSame($code->id, $claim->warranty_code_id);
        $this->assertSame(['roof'], $claim->panels);
        $this->assertNotNull($code->fresh()->used_at);
        $this->assertSame($user->id, $code->fresh()->used_by_user_id);
        $this->assertDatabaseHas('warranty_codes', ['subscription_id' => $claim->subscription_id, 'used_at' => null, 'is_active' => true]);
        Notification::assertSentTo($user, CustomerEventNotification::class, fn ($notification) => $notification->event === 'claim_received');

        [$other] = $this->customerWithSubscription();
        $this->actingAs($other)->from(route('claims.create'))->post(route('claims.store'), [
            'warranty_code' => $code->code,
            'vehicle_make' => 'Honda', 'vehicle_model' => 'City', 'registration_number' => 'MH01AA0001',
            'panels' => ['front_bumper'], 'photos' => [UploadedFile::fake()->image('second.jpg')],
        ])->assertRedirect(route('claims.create'))->assertSessionHasErrors('warranty_code');
    }

    public function test_admin_can_filter_toggle_delete_and_view_used_customer_details(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'codes@example.com', 'password' => 'password123']);
        [$user, $subscription] = $this->customerWithSubscription();
        $used = WarrantyCode::query()->firstOrFail();
        $claim = Claim::create(['claim_number' => 'CLM-TEST-USED', 'user_id' => $user->id, 'subscription_id' => $subscription->id, 'warranty_code_id' => $used->id, 'panels' => ['bonnet'], 'photos' => [], 'status' => 'pending']);
        $used->update(['used_by_user_id' => $user->id, 'used_at' => now()]);

        $this->actingAs($admin, 'admin')->get(route('admin.warranty-codes.index', ['search' => $used->code, 'status' => 'used']))->assertOk()->assertSee($used->code)->assertSee($user->email);
        $this->actingAs($admin, 'admin')->get(route('admin.warranty-codes.show', $used->id))->assertOk()->assertSee($claim->claim_number);

        $available = WarrantyCode::query()->whereNull('used_at')->firstOrFail();
        $this->actingAs($admin, 'admin')->patch(route('admin.warranty-codes.toggle', $available->id))->assertSessionHas('status');
        $this->assertFalse($available->fresh()->is_active);
        $this->assertDatabaseCount('warranty_codes', 101);

        $deletable = WarrantyCode::query()->whereNull('used_at')->where('is_active', true)->firstOrFail();
        $this->actingAs($admin, 'admin')->delete(route('admin.warranty-codes.destroy', $deletable->id))->assertRedirect(route('admin.warranty-codes.index'));
        $this->assertSoftDeleted('warranty_codes', ['id' => $deletable->id]);
        $this->assertSame(102, WarrantyCode::withTrashed()->count());
    }

    public function test_warranty_code_can_be_checked_instantly_for_every_availability_state(): void
    {
        [$user] = $this->customerWithSubscription();
        $available = WarrantyCode::query()->whereNull('used_at')->where('is_active', true)->firstOrFail();

        $this->actingAs($user)->postJson(route('claims.warranty-code.check'), ['warranty_code' => $available->code])
            ->assertOk()->assertJson(['valid' => true, 'status' => 'available']);
        $this->actingAs($user)->postJson(route('claims.warranty-code.check'), ['warranty_code' => 'NOT-A-CODE'])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'invalid']);

        $used = WarrantyCode::query()->whereKeyNot($available->id)->firstOrFail();
        $used->update(['used_by_user_id' => $user->id, 'used_at' => now()]);
        $this->actingAs($user)->postJson(route('claims.warranty-code.check'), ['warranty_code' => $used->code])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'used']);

        $inactive = WarrantyCode::query()->whereNotIn('id', [$available->id, $used->id])->firstOrFail();
        $inactive->update(['is_active' => false]);
        $this->actingAs($user)->postJson(route('claims.warranty-code.check'), ['warranty_code' => $inactive->code])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'inactive']);

        $deleted = WarrantyCode::query()->whereNotIn('id', [$available->id, $used->id, $inactive->id])->firstOrFail();
        $deleted->delete();
        $this->actingAs($user)->postJson(route('claims.warranty-code.check'), ['warranty_code' => $deleted->code])
            ->assertOk()->assertJson(['valid' => false, 'status' => 'deleted']);
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
