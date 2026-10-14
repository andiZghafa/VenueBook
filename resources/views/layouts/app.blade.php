<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c1d38">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Venue Booking</title>
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
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand" aria-label="Venue Booking Dashboard">
            <img src="{{ asset('images/Venue Booking Logo-03.png') }}" alt="Venue Booking" class="brand-logo logo-full">
            <img src="{{ asset('images/Venue Booking Logo-02.png') }}" alt="Venue Booking" class="brand-logo logo-icon">
        </a>
        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard"><span class="nav-icon">▦</span><span class="nav-label">Dashboard</span></a>
            <a href="{{ route('bookings.index') }}" class="{{ request()->routeIs('bookings.*') && ! request()->routeIs('bookings.seats.*') ? 'active' : '' }}" title="Event"><span class="nav-icon">◫</span><span class="nav-label">Event</span></a>
            <a href="{{ route('seats.index') }}" class="{{ request()->routeIs('seats.*') || request()->routeIs('bookings.seats.*') ? 'active' : '' }}" title="Booking Kursi"><span class="nav-icon">🎟</span><span class="nav-label">Booking Kursi</span></a>
            <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}" title="Client/Organizer"><span class="nav-icon">👥</span><span class="nav-label">Client/Organizer</span></a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('venues.index') }}" class="{{ request()->routeIs('venues.*') ? 'active' : '' }}" title="Venue"><span class="nav-icon">⌖</span><span class="nav-label">Venue</span></a>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}" title="Pengguna"><span class="nav-icon">⚙</span><span class="nav-label">Pengguna</span></a>
            @endif
        </nav>

        <div class="sidebar-foot">
            <div class="user-card" title="{{ auth()->user()->name }} — {{ ucfirst(auth()->user()->role) }}">
                <span class="user-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.418 3.582-7 8-7s8 2.582 8 7a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/></svg>
                </span>
                <div class="user-info nav-label">
                    <b>{{ auth()->user()->name }}</b>
                    <span>{{ auth()->user()->isSuperAdmin() ? 'Super Admin' : (auth()->user()->isAdmin() ? 'Admin' : 'Sales — '.auth()->user()->destination?->name) }}</span>
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

    <button type="button" class="sidebar-toggle" onclick="toggleSidebar()" title="Ciutkan/lebarkan menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 7-5 5 5 5"/><path d="m15 7 5 5-5 5"/></svg>
    </button>

    <div class="main-wrapper">
        <main class="main">
            @if (session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
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
</script>
</body>
</html>
