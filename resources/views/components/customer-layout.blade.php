@props(['title' => 'My Warranty'])
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} · Proflect</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="dashboard-body customer-nav-body">
@php($customerNotifications = auth()->user()->notifications()->latest()->limit(30)->get())
<header class="customer-navbar">
    <a href="{{ route('dashboard') }}" class="customer-navbar__brand"><x-brand light/></a>
    <button class="customer-nav-toggle" type="button" data-customer-nav-toggle aria-controls="customer-navigation" aria-expanded="false" aria-label="Open navigation">☰</button>
    <nav id="customer-navigation" class="customer-navbar__nav" aria-label="Customer navigation">
        <a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">Overview</a>
        <a href="{{ route('dashboard') }}#warranty">My warranty</a>
        <a class="{{ request()->routeIs('claims.*')?'active':'' }}" href="{{ route('claims.index') }}">Claims</a>
        <a class="{{ request()->routeIs('subscription.*')?'active':'' }}" href="{{ route('subscription.index') }}">Protection plans</a>
        <a class="{{ request()->routeIs('documents.*')?'active':'' }}" href="{{ route('documents.index') }}">Documents</a>
    </nav>
    <div class="notification-menu" data-notification-menu data-read-url="{{ route('notifications.read-all') }}">
        <button class="notification-bell" type="button" data-notification-toggle aria-expanded="false" aria-label="Open notifications"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>@if(auth()->user()->unreadNotifications()->exists())<span class="notification-badge" data-notification-badge>{{ min(99, auth()->user()->unreadNotifications()->count()) }}</span>@endif</button>
        <section class="notification-dropdown"><header><div><b>Notifications</b><small>{{ auth()->user()->unreadNotifications()->count() }} unread</small></div></header><div class="notification-list">@forelse($customerNotifications as $notification)<form method="POST" action="{{ route('notifications.read',$notification->id) }}" class="notification-item {{ $notification->read_at ? '' : 'unread' }}">@csrf<button><i></i><span><b>{{ $notification->data['title'] ?? 'Proflect update' }}</b><small>{{ $notification->data['message'] ?? '' }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span></button></form>@empty<div class="notification-empty">You have no notifications yet.</div>@endforelse</div></section>
    </div>
    <div class="navbar-profile" data-profile-menu>
        <button type="button" data-profile-toggle aria-expanded="false"><span class="profile-menu__avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></span><i>⌄</i></button>
        <div class="profile-dropdown"><a href="{{ route('profile.edit') }}">My profile</a><form method="POST" action="{{ route('logout') }}">@csrf<button>Sign out</button></form></div>
    </div>
</header>
<main class="customer-main"><header class="customer-page-head"><div><p class="topbar__kicker">CUSTOMER PORTAL</p><h1>{{ $title }}</h1></div></header><div class="dashboard-content">@if(session('status'))<div class="alert alert--success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert admin-error" role="alert">{{ $errors->first() }}</div>@endif {{ $slot }}</div></main>
</body></html>
