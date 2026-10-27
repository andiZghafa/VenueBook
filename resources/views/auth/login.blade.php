<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#a7ba68">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0d243c">
    <meta name="color-scheme" content="light">
    <title>Masuk | Venue Booking</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Venue Booking Logo-02.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="login-wrap" style="background-image: url('{{ asset('images/bg.jpeg') }}')">
    <div class="login-area">
        <div class="login-card">
            <div class="brand"><img src="{{ asset('images/Venue Booking Logo-Login.png') }}" alt="Venue Booking" class="brand-logo"></div>

            <h1 class="login-title">Selamat Datang Kembali!</h1>
            <p class="login-sub">Silakan masuk untuk mengelola pemesanan venue di kawasan wisata Borobudur, Prambanan, dan Ratu Boko.</p>

            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autocapitalize="none" autocorrect="off" enterkeyhint="next" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" enterkeyhint="go" required>
                </div>
                <button type="submit" class="btn primary">Masuk</button>
            </form>
        </div>
    </div>

    @include('partials.footer')
</div>
</body>
</html>
