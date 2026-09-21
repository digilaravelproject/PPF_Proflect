<!DOCTYPE html><html lang="en"><head><link rel="icon" type="image/png" href="{{ asset('favicon.png') }}"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Choose protection · Proflect</title>@vite(['resources/css/app.css','resources/js/app.js'])<style>.plan-cards{max-width:1120px}.plan-card form{margin:0}.free-price{font-size:32px!important;letter-spacing:.04em}</style></head>
<body class="offer-body">
<header class="offer-header"><x-brand light/><div class="offer-header__right"><span>Step 1 of 3</span><form method="POST" action="{{ route('logout') }}">@csrf<button>Sign out</button></form></div></header>
<section class="offer-hero"><div><span class="eyebrow eyebrow--light">EXCLUSIVE CUSTOMER OFFER</span><h1>Keep your PPF protected<br>after the unexpected.</h1><p>Optional accidental-damage replacement support for eligible Proflect PPF customers.</p></div><div class="offer-car" aria-hidden="true"><span></span></div></section>
<main class="offer-main">
    @if(session('status'))<div class="alert alert--success">{{ session('status') }}</div>@endif
    @if($expiredSubscription)
        <div class="subscription-expired" role="alert"><span>!</span><div><b>Your {{ $expiredSubscription->plan->name }} subscription ended on {{ $expiredSubscription->ends_at->format('d M Y') }}.</b><p>Renew your protection below to restore access to your dashboard, claims, documents and profile.</p></div></div>
    @endif
    <div class="stepper"><span class="active"><i>1</i>Choose a plan</span><b></b><span><i>2</i>Checkout</span><b></b><span><i>3</i>Protected</span></div>
    <div class="offer-title"><div><span class="eyebrow">TAILORED PROTECTION</span><h2>{{ $expiredSubscription ? 'Renew your protection to continue.' : 'Choose the cover that fits your journey.' }}</h2></div>@if(!$expiredSubscription)<form method="POST" action="{{ route('subscription.skip') }}">@csrf<button class="skip-button" type="submit">Skip for now →</button></form>@endif</div>
    @if($plans->isEmpty())<div class="empty-state"><h3>No plans available right now</h3><p>You can continue to your dashboard and return later.</p><form method="POST" action="{{ route('subscription.skip') }}">@csrf<button class="button button--dark">Continue to dashboard</button></form></div>@else
    <div class="plan-cards">@foreach($plans as $plan)<article class="plan-card plan-card--{{ $plan->accent }}">
        @if($loop->last)<span class="popular-badge">MOST POPULAR</span>@endif
        <div class="plan-card__head"><span class="plan-badge">{{ strtoupper(substr($plan->name,0,1)) }}</span><div><span>PROFLECT</span><h3>{{ $plan->name }}</h3></div></div>
        <p>{{ $plan->description }}</p><div class="plan-price">@if($plan->is_free)<b class="free-price">FREE</b><span>no card<br>no payment</span>@else<sup>$</sup><b>{{ number_format($plan->price/100) }}</b><span>USD<br>one-time payment</span>@endif</div>
        <div class="plan-term"><b>{{ strtoupper($plan->duration_label) }}</b><span>•</span><b>{{ rtrim(rtrim($plan->coverage_sqm,'0'),'.') }} SQ. METRES</b></div>
        <ul>@foreach($plan->features ?? [] as $feature)<li>✓ <span>{{ $feature }}</span></li>@endforeach</ul>
        @if($plan->is_free && $usedFreePlanIds->contains($plan->id))<button class="button button--unavailable" type="button" disabled>Free trial already used</button>@elseif($plan->is_free)<form method="POST" action="{{ route('subscription.free',$plan) }}">@csrf<button class="button button--dark" type="submit">Start free for {{ $plan->duration_days }} days <span>→</span></button></form>@else<a class="button {{ $plan->accent === 'gold' ? 'button--gold' : 'button--dark' }}" href="{{ route('subscription.checkout',$plan) }}">Choose {{ $plan->name }} <span>→</span></a>@endif
    </article>@endforeach</div>@endif
    <div class="covered-grid"><div><i>✓</i><p><b>Covered</b><br>Approved accidental panel damage. Replacement includes PPF film and labour.</p></div><div><i>×</i><p><b>Not covered</b><br>Peeling and yellowing stay under standard warranty. Ceramic coating is excluded.</p></div><div><i>⌁</i><p><b>Simple claims</b><br>Submit panel damage online and track every update from your dashboard.</p></div></div>
</main></body></html>

\n