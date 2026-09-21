@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><link rel="icon" type="image/png" href="{{ asset('favicon.png') }}"><title>{{ $title }} · Proflect Admin</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="dashboard-body">
<aside class="sidebar admin-sidebar" id="admin-sidebar">
    <div class="sidebar__brand"><x-brand light/><small>ADMIN PORTAL</small></div>
    <nav class="sidebar__nav" aria-label="Admin navigation">
        <a class="{{ request()->routeIs('admin.dashboard')?'active':'' }}" href="{{ route('admin.dashboard') }}"><span>⌂</span>Dashboard</a>
        <a class="{{ request()->routeIs('admin.customers.*')?'active':'' }}" href="{{ route('admin.customers.index') }}"><span>♙</span>Customers</a>
        <a class="{{ request()->routeIs('admin.vehicles.*')?'active':'' }}" href="{{ route('admin.vehicles.index') }}"><span>▱</span>Manage Vehicles</a>
        <a class="{{ request()->routeIs('admin.payments.*')?'active':'' }}" href="{{ route('admin.payments.index') }}"><span>◇</span>Program Enrolments</a>
        <a class="{{ request()->routeIs('admin.claims.*')?'active':'' }}" href="{{ route('admin.claims.index') }}"><span>▣</span>Claims</a>
        <a class="{{ request()->routeIs('admin.warranty-codes.*')?'active':'' }}" href="{{ route('admin.warranty-codes.index') }}"><span>⌁</span>Warranty Codes</a>
        <a class="{{ request()->routeIs('admin.reports.*')?'active':'' }}" href="{{ route('admin.reports.index') }}"><span>▤</span>Reports</a>
        <a class="{{ request()->routeIs('admin.plans.*')?'active':'' }}" href="{{ route('admin.plans.index') }}"><span>⚙</span>Subscription Plans</a>
    </nav>
    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="sidebar__logout"><span>↪</span>Sign out</button></form>
</aside>
<main class="dashboard-main"><header class="topbar"><button class="menu-button" type="button" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation">☰</button><div><p class="topbar__kicker">PROFLECT ADMIN</p><h1>{{ $title }}</h1></div><div class="navbar-profile" data-profile-menu><button type="button" data-profile-toggle aria-expanded="false"><span class="profile-menu__avatar">{{ strtoupper(substr(auth('admin')->user()->name,0,1)) }}</span><span><b>{{ auth('admin')->user()->name }}</b><small>{{ auth('admin')->user()->email }}</small></span><i>⌄</i></button><div class="profile-dropdown"><a href="{{ route('admin.profile.edit') }}">My profile</a><form method="POST" action="{{ route('admin.logout') }}">@csrf<button>Sign out</button></form></div></div></header><div class="dashboard-content">@if(session('status'))<div class="alert alert--success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert admin-error" role="alert">{{ $errors->first() }}</div>@endif {{ $slot }}<p class="vehicle-data-credit">Vehicle data by <a href="https://vehiclesdb.com">VehiclesDB</a> · <a href="{{ route('vehicle-data.credits') }}">Source credits</a></p></div></main><div class="sidebar-scrim" data-sidebar-toggle></div>
</body></html>
