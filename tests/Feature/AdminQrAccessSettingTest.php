<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\WarrantyCodeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminQrAccessSettingTest extends TestCase
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

    public function test_admin_can_view_qr_code_master_page(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Password1']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.masters.qr-code.edit'))
            ->assertOk()
            ->assertSee('Customer QR access')
            ->assertSee('QR-only access')
            ->assertSee('data-qr-code', false);
    }

    public function test_admin_can_disable_and_enable_qr_only_access(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Password1']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.masters.qr-code.update'), ['qr_access_enabled' => '0'])
            ->assertRedirect();

        $this->assertFalse(WarrantyCodeSetting::findOrFail(1)->qr_access_enabled);
        Auth::guard('admin')->logout();
        Auth::shouldUse('web');
        $this->get('/')->assertOk();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.masters.qr-code.update'), ['qr_access_enabled' => '1'])
            ->assertRedirect();

        $this->assertTrue(WarrantyCodeSetting::findOrFail(1)->qr_access_enabled);
        Auth::guard('admin')->logout();
        Auth::shouldUse('web');
        $this->get('/')->assertOk()->assertSee('Welcome back');
    }
}
