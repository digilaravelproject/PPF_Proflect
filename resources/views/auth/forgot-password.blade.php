<x-guest-layout>
    <x-slot:title>Forgot password</x-slot:title>

    <a href="{{ route('login') }}" class="back-link">← Back to sign in</a>
    <div class="form-icon" aria-hidden="true">↗</div>
    <div class="form-heading">
        <span class="eyebrow">ACCOUNT RECOVERY</span>
        <h2>Forgot your password?</h2>
        <p>No problem. Enter your email and we’ll send you a secure reset link.</p>
    </div>

    @if (session('status'))
        <div class="alert alert--success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <div class="field__control">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="email">
            </div>
            @error('email') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="button button--primary">Send reset link <span aria-hidden="true">→</span></button>
    </form>

    <div class="help-note"><b>Still need help?</b><br>Contact your Proflect program administrator.</div>
</x-guest-layout>
