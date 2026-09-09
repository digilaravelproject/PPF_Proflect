<x-guest-layout>
    <x-slot:title>Reset password</x-slot:title>

    <div class="form-icon" aria-hidden="true">✓</div>
    <div class="form-heading">
        <span class="eyebrow">SECURE YOUR ACCOUNT</span>
        <h2>Choose a new password</h2>
        <p>Use a strong password you haven’t used before.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
            <label for="email">Email address</label>
            <div class="field__control">
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">
            </div>
            @error('email') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="password">New password</label>
            <div class="field__control">
                <input id="password" name="password" type="password" placeholder="Min. 8 characters" required autocomplete="new-password">
                <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5z"/><circle cx="12" cy="12" r="2.5"/></svg>
                </button>
            </div>
            @error('password') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <div class="field__control">
                <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat new password" required autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="button button--primary">Reset password <span aria-hidden="true">→</span></button>
    </form>
</x-guest-layout>
