<x-customer-layout title="Overview">
    <section class="overview-hero">
        <div>
            <span class="eyebrow">GOOD TO SEE YOU</span>
            <div class="overview-hero__welcome" style="gap: 120px!important;">
                <h2>Welcome back, {{ explode(' ', trim(auth()->user()->name))[0] }}.</h2>
                @if(isset($warrantyCode) && $warrantyCode)
                    <button type="button" class="customer-claim-code" data-copy-code data-code="{{ $warrantyCode->code }}" title="Copy active warranty code">
                        <span class="customer-claim-code__status" aria-hidden="true"></span>
                        <span class="customer-claim-code__text"><small>YOUR WARRANTY CODE</small><strong>{{ $warrantyCode->code }}</strong></span>
                        <span class="customer-claim-code__copy" data-copy-icon aria-hidden="true">▣</span>
                        <span class="sr-only" data-copy-feedback aria-live="polite">Click to copy warranty code</span>
                    </button>
                @endif
            </div>
            <p>Manage your vehicle protection, claims and documents from one place.</p>
        </div>
        <a href="{{ route('claims.create') }}" class="button button--dark">Make a claim <span>→</span></a>
    </section>

    <section class="overview-stats">
        <article><span class="overview-stat-icon">✓</span><div><small>PROGRAM STATUS</small><b>{{ $subscription ? 'Active' : 'Not active' }}</b><p>{{ $subscription ? 'Your vehicle protection is ready.' : 'Choose a plan to get protected.' }}</p></div></article>
        <article><span class="overview-stat-icon">◇</span><div><small>ACTIVE PLAN</small><b>{{ $subscription?->plan->name ?? 'No plan' }}</b><p>{{ $subscription ? $subscription->plan->duration_label.' · '.$subscription->plan->coverage_sqm.' m²' : 'Explore available protection plans.' }}</p></div></article>
        <article><span class="overview-stat-icon">▤</span><div><small>CLAIMS</small><b>{{ auth()->user()->claims()->count() }}</b><p>All submitted claim requests.</p></div></article>
    </section>

    @if($subscription)
    <section class="panel warranty-code-action">
        <div>
            <span class="eyebrow">YOUR WARRANTY CODE</span>
            @if(isset($warrantyCode) && $warrantyCode)
                <h3>Your warranty code is always within reach.</h3>
                <p>Click the active warranty code beside your welcome message to copy it. You can also send it to {{ auth()->user()->email }} anytime.</p>
            @else
                <h3>Need your warranty code?</h3>
                <p>We emailed a code when your subscription started. Send the current available code to {{ auth()->user()->email }} again whenever you need it.</p>
            @endif
        </div>
        <form method="POST" action="{{ route('warranty-code.email') }}">
            @csrf
            <button type="submit" class="button button--dark">Get warranty code by email →</button>
        </form>
    </section>
    @endif

    <section class="dashboard-grid overview-grid">
        <article class="panel"><div class="panel__header"><span class="eyebrow">QUICK ACTIONS</span><h3>What would you like to do?</h3></div><div class="action-list"><a href="{{ route('warranty.show') }}"><span class="action-list__icon action-list__icon--green">◇</span><span><b>View my warranty</b><small>See plan details, dates and allowance</small></span><i>→</i></a><a href="{{ route('claims.create') }}"><span class="action-list__icon">＋</span><span><b>Make a claim</b><small>Report damage to protected panels</small></span><i>→</i></a><a href="{{ route('claims.index') }}"><span class="action-list__icon">▤</span><span><b>Claim history</b><small>Track current and previous requests</small></span><i>→</i></a><a href="{{ route('documents.index') }}"><span class="action-list__icon">⇩</span><span><b>Documents</b><small>Download certificates and invoices</small></span><i>→</i></a></div></article>
        <article class="panel overview-program"><span class="eyebrow">YOUR PROGRAM</span><h3>{{ $subscription ? 'Protection is active' : 'Get protected today' }}</h3><p>{{ $subscription ? 'Your Proflect program remains valid until '.$subscription->ends_at->format('d M Y').'.' : 'Review the available plans and select cover for your vehicle.' }}</p><div class="overview-program__car"><img src="{{ asset('images/premium-sedan.png') }}" alt="Protected vehicle"></div><a href="{{ $subscription ? route('warranty.show') : route('subscription.index') }}" class="button button--dark">{{ $subscription ? 'Warranty details' : 'View plans' }} <span>→</span></a></article>
    </section>
</x-customer-layout>
