<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>{{ $title ?? 'Customer account' }} · {{ config('app.name', 'Proflect') }}</title>
    <meta name="description" content="Manage your Proflect PPF replacement program.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-visual" aria-label="Proflect protection program">
            <div class="auth-visual__top">
                <x-brand light />
                <span class="auth-secure"><span></span> Secure customer portal</span>
            </div>
            <div class="auth-visual__copy">
                <span class="eyebrow eyebrow--light">PPF REPLACEMENT PROGRAM</span>
                <h1>Protection that<br>goes the distance.</h1>
                <p>Your vehicle deserves more than coverage. Proflect keeps every journey protected, from purchase to claim.</p>
                <div class="auth-benefits">
                    <span><b>5 years</b> program cover</span>
                    <span><b>Simple</b> online claims</span>
                    <span><b>Always</b> within reach</span>
                </div>
            </div>
            <div class="road-art" aria-hidden="true">
                <span class="road-art__glow"></span>
                <span class="road-art__line road-art__line--one"></span>
                <span class="road-art__line road-art__line--two"></span>
                <span class="road-art__wheel"></span>
            </div>
            <p class="auth-visual__footer">Drive Protected. Always.</p>
        </section>
        <section class="auth-panel">
            <div class="auth-panel__mobile-brand"><x-brand /></div>
            <div class="auth-card">{{ $slot }}</div>
            <p class="auth-copyright">© {{ date('Y') }} Proflect. All rights reserved. <a href="{{ route('privacy') }}">Privacy</a></p>
        </section>
    </main>
</body>
</html>
