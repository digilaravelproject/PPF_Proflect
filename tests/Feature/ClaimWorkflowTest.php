<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Claim;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WarrantyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_complete_claim_with_panels_and_photos(): void
    {
        Storage::fake('public');
        [$user, $subscription] = $this->customerWithSubscription();
        $warrantyCode = WarrantyCode::query()->where('is_active', true)->whereNull('used_at')->firstOrFail();

        $this->actingAs($user)->get(route('claims.create'))
            ->assertOk()
            ->assertSee('Select damaged panels')
            ->assertSee('Upload damage photos')
            ->assertSee('Review');

        $response = $this->actingAs($user)->post(route('claims.store'), [
            'warranty_code' => $warrantyCode->code,
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Fortuner',
            'registration_number' => 'MH12AB1234',
            'vehicle_year' => 2024,
            'panels' => ['front_bumper', 'left_fender'],
            'photos' => [UploadedFile::fake()->image('damage.jpg', 1000, 700)],
            'description' => 'Scraped while parking.',
        ]);

        $claim = Claim::firstOrFail();
        $response->assertRedirect(route('claims.show', $claim));
        $this->assertSame($subscription->id, $claim->subscription_id);
        $this->assertSame('MH12AB1234', $claim->registration_number);
        $this->assertSame(['front_bumper', 'left_fender'], $claim->panels);
        Storage::disk('public')->assertExists($claim->photos[0]);
        $this->actingAs($user)->get(route('claims.show', $claim))->assertOk()->assertSee($claim->claim_number);
    }

    public function test_claims_require_an_active_subscription_and_are_private(): void
    {
        Storage::fake('public');
        [$owner] = $this->customerWithSubscription();
        $other = User::factory()->create(['onboarding_completed_at' => now()]);
        $claim = Claim::create([
            'claim_number' => 'CLM-PRIVATE', 'user_id' => $owner->id,
            'subscription_id' => $owner->subscriptions()->firstOrFail()->id,
            'panels' => ['bonnet'], 'photos' => ['claims/private.jpg'], 'status' => 'pending',
        ]);

        $this->actingAs($other)->get(route('claims.create'))->assertNotFound();
        $this->actingAs($other)->get(route('claims.show', $claim))->assertNotFound();
        $this->actingAs($other)->get(route('claims.photo', [$claim, 0]))->assertNotFound();
    }

    public function test_admin_can_filter_review_delete_claims_and_download_both_reports(): void
    {
        Storage::fake('public');
        [$user, $subscription, $plan] = $this->customerWithSubscription();
        Storage::disk('public')->put('claims/evidence.jpg', 'image');
        $claim = Claim::create([
            'claim_number' => 'CLM-REPORT-001', 'user_id' => $user->id, 'subscription_id' => $subscription->id,
            'vehicle_make' => 'Toyota', 'vehicle_model' => 'Fortuner', 'registration_number' => 'MH12AB1234',
            'panels' => ['right_door'], 'photos' => ['claims/evidence.jpg'], 'status' => 'pending',
        ]);
        Payment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'gateway_order_id' => 'order_report_1',
            'gateway_payment_id' => 'pay_report_1', 'amount' => 54900, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now(),
        ]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'claims-admin@example.com', 'password' => 'password123']);

        $this->actingAs($admin, 'admin')->get(route('admin.claims.index', ['search' => 'CLM-REPORT', 'status' => 'pending']))
            ->assertOk()->assertSee('CLM-REPORT-001')->assertSee('MH12AB1234');
        $this->actingAs($admin, 'admin')->put(route('admin.claims.update', $claim), [
            'status' => 'approved', 'admin_notes' => 'Damage is covered.', 'booking_date' => now()->addWeek()->toDateString(),
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('claims', ['id' => $claim->id, 'status' => 'approved', 'admin_notes' => 'Damage is covered.']);

        $this->actingAs($admin, 'admin')->get(route('admin.claims.report'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin, 'admin')->get(route('admin.payments.report', ['status' => 'paid']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin, 'admin')->get(route('admin.payments.index', ['search' => 'pay_report_1']))
            ->assertOk()->assertSee('pay_report_1');
        $this->actingAs($admin, 'admin')->get(route('admin.claims.show', $claim))
            ->assertOk()->assertSee('CUSTOMER SELECTED PANELS')->assertSee('Right Door');
        $this->actingAs($admin, 'admin')->get(route('admin.reports.index'))
            ->assertOk()->assertSee('Export to Excel')->assertSee('Export to PDF');
        $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Claims over six months')->assertSee('Recent claim requests');
        $this->actingAs($admin, 'admin')->get(route('admin.reports.export', ['type' => 'all', 'format' => 'excel']))
            ->assertOk()->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->actingAs($admin, 'admin')->get(route('admin.reports.export', ['type' => 'claims', 'format' => 'pdf']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin, 'admin')->delete(route('admin.claims.destroy', $claim))->assertRedirect(route('admin.claims.index'));
        Storage::disk('public')->assertMissing('claims/evidence.jpg');
        $this->assertDatabaseMissing('claims', ['id' => $claim->id]);
    }

    public function test_customer_documents_render_and_invalid_future_vehicle_year_has_a_clear_error(): void
    {
        Storage::fake('public');
        [$user, $subscription] = $this->customerWithSubscription();

        $this->actingAs($user)->get(route('documents.index'))
            ->assertOk()->assertSee('Your documents')->assertSee('WARRANTY CERTIFICATE');
        $this->actingAs($user)->get(route('documents.certificate', $subscription))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)->from(route('claims.create'))->post(route('claims.store'), [
            'warranty_code' => WarrantyCode::query()->where('is_active', true)->whereNull('used_at')->value('code'),
            'vehicle_make' => 'Tata',
            'vehicle_model' => 'Indigo XZ',
            'registration_number' => 'MH24AH0850',
            'vehicle_year' => now()->year + 2,
            'panels' => ['right_fender'],
            'photos' => [UploadedFile::fake()->image('damage.jpg')],
        ])->assertRedirect(route('claims.create'))->assertSessionHasErrors('vehicle_year');
    }

    private function customerWithSubscription(): array
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $plan = Plan::create([
            'name' => 'Test Plan', 'slug' => 'test-plan-'.uniqid(), 'price' => 54900,
            'duration_years' => 2, 'coverage_sqm' => 5, 'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'payment_id' => null,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear(),
        ]);

        return [$user, $subscription, $plan];
    }
}
