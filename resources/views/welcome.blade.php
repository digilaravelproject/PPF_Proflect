<!DOCTYPE html>
<html lang="en">
<head><link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proflect · Car Refresh Program (PCF)</title>
    @vite(['resources/css/app.css', 'resources/css/auth-shade.css', 'resources/js/app.js'])
    <!-- New external CSS loaded after existing styles -->
    <link
        rel="stylesheet"
        href="{{ asset('css/proflect-video-fullwidth.css') }}?v=1"
    >
</head>
<body class="landing-body">
    <header class="landing-nav">
        <x-brand light />
        <button type="button" class="landing-nav__toggle" data-landing-nav-toggle aria-expanded="false" aria-label="Open menu">☰</button>
        <nav data-landing-nav>
            <a href="#benefits">Services</a><a href="#films">Watch films</a><a href="#program">Warranty</a><a href="#support">Support</a><a href="{{ route('login') }}">Login</a>
        </nav>
    </header>
    <main>
        <section class="landing-hero" id="program">
            <img src="{{ asset('images/rolls-royce-spectre-series-ii-hero.png') }}" alt="Black Rolls-Royce Spectre Series II protected by Proflect">
            <div class="landing-hero__shade"></div>
            <div class="landing-hero__copy">
                <span>PROTECT YOUR PROTECTION</span>
                <h1>Your PPF is an investment. Keep it covered.</h1>
                <p>Insure your PPF. Enhance its beauty. Get straightforward replacement support when eligible film is damaged.</p>
                <div class="landing-hero__actions"><a class="button button--light" href="{{ route('register') }}">Protect My PPF Now <b>→</b></a><a class="landing-text-link" href="#benefits">Explore PCF</a></div>
            </div>
            <div class="landing-teaser"><span class="landing-teaser__icon">◇</span><div><b>Add extra peace of mind</b><p>Ask about the Proflect Car Refresh Program (PCF) within 30 days.</p><a href="{{ route('register') }}">Learn more →</a></div></div>
        </section>
        <section class="landing-films" id="films" aria-labelledby="films-title">
            <div class="landing-films__heading">
                <div>
                    <span class="eyebrow">SEE THE REFRESH</span>
                    <h2 id="films-title">Protection you can see.</h2>
                </div>
                <div class="landing-films__intro"><span>2 SHORT FILMS</span><p>Discover how Proflect helps preserve the details that make your vehicle feel exceptional.</p></div>
            </div>
            <div class="landing-film-grid">
                <button class="landing-film landing-film--one" type="button" data-video-open data-video-src="{{ asset('videos/care-refresh-01.mp4') }}" data-video-title="The Proflect finish">
                    <img class="landing-film__media" src="{{ asset('videos/care-refresh-01-poster.jpg') }}" alt="" aria-hidden="true">
                    <span class="landing-film__veil"></span>
                    <span class="landing-film__topline"><span class="landing-film__number">FILM 01</span><span class="landing-film__watch">WATCH NOW <b>↗</b></span></span>
                    <span class="landing-film__content">
                        <span class="landing-film__play" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M9 7.5 16 12l-7 4.5z"/></svg>
                        </span>
                        <span class="landing-film__copy"><small>01:18 · THE PROFLECT STORY</small><b>The Proflect finish</b><span>Experience a cleaner, more confident drive.</span></span>
                    </span>
                    <span class="sr-only">Play The Proflect finish video</span>
                </button>
                <?php /*<button class="landing-film landing-film--two" type="button" data-video-open data-video-src="{{ asset('videos/care-refresh-02.mp4') }}" data-video-title="Refresh with confidence">
                    <img class="landing-film__media" src="{{ asset('videos/care-refresh-02-poster.jpg') }}" alt="" aria-hidden="true">
                    <span class="landing-film__veil"></span>
                    <span class="landing-film__topline"><span class="landing-film__number">FILM 02</span><span class="landing-film__watch">WATCH NOW <b>↗</b></span></span>
                    <span class="landing-film__content">
                        <span class="landing-film__play" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M9 7.5 16 12l-7 4.5z"/></svg>
                        </span>
                        <span class="landing-film__copy"><small>01:18 · CARE REFRESH</small><b>Refresh with confidence</b><span>See care, clarity and protection in motion.</span></span>
                    </span>
                    <span class="sr-only">Play Refresh with confidence video</span>
                </button>*/ ?>
            </div>
        </section>
        <section class="landing-benefits" id="benefits">
            <div><span class="eyebrow">BUILT AROUND YOUR VEHICLE</span><h2>Protection made simple.</h2></div>
            <article><b>01</b><h3>Choose your cover</h3><p>Compare clear plans and select the protection that fits your vehicle.</p></article>
            <article><b>02</b><h3>Keep everything together</h3><p>Access your warranty, documents and allowance from one secure portal.</p></article>
            <article><b>03</b><h3>Claim with confidence</h3><p>Select damaged panels, upload evidence and follow every status update.</p></article>
        </section>
        <section class="landing-support" id="support"><div><span class="eyebrow">NEED A HAND?</span><h2>We’re here when protection matters.</h2></div><a class="button button--dark" href="mailto:contact@proflect.com.au">Contact support <span>→</span></a></section>
    </main>
    <div class="video-modal" data-video-modal hidden>
        <button class="video-modal__backdrop" type="button" data-video-close aria-label="Close video"></button>
        <div class="video-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="video-modal-title">
            <div class="video-modal__topbar">
                <div><span>PROFLECT FILM</span><h2 id="video-modal-title" data-video-modal-title>Care Refresh</h2></div>
                <button class="video-modal__close" type="button" data-video-close aria-label="Close video">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="video-modal__stage">
                <video controls playsinline data-video-player></video>
                <p data-video-error hidden>This video could not be played in your browser. Please try again in another modern browser.</p>
            </div>
        </div>
    </div>
</body>
</html>
