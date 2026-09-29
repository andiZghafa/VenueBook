@extends('layouts.app')

@section('title', $booking->code)

@section('content')

    <div class="topbar">
        <div class="title">
            <p style="margin:0 0 4px;font-size:12px;color:var(--muted)"><a href="{{ route('bookings.index') }}">Daftar Event</a> / {{ $booking->code }}</p>
            <h1>{{ $booking->title }}</h1>
        </div>
        <div class="actions">
            @if ($booking->venue->is_seated)
                <a href="{{ route('bookings.seats.edit', $booking) }}" class="btn">Kelola Kursi</a>
            @endif
            @can('update', $booking)
                <a href="{{ route('bookings.edit', $booking) }}" class="btn primary">Edit Event</a>
            @endcan
            @can('delete', $booking)
                <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Hapus event ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn danger">Hapus</button>
                </form>
            @endcan
        </div>
    </div>

    <section class="panel" style="padding:20px;max-width:640px">
        <span class="badge-destination">{{ $booking->venue->destination->name }}</span>
        <span class="pill {{ $booking->status }}" style="display:inline-block;margin-left:8px">{{ ucfirst($booking->status) }}</span>

        @if ($booking->isLoadingOrUnloading())
            <div class="alert error" style="margin-top:12px">⚠️ Hey, event ini masih dalam proses loading atau unloading.</div>
        @endif

        <div class="kv">
            <div>Kode Event</div><div><b>{{ $booking->code }}</b></div>
            <div>Venue</div><div>{{ $booking->venue->name }}</div>
            <div>Tanggal</div><div>{{ $booking->start_date->translatedFormat('d M Y') }} @if(!$booking->start_date->eq($booking->end_date)) &ndash; {{ $booking->end_date->translatedFormat('d M Y') }} @endif</div>
            <div>Loading</div><div>{{ $booking->load_at ? $booking->load_at->translatedFormat('d M Y H:i') : '—' }}</div>
            <div>Unloading</div><div>{{ $booking->unload_at ? $booking->unload_at->translatedFormat('d M Y H:i') : '—' }}</div>
            <div>Jenis Event</div><div>{{ $booking->eventType->name }}</div>
            <div>Klien / Organizer</div><div>{{ $booking->client?->name ?? '—' }}</div>
            <div>PIC Sales</div><div>{{ $booking->pic?->name ?? '—' }}</div>
            <div>Jumlah Tamu</div><div>{{ $booking->guest_count ? number_format($booking->guest_count).' orang' : '—' }}</div>
            <div>Dibuat oleh</div><div>{{ $booking->creator->name }} &middot; {{ $booking->created_at->translatedFormat('d M Y H:i') }}</div>
            <div>Catatan</div><div>{{ $booking->notes ?: '—' }}</div>
            @if ($booking->venue->is_seated)
                @php
                    $totalSeats = $booking->venue->seats()->count();
                    $booked = $booking->bookingSeats->where('status', 'booked')->count();
                    $blocked = $booking->bookingSeats->where('status', 'blocked')->count();
                @endphp
                <div>Kursi</div><div>{{ $booked }} booked &middot; {{ $blocked }} blocked &middot; {{ $totalSeats - $booked - $blocked }} tersedia dari {{ $totalSeats }}</div>
            @endif
        </div>

        @cannot('update', $booking)
            <p style="font-size:12px;color:var(--muted);margin-top:16px">Event ini milik destinasi lain — Anda hanya dapat melihatnya, bukan mengubahnya.</p>
        @endcannot
    </section>

@endsection
