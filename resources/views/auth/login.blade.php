<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c1d38">
    <title>Masuk — Venue Booking</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Venue Booking Logo-02.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="login-wrap" style="background-image: url('{{ asset('images/bg.jpeg') }}')">
    <div class="login-area">
        <div class="login-card">
            <div class="brand"><img src="{{ asset('images/Venue Booking Logo-Login.png') }}" alt="Venue Booking" class="brand-logo"></div>

            <h1 class="login-title">Selamat Datang Kembali!</h1>
            <p class="login-sub">Platform pengelolaan pemesanan venue di kawasan wisata Borobudur, Prambanan, dan Ratu Boko. Silakan masuk untuk melanjutkan.</p>

            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn primary">Masuk</button>
            </form>
        </div>
    </div>

    @include('partials.footer')
</div>
</body>
</html>
