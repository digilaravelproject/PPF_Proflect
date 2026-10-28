<?php

namespace Tests\Feature;

use App\Support\QrAccess;
use Tests\TestCase;

class QrAccessTest extends TestCase
{
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

    public function test_direct_customer_url_redirects_to_qr_page(): void
    {
        $this->get('/')->assertRedirect(route('qr-code.show'));
        $this->get('/login')->assertRedirect(route('qr-code.show'));
    }

    public function test_valid_qr_entry_unlocks_the_session(): void
    {
        $this->get(route('qr-access.enter', ['token' => QrAccess::token()]))
            ->assertRedirect(route('home'))
            ->assertSessionHas(config('qr_access.session_key'), true);

        $this->get('/')->assertOk();
    }

    public function test_invalid_qr_entry_does_not_unlock_the_session(): void
    {
        $this->get('/qr-access/invalid-token-12345678901234567890')->assertNotFound();
        $this->get('/')->assertRedirect(route('qr-code.show'));
    }

    public function test_admin_routes_are_not_blocked_by_the_customer_qr_gate(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
