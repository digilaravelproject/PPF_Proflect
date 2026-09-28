@props(['light' => false])

<a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="brand {{ $light ? 'brand--light' : '' }}" aria-label="Proflect home">
    <img class="brand__logo" src="{{ asset($light ? 'images/proflect-logo-full.png' : 'images/proflect-logo-full-dark.png') }}" alt="Proflect Paint Protection">
</a>
