<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0d243c">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Venue Booking</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Venue Booking Logo-02.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<script>
    if (localStorage.getItem('vt_sidebar_collapsed') === '1') {
        document.documentElement.classList.add('sidebar-collapsed');
    }
</script>
<div class="app">
    <div class="sidebar-backdrop" onclick="closeMobileNav()" aria-hidden="true"></div>
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-head">
            <a href="{{ route('dashboard') }}" class="brand" aria-label="Venue Booking Dashboard">
                <img src="{{ asset('images/Venue Booking Logo-03.png') }}" alt="Venue Booking" class="brand-logo logo-full" width="210" height="56">
                <img src="{{ asset('images/Venue Booking Logo-02.png') }}" alt="Venue Booking" class="brand-logo logo-icon" width="42" height="42">
            </a>
            <button type="button" class="mobile-sidebar-close" onclick="closeMobileNav()" aria-label="Tutup menu navigasi" title="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                <span class="nav-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                </span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="{{ route('bookings.index') }}" class="{{ request()->routeIs('bookings.*') && ! request()->routeIs('bookings.seats.*') ? 'active' : '' }}" title="Event">
                <span class="nav-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
                <span class="nav-label">Event</span>
            </a>
            <a href="{{ route('seats.index') }}" class="{{ request()->routeIs('seats.*') || request()->routeIs('bookings.seats.*') ? 'active' : '' }}" title="Booking Kursi">
                <span class="nav-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v2a2 2 0 0 0 0 4v2a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-2a2 2 0 0 0 0-4V9z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                </span>
                <span class="nav-label">Booking Kursi</span>
            </a>
            <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}" title="Client/Organizer">
                <span class="nav-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <span class="nav-label">Client/Organizer</span>
            </a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('venues.index') }}" class="{{ request()->routeIs('venues.*') ? 'active' : '' }}" title="Venue">
                    <span class="nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <span class="nav-label">Venue</span>
                </a>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}" title="Pengguna">
                    <span class="nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </span>
                    <span class="nav-label">Pengguna</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-foot">
            <div class="user-card" title="{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})">
                <span class="user-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.418 3.582-7 8-7s8 2.582 8 7a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/></svg>
                </span>
                <div class="user-info nav-label">
                    <b>{{ auth()->user()->name }}</b>
                    <span>{{ auth()->user()->isSuperAdmin() ? 'Super Admin' : (auth()->user()->isAdmin() ? 'Admin' : 'Sales · '.auth()->user()->destination?->name) }}</span>
                    <span class="badge-role">{{ auth()->user()->role }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn" title="Keluar">
                    <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span class="nav-label">Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <button type="button" class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Ciutkan atau lebarkan menu navigasi" title="Ciutkan/lebarkan menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 7-5 5 5 5"/><path d="m15 7 5 5-5 5"/></svg>
    </button>

    <div class="main-wrapper">
        @include('partials.header')

        <main class="main">
            @if (session('success'))
                <div class="alert success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert error" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error" role="alert">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>

        @include('partials.footer')
    </div>
</div>
<script>
    function toggleSidebar() {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        localStorage.setItem('vt_sidebar_collapsed', collapsed ? '1' : '0');
    }
    function toggleMobileNav() {
        document.documentElement.classList.toggle('mobile-nav-open');
    }
    function closeMobileNav() {
        document.documentElement.classList.remove('mobile-nav-open');
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.documentElement.classList.contains('mobile-nav-open')) {
            closeMobileNav();
        }
    });
</script>
</body>
</html>
