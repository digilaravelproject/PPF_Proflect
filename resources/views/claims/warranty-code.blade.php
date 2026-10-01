<x-guest-layout>
    <x-slot:title>Verify warranty code</x-slot:title>

    <div class="form-heading">
        <span class="eyebrow">YOUR NEXT CLAIM</span>
        <h2>Enter a new warranty code</h2>
        <p>Each subscription covers one claim. Verify a new code, then choose a new subscription to continue.</p>
    </div>

    <form method="POST" action="{{ route('claims.warranty.store') }}" class="auth-form" data-registration-form data-warranty-check-url="{{ route('claims.warranty.check') }}">
        @csrf
        <div class="registration-warranty">
            <div class="field">
                <label for="warranty-code">Warranty number / code</label>
                <div class="field__control" data-warranty-control>
                    <input id="warranty-code" name="warranty_code" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('warranty_code') }}" placeholder="5-digit code" required autofocus autocomplete="off">
                </div>
                <p class="registration-warranty__help">Enter the new 5-digit code provided to you by the admin.</p>
                <p class="warranty-feedback @error('warranty_code') invalid @enderror" data-warranty-feedback aria-live="polite">@error('warranty_code'){{ $message }}@enderror</p>
            </div>
            <div class="ppf-code-card ppf-code-card--preview" data-card-animate tabindex="0" role="button" aria-label="Animate PPF protection warranty card preview">
                <div class="ppf-code-card__brand"><strong>PROFLECT</strong><span>PAINT PROTECTION</span></div>
                <small>WARRANTY</small>
                <div class="ppf-code-card__number">
                    <img class="ppf-code-card__logo-mark" src="{{ asset('images/proflect-logo.png') }}" alt="Proflect">
                    <div class="ppf-code-card__code-wrap">
                        <b data-code-preview>{{ old('warranty_code') ?: '-----' }}</b>
                        <em>Single claim access</em>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="button button--primary">Verify and choose subscription <span aria-hidden="true">→</span></button>
        <a href="{{ route('dashboard') }}" class="back-link">← Back to dashboard</a>
    </form>
</x-guest-layout>
