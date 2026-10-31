<section class="landing-films subscription-video" id="films" aria-labelledby="films-title">
    <div class="landing-films__heading">
        <div><span class="eyebrow">SEE THE REFRESH</span><h2 id="films-title">Protection you can see.</h2></div>
        <div class="landing-films__intro"><span>PROFLECT FILM</span><p>Discover how Proflect helps preserve the details that make your vehicle feel exceptional.</p></div>
    </div>
    <div class="landing-film-grid landing-film-grid--single">
        <button class="landing-film landing-film--one" type="button" data-video-open data-video-src="{{ asset('videos/care-refresh-01.mp4') }}" data-video-title="The Proflect Warranty">
            <img class="landing-film__media" src="{{ asset('images/care-refresh-program-cover.jpg') }}" alt="Proflect Car Refresh Program video cover">
            <span class="landing-film__veil"></span>
            <span class="landing-film__topline"><span class="landing-film__number">FILM 01</span><span class="landing-film__watch">WATCH NOW <b>↗</b></span></span>
            <span class="landing-film__play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 7.5 16 12l-7 4.5z"/></svg></span>
            <span class="sr-only">Play The Proflect Warranty video</span>
        </button>
    </div>
</section>
<div class="video-modal" data-video-modal hidden>
    <button class="video-modal__backdrop" type="button" data-video-close aria-label="Close video"></button>
    <div class="video-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="video-modal-title">
        <div class="video-modal__topbar"><div><span>PROFLECT FILM</span><h2 id="video-modal-title" data-video-modal-title>The Proflect Warranty</h2></div><button class="video-modal__close" type="button" data-video-close aria-label="Close video"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <div class="video-modal__stage"><video controls playsinline data-video-player></video><p data-video-error hidden>This video could not be played in your browser. Please try another modern browser.</p></div>
    </div>
</div>
