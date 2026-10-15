@extends('layouts.app')

@section('title', 'Event Tidak Ditemukan')

@section('content')
    <div class="topbar">
        <div class="title">
            <h1>Event Tidak Ditemukan</h1>
            <p>Event yang Anda cari tidak tersedia di sistem.</p>
        </div>
        <div class="actions">
            <a href="{{ route('dashboard') }}" class="btn primary">← Kembali ke Dashboard</a>
            <a href="{{ route('bookings.index') }}" class="btn">Lihat Semua Event</a>
        </div>
    </div>

    <section class="panel" style="padding: 48px 24px; text-align: center; max-width: 600px; margin: 30px auto;">
        <div style="font-size: 56px; margin-bottom: 16px;">🗓️</div>
        <h2 style="font-size: 22px; font-weight: 700; margin: 0 0 10px; color: var(--text);">Event Tidak Ditemukan</h2>
        <p style="font-size: 14px; color: var(--muted); line-height: 1.6; margin: 0 0 24px;">
            Event ini mungkin telah dibatalkan, dihapus, atau tautan yang Anda ikuti sudah tidak berlaku.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="{{ route('dashboard') }}" class="btn primary">Kembali ke Dashboard</a>
            <a href="{{ route('bookings.index') }}" class="btn">Daftar Event</a>
        </div>
    </section>
@endsection
