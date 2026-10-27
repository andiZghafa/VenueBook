@props(['code', 'title', 'message'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#a7ba68">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0d243c">
    <meta name="color-scheme" content="light">
    <title>{{ $code }} · {{ $title }} | Venue Booking</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Venue Booking Logo-02.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="login-wrap" style="background-image: url('{{ asset('images/bg.jpeg') }}')">
    <div class="login-area">
        <div class="login-card" style="text-align:center">
            <div class="brand" style="justify-content:center"><img src="{{ asset('images/Venue Booking Logo-03.png') }}" alt="Venue Booking" class="brand-logo"></div>
            <div style="font-size:13px;font-weight:800;letter-spacing:.05em;color:var(--muted);margin-top:10px">ERROR {{ $code }}</div>
            <h1 style="margin:6px 0 10px;color:var(--text);font-size:22px">{{ $title }}</h1>
            <p class="login-sub">{{ $message }}</p>
            <a href="{{ url('/') }}" class="btn primary" style="width:100%;justify-content:center;margin-top:6px">Kembali ke Dashboard</a>
        </div>
    </div>
    @include('partials.footer')
</div>
</body>
</html>
