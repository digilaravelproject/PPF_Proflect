<x-guest-layout>
    <x-slot:title>Create account</x-slot:title>

    <div class="form-heading">
        <span class="eyebrow">GET STARTED</span>
        <h2>Create your account</h2>
        <p>Join Proflect and keep your protection close.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form auth-form--compact" data-registration-form data-warranty-check-url="{{ route('register.warranty-code.check') }}">
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

        <div class="registration-warranty">
            <div class="field">
                <label for="warranty-code">Warranty number / code</label>
                <div class="field__control" data-warranty-control>
                    <input id="warranty-code" name="warranty_code" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('warranty_code') }}" placeholder="5-digit code" required autocomplete="off">
                </div>
                <p class="registration-warranty__help">Enter the 5-digit code provided to you by the admin.</p>
                <p class="warranty-feedback @error('warranty_code') invalid @enderror" data-warranty-feedback aria-live="polite">@error('warranty_code'){{ $message }}@enderror</p>
            </div>
            <div class="ppf-code-card ppf-code-card--preview" data-card-animate tabindex="0" role="button" aria-label="Animate PPF protection warranty card preview">
                <div class="ppf-code-card__brand"><strong>PROFLECT</strong><span>PAINT PROTECTION</span></div>
                <small>WARRANTY</small>
                <div class="ppf-code-card__number">
                    <img class="ppf-code-card__logo-mark" src="{{ asset('images/proflect-logo.png') }}" alt="Proflect">
                    <div class="ppf-code-card__code-wrap">
                        <b data-code-preview>{{ old('warranty_code') ?: '-----' }}</b>
                        <em>Expires {{ date('d/m/y', strtotime('+5 years')) }}</em>
                    </div>
                </div>
            </div>
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
            <span>I agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms of Service</a> and <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</span>
        </label>
        @error('terms') <p class="field__error field__error--raised">{{ $message }}</p> @enderror

        <button type="submit" class="button button--primary">Create account <span aria-hidden="true">→</span></button>
    </form>

    <p class="form-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</x-guest-layout>
