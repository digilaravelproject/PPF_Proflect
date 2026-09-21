<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View { return view('admin.auth.forgot-password'); }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::broker('admins')->sendResetLink($request->only('email'));

        return back()->with('status', 'If an admin account exists for that email, a reset link has been sent.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->mixedCase()->numbers()],
        ]);
        $status = Password::broker('admins')->reset($request->only('email', 'password', 'password_confirmation', 'token'), function (Admin $admin, string $password): void {
            $admin->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($admin));
        });

        return $status === Password::PasswordReset
            ? redirect()->route('admin.login')->with('status', 'Password reset successfully. Please sign in.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
