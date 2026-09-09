@props(['light' => false])

<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="brand {{ $light ? 'brand--light' : '' }}" aria-label="Proflect home">
    <span class="brand__mark" aria-hidden="true"><span>P</span></span>
    <span class="brand__word">PROFLECT</span>
</a>
