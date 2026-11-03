<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\QrAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'qr_access.enabled' => true,
            'qr_access.token' => 'fixed-test-token-1234567890123456',
        ]);
    }

    public function test_qr_page_is_available_without_an_unlocked_session(): void
    {
        $entryUrl = rtrim((string) config('app.url'), '/')
            .route('qr-access.enter', ['token' => QrAccess::token()], false);

        $this->get('/qr_code')
            ->assertOk()
            ->assertSee($entryUrl, false);

        $this->withServerVariables(['HTTP_HOST' => 'untrusted.example'])
            ->get('/qr_code')
            ->assertSee($entryUrl, false);
    }

    public function test_base_url_displays_registration_and_legacy_registration_redirects_to_it(): void
    {
        $this->get('/')->assertOk()->assertSee('Create your account');
        $this->get('/register')->assertRedirect(route('register'));
        $this->get('/login')->assertOk()->assertSee('Welcome back');
    }

    public function test_landing_page_has_its_own_url(): void
    {
        $this->get('/landing-page')
            ->assertOk()
            ->assertSee('Premium PPF');
    }

    public function test_valid_qr_entry_unlocks_the_session(): void
    {
        $this->get(route('qr-access.enter', ['token' => QrAccess::token()]))
            ->assertRedirect(route('register'))
            ->assertSessionHas(config('qr_access.session_key'), true);

        $this->get('/')->assertOk();
    }

    public function test_valid_qr_entry_logs_out_an_existing_customer_and_displays_registration(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $this->actingAs($user)
            ->get(route('qr-access.enter', ['token' => QrAccess::token()]))
            ->assertRedirect(route('register'))
            ->assertSessionHas(config('qr_access.session_key'), true);

        $this->assertGuest();
        $this->get(route('register'))->assertOk()->assertSee('Create your account');
    }

    public function test_invalid_qr_entry_does_not_unlock_the_session(): void
    {
        $this->get('/qr-access/invalid-token-12345678901234567890')->assertNotFound();
        $this->get('/')->assertOk()->assertSee('Create your account');
        $this->get('/forgot-password')->assertRedirect(route('qr-code.show'));
    }

    public function test_admin_routes_are_not_blocked_by_the_customer_qr_gate(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
