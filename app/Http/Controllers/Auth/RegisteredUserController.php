<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeCustomerMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ]);

        $user = User::create($validated);

        event(new Registered($user));
        try {
            Mail::to($user)->send(new WelcomeCustomerMail($user));
        } catch (Throwable $exception) {
            report($exception);
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('subscription.index')->with('status', 'Welcome to Proflect. Choose a protection plan or skip for now.');
    }
}
