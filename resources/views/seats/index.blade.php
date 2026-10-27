@extends('layouts.app')

@section('title', 'Booking Kursi')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Booking Kursi</h1>
        </div>
        <div class="actions">
            @can('create', \App\Models\Booking::class)
                <a href="{{ route('bookings.create', ['seated' => 1]) }}" class="btn primary">+ Tambah Jadwal</a>
            @endcan
        </div>
    </div>

    <section class="panel table-card">
        <table class="table">
            <thead>
                <tr><th>Tanggal</th><th>Destinasi</th><th>Venue</th><th>Nama Pertunjukan</th><th>Status</th><th>Kursi</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    @php
                        $total = $booking->venue->seats->count();
                        $booked = $booking->bookingSeats->where('status', 'booked')->count();
                        $blocked = $booking->bookingSeats->where('status', 'blocked')->count();
                        $available = $total - $booked - $blocked;
                    @endphp
                    <tr tabindex="0" role="link" aria-label="Kelola kursi {{ $booking->title }}" onclick="window.location='{{ route('bookings.seats.edit', $booking) }}'" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location='{{ route('bookings.seats.edit', $booking) }}';}" style="cursor:pointer">
                        <td>{{ $booking->start_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $booking->venue->destination->name }}</td>
                        <td>{{ $booking->venue->name }}</td>
                        <td>{{ $booking->title }}</td>
                        <td><span class="pill {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                        <td>{{ $available }} tersedia / {{ $total }}</td>
                        <td><a href="{{ route('bookings.seats.edit', $booking) }}" class="btn" onclick="event.stopPropagation()">Kelola Kursi</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:var(--muted); padding:20px 10px;">Belum ada jadwal pertunjukan di venue berkursi. Klik <b>+ Tambah Jadwal</b> di kanan atas untuk membuat jadwal baru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

@endsection
