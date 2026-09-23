@props(['light' => false])

<a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="brand {{ $light ? 'brand--light' : '' }}" aria-label="Proflect home">
    <img class="brand__logo" src="{{ asset('images/proflect-logo.webp') }}" alt="" aria-hidden="true">
    <?php /*<span class="brand__word">PROFLECT</span> */?>
</a>
