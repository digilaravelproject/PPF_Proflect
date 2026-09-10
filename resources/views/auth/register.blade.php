<x-guest-layout>
    <x-slot:title>Create account</x-slot:title>

    <div class="form-heading">
        <span class="eyebrow">GET STARTED</span>
        <h2>Create your account</h2>
        <p>Join Proflect and keep your protection close.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form auth-form--compact">
        @csrf
        <div class="field">
            <label for="name">Full name</label>
            <div class="field__control">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/></svg>
                <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Your full name" required autofocus autocomplete="name">
            </div>
            @error('name') <p class="field__error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="email">Email address</label>
            <div class="field__control">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
            </div>
            @error('email') <p class="field__error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="phone">Phone number</label>
            <div class="field__control">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3l3 4-2 2c1.5 3 3.5 5 6.5 6.5l2-2 4 3c-1 3-3 4.5-5.5 4C9 19 5 15 3.5 9 3 6.5 4 4 7 3z"/></svg>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="Your phone number" required autocomplete="tel">
            </div>
            @error('phone') <p class="field__error">{{ $message }}</p> @enderror
        </div>

        <div class="field-grid">
            <div class="field">
                <label for="password">Password</label>
                <div class="field__control">
                    <input id="password" name="password" type="password" placeholder="Min. 8 characters" required autocomplete="new-password">
                    <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <div class="field__control">
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat password" required autocomplete="new-password">
                </div>
            </div>
        </div>
        @error('password') <p class="field__error field__error--raised">{{ $message }}</p> @enderror

        <label class="check-row">
            <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
            <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</span>
        </label>
        @error('terms') <p class="field__error field__error--raised">{{ $message }}</p> @enderror

        <button type="submit" class="button button--primary">Create account <span aria-hidden="true">→</span></button>
    </form>

    <p class="form-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</x-guest-layout>
