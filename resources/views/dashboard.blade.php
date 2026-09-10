<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Warranty · {{ config('app.name', 'Proflect') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-body">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar__brand"><x-brand light /></div>
        <nav class="sidebar__nav" aria-label="Main navigation">
            <a class="active" href="{{ route('dashboard') }}"><span>⌂</span> Overview</a>
            <a href="#warranty"><span>◇</span> My warranty</a>
            <a href="{{ route('claims.index') }}"><span>▣</span> Claims</a>
            <a href="{{ route('profile.edit') }}"><span>♙</span> My profile</a>
            <a href="{{ route('subscription.index') }}"><span>◇</span> Protection plans</a>
            <a href="#documents"><span>⇩</span> Documents</a>
        </nav>
        <div class="sidebar__help">
            <span class="sidebar__help-icon">?</span>
            <b>Need some help?</b>
            <p>Our protection team is here for you.</p>
            <a href="mailto:support@proflect.com">Contact support</a>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar__logout"><span>↪</span> Sign out</button>
        </form>
    </aside>

    <main class="dashboard-main">
        <header class="topbar">
            <button class="menu-button" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false">☰</button>
            <div>
                <p class="topbar__kicker">CUSTOMER PORTAL</p>
                <h1>My Warranty</h1>
            </div>
            <div class="profile-menu">
                <a href="{{ route('profile.edit') }}" class="profile-menu__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</a>
                <span><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></span>
            </div>
        </header>

        <div class="dashboard-content">
            @if (session('status'))
                <div class="alert alert--success dashboard-alert" role="status">{{ session('status') }}</div>
            @endif

            <section class="welcome-row">
                <div>
                    <h2>Welcome back, {{ explode(' ', trim(auth()->user()->name))[0] }}.</h2>
                    <p>Here’s everything you need to manage your vehicle protection.</p>
                </div>
                <a href="{{ route('claims.create') }}" class="button button--dark">Make a claim <span>→</span></a>
            </section>

            @if ($subscription)
            <section class="warranty-card" id="warranty">
                <div class="warranty-card__accent"></div>
                <div class="warranty-card__top">
                    <span class="status-pill"><i></i> PROGRAM ACTIVE</span>
                    <span class="warranty-card__id">WARRANTY #PF-{{ str_pad($subscription->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="warranty-card__body">
                    <div class="vehicle-art" aria-hidden="true">
                        <div class="vehicle-art__car">PROFLECT</div>
                        <div class="vehicle-art__shadow"></div>
                    </div>
                    <div class="warranty-card__details">
                        <span class="eyebrow">YOUR VEHICLE</span>
                        <h3>Your registered vehicle</h3>
                        <p class="reg-number">PROFILE DETAILS AVAILABLE</p>
                        <div class="plan-row">
                            <span class="plan-badge">{{ strtoupper(substr($subscription->plan->name, 0, 1)) }}</span>
                            <span><b>{{ $subscription->plan->name }}</b><small>{{ $subscription->plan->duration_label }} · {{ rtrim(rtrim($subscription->plan->coverage_sqm, '0'), '.') }} square metres</small></span>
                        </div>
                    </div>
                    <div class="coverage-ring" style="--progress: 0deg">
                        <div><b>0</b><span>of 5 m² used</span></div>
                    </div>
                </div>
                <div class="warranty-card__footer">
                    <div><span>START DATE</span><b>{{ $subscription->starts_at->format('d M Y') }}</b></div>
                    <div><span>VALID UNTIL</span><b>{{ $subscription->ends_at->format('d M Y') }}</b></div>
                    <div><span>PROGRAM STATUS</span><b class="text-green">Active & protected</b></div>
                    <a href="#documents">View certificate →</a>
                </div>
            </section>
            @else
            <section class="panel no-plan-card"><span class="eyebrow">PROTECTION PLAN</span><h3>No active plan yet</h3><p>You skipped plan selection. You can explore the available protection options whenever you are ready.</p><a href="{{ route('subscription.index') }}" class="button button--dark">View protection plans <span>→</span></a></section>
            @endif

            <section class="dashboard-grid">
                <article class="panel" id="claims">
                    <div class="panel__header"><div><span class="eyebrow">QUICK ACTIONS</span><h3>What would you like to do?</h3></div></div>
                    <div class="action-list">
                        <a href="{{ route('claims.create') }}"><span class="action-list__icon action-list__icon--green">＋</span><span><b>Make a claim</b><small>Report damage to your protected panels</small></span><i>→</i></a>
                        <a href="{{ route('claims.index') }}"><span class="action-list__icon">▤</span><span><b>View claim history</b><small>Track current and previous claims</small></span><i>→</i></a>
                        <a href="#"><span class="action-list__icon">⇄</span><span><b>Transfer ownership</b><small>Move coverage to a new owner</small></span><i>→</i></a>
                        <a href="#vehicle"><span class="action-list__icon">◇</span><span><b>Vehicle details</b><small>Review your registered vehicle</small></span><i>→</i></a>
                    </div>
                </article>

                <article class="panel protection-panel">
                    <span class="eyebrow">COVERAGE SUMMARY</span>
                    <h3>Your protection at a glance</h3>
                    <div class="coverage-bar"><span style="width: 0%"></span></div>
                    <div class="coverage-labels"><span><b>0 m²</b> used</span><span><b>{{ $subscription ? rtrim(rtrim($subscription->plan->coverage_sqm, '0'), '.') : 0 }} m²</b> available</span></div>
                    <hr>
                    <div class="detail-row"><span>Plan type</span><b>{{ $subscription?->plan->name ?? 'No active plan' }}</b></div>
                    <div class="detail-row"><span>Term</span><b>{{ $subscription ? $subscription->plan->duration_label : '—' }}</b></div>
                    <div class="detail-row"><span>Covered panels</span><b>{{ $subscription ? 'Full vehicle' : '—' }}</b></div>
                    <div class="protected-note"><span>{{ $subscription ? '✓' : '!' }}</span><p><b>{{ $subscription ? 'You’re fully protected' : 'Protection not active' }}</b><br>{{ $subscription ? 'Your program is active and ready when you need it.' : 'Choose a plan to activate replacement protection.' }}</p></div>
                </article>
            </section>
        </div>
    </main>
    <div class="sidebar-scrim" data-sidebar-toggle></div>
</body>
</html>
