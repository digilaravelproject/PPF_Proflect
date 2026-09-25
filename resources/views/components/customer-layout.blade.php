@props(['title' => 'My Warranty'])
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><link rel="icon" type="image/png" href="{{ asset('favicon.png') }}"><title>{{ $title }} · Proflect</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="dashboard-body customer-nav-body">
@php
    $customerNotifications = auth()->user()->notifications()->latest()->limit(30)->get();
@endphp
<header class="customer-navbar">
    <x-brand light />
    <button class="customer-nav-toggle" type="button" data-customer-nav-toggle aria-controls="customer-navigation" aria-expanded="false" aria-label="Open navigation">☰</button>
    <nav id="customer-navigation" class="customer-navbar__nav" aria-label="Customer navigation">
        <a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="{{ request()->routeIs('vehicles.*')?'active':'' }}" href="{{ route('vehicles.index') }}">My Vehicles</a>
        <a class="{{ request()->routeIs('warranty.show')?'active':'' }}" href="{{ route('warranty.show') }}">My Warranty</a>
        <a class="{{ request()->routeIs('claims.create')?'active':'' }}" href="{{ route('claims.create') }}">Make a Claim</a>
        <a href="mailto:contact@proflect.com.au">Support</a>
        <a class="{{ request()->routeIs('profile.*')?'active':'' }}" href="{{ route('profile.edit') }}">Profile</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
    </nav>
    <div class="notification-menu" data-notification-menu data-read-url="{{ route('notifications.read-all') }}">
        <button class="notification-bell" type="button" data-notification-toggle aria-expanded="false" aria-label="Open notifications"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>@if(auth()->user()->unreadNotifications()->exists())<span class="notification-badge" data-notification-badge>{{ min(99, auth()->user()->unreadNotifications()->count()) }}</span>@endif</button>
        <section class="notification-dropdown"><header><div><b>Notifications</b><small>{{ auth()->user()->unreadNotifications()->count() }} unread</small></div></header><div class="notification-list">@forelse($customerNotifications as $notification)<form method="POST" action="{{ route('notifications.read',$notification->id) }}" class="notification-item {{ $notification->read_at ? '' : 'unread' }}">@csrf<button><i></i><span><b>{{ $notification->data['title'] ?? 'Proflect update' }}</b><small>{{ $notification->data['message'] ?? '' }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span></button></form>@empty<div class="notification-empty">You have no notifications yet.</div>@endforelse</div></section>
    </div>
    <div class="navbar-profile" data-profile-menu><button type="button" data-profile-toggle aria-expanded="false"><span class="profile-menu__avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></span><i>⌄</i></button><div class="profile-dropdown"><a href="{{ route('profile.edit') }}">My profile</a><form method="POST" action="{{ route('logout') }}">@csrf<button>Sign out</button></form></div></div>
</header>
<main class="customer-main customer-main--navbar">
    <header class="customer-page-head"><div><p class="topbar__kicker">CUSTOMER PORTAL</p><h1>{{ $title }}</h1></div></header>
    <div class="dashboard-content">@if(session('status'))<div class="alert alert--success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert admin-error" role="alert">{{ $errors->first() }}</div>@endif {{ $slot }}<p class="vehicle-data-credit">Vehicle data by <a href="https://vehiclesdb.com">VehiclesDB</a> · <a href="{{ route('vehicle-data.credits') }}">Source credits</a></p></div>
</main>
</body></html>
