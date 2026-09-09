<x-guest-layout>
    <x-slot:title>Sign in</x-slot:title>

    <div class="form-heading">
        <span class="eyebrow">CUSTOMER PORTAL</span>
        <h2>Welcome back</h2>
        <p>Sign in to manage your Proflect protection.</p>
    </div>

    @if (session('status'))
        <div class="alert alert--success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <div class="field__control">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="email">
            </div>
            @error('email') <p class="field__error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <div class="field__label-row">
                <label for="password">Password</label>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <div class="field__control">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                <input id="password" name="password" type="password" placeholder="Enter your password" required autocomplete="current-password">
                <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5z"/><circle cx="12" cy="12" r="2.5"/></svg>
                </button>
            </div>
            @error('password') <p class="field__error">{{ $message }}</p> @enderror
        </div>

        <label class="check-row">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me on this device</span>
        </label>

        <button type="submit" class="button button--primary">Sign in <span aria-hidden="true">→</span></button>
    </form>

    <p class="form-switch">New to Proflect? <a href="{{ route('register') }}">Create an account</a></p>
    <div class="trust-note"><span>✓</span><p><b>Your details stay protected</b><br>Encrypted and securely handled.</p></div>
</x-guest-layout>
