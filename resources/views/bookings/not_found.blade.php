@extends('layouts.app')

@section('title', 'Event Tidak Ditemukan')

@section('content')
    <div class="topbar">
        <div class="title">
            <h1>Event Tidak Ditemukan</h1>
            <p>Event yang Anda cari tidak tersedia di sistem.</p>
        </div>
        <div class="actions">
            <a href="{{ route('dashboard') }}" class="btn primary">Kembali ke Dashboard</a>
            <a href="{{ route('bookings.index') }}" class="btn">Lihat Semua Event</a>
        </div>
    </div>

    <section class="panel" style="padding: 48px 24px; text-align: center; max-width: 600px; margin: 30px auto;">
        <div style="margin-bottom: 20px; display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: #f1f5f9; color: var(--muted);">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="14" y2="18"/><line x1="14" y1="14" x2="10" y2="18"/></svg>
        </div>
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
