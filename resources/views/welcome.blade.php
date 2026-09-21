<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proflect · PPF Replacement Program</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="landing-body">
    <header class="landing-nav">
        <x-brand light />
        <button type="button" class="landing-nav__toggle" data-landing-nav-toggle aria-expanded="false" aria-label="Open menu">☰</button>
        <nav data-landing-nav>
            <a href="#benefits">Services</a><a href="#program">Warranty</a><a href="#support">Support</a><a href="{{ route('login') }}">Login</a>
        </nav>
    </header>
    <main>
        <section class="landing-hero" id="program">
            <img src="{{ asset('images/landing-hero-suv.png') }}" alt="Black premium vehicle protected by Proflect">
            <div class="landing-hero__shade"></div>
            <div class="landing-hero__copy">
                <span>PPF REPLACEMENT PROGRAM</span>
                <h1>Premium PPF for a cleaner, brighter tomorrow.</h1>
                <p>Protect your vehicle. Enhance its beauty. Get straightforward replacement support when eligible film is damaged.</p>
                <div class="landing-hero__actions"><a class="button button--light" href="{{ route('register') }}">Get protected <b>→</b></a><a class="landing-text-link" href="#benefits">Explore the program</a></div>
            </div>
            <div class="landing-teaser"><span class="landing-teaser__icon">◇</span><div><b>Add extra peace of mind</b><p>Ask about the Proflect PPF Replacement Program within 30 days.</p><a href="{{ route('register') }}">Learn more →</a></div></div>
        </section>
        <section class="landing-benefits" id="benefits">
            <div><span class="eyebrow">BUILT AROUND YOUR VEHICLE</span><h2>Protection made simple.</h2></div>
            <article><b>01</b><h3>Choose your cover</h3><p>Compare clear plans and select the protection that fits your vehicle.</p></article>
            <article><b>02</b><h3>Keep everything together</h3><p>Access your warranty, documents and allowance from one secure portal.</p></article>
            <article><b>03</b><h3>Claim with confidence</h3><p>Select damaged panels, upload evidence and follow every status update.</p></article>
        </section>
        <section class="landing-support" id="support"><div><span class="eyebrow">NEED A HAND?</span><h2>We’re here when protection matters.</h2></div><a class="button button--dark" href="mailto:support@proflect.com">Contact support <span>→</span></a></section>
    </main>
</body>
</html>
