<?php

namespace Tests\Feature;

use App\Mail\WelcomeCustomerMail;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login_and_auth_pages_render(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Welcome back');
        $this->get(route('register'))->assertOk()->assertSee('Create your account');
        $this->get(route('password.request'))->assertOk()->assertSee('Forgot your password?');
    }

    public function test_customer_can_register_and_is_signed_in(): void
    {
        Mail::fake();
        $response = $this->post(route('register'), [
            'name' => 'Rahul Kulkarni',
            'email' => 'rahul@example.com',
            'password' => 'Protection123',
            'password_confirmation' => 'Protection123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('subscription.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'rahul@example.com']);
        $this->assertTrue(Hash::check('Protection123', User::first()->password));
        Mail::assertSent(WelcomeCustomerMail::class);
    }

    public function test_registration_requires_a_strong_confirmed_password_and_terms(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => 'Rahul Kulkarni',
            'email' => 'rahul@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['password', 'terms']);

        $this->assertGuest();
    }

    public function test_customer_can_sign_in_and_view_dashboard(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Protection123'), 'onboarding_completed_at' => now()]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Protection123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk()->assertSee($user->name)->assertSee('My Warranty');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication_and_customer_can_logout(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_reset_link_can_be_requested_without_disclosing_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        $this->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }

    public function test_customer_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewProtection456',
            'password_confirmation' => 'NewProtection456',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewProtection456', $user->fresh()->password));
    }
}
