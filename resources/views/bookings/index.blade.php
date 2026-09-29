@extends('layouts.app')

@section('title', 'Daftar Event')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Daftar Event</h1>
        </div>
        <div class="actions">
            <a href="{{ route('bookings.create') }}" class="btn primary">+ Tambah Event</a>
        </div>
    </div>

    <section class="panel filters" style="margin-bottom:14px">
        <form method="GET" action="{{ route('bookings.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;padding:16px">
            <div class="field" style="min-width:200px;margin:0">
                <label>Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama event, kode, klien, venue...">
            </div>
            <div class="field" style="min-width:180px;margin:0">
                <label>Destinasi</label>
                <select name="destination_id">
                    <option value="">Semua Destinasi</option>
                    @foreach ($destinations as $d)
                        <option value="{{ $d->id }}" @selected(($filters['destination_id'] ?? null) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:180px;margin:0">
                <label>Venue</label>
                <select name="venue_id">
                    <option value="">Semua Venue</option>
                    @foreach ($venues as $v)
                        <option value="{{ $v->id }}" @selected(($filters['venue_id'] ?? null) == $v->id)>{{ $v->destination->name }} — {{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:150px;margin:0">
                <label>Status</label>
                <select name="status">
                    <option value="">Semua Status</option>
                    @foreach (['hold' => 'Hold', 'tentative' => 'Tentative', 'fix' => 'Fix', 'cancelled' => 'Cancelled', 'maintenance' => 'Maintenance'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:180px;margin:0">
                <label>PIC Sales</label>
                <select name="pic_user_id">
                    <option value="">Semua PIC</option>
                    @foreach ($salesUsers as $u)
                        <option value="{{ $u->id }}" @selected(($filters['pic_user_id'] ?? null) == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn primary">Terapkan</button>
            <a href="{{ route('bookings.index') }}" class="btn">Reset</a>
        </form>
    </section>

    <section class="panel table-card">
        <table class="table">
            <thead>
                <tr><th>Kode</th><th>Tanggal</th><th>Destinasi</th><th>Venue</th><th>Nama Event</th><th>Status</th><th>PIC</th></tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr onclick="window.location='{{ route('bookings.show', $booking) }}'" style="cursor:pointer">
                        <td>{{ $booking->code }}</td>
                        <td>{{ $booking->start_date->translatedFormat('d M Y') }}@if(!$booking->start_date->eq($booking->end_date)) &ndash; {{ $booking->end_date->translatedFormat('d M Y') }}@endif</td>
                        <td>{{ $booking->venue->destination->name }}</td>
                        <td>{{ $booking->venue->name }}</td>
                        <td>{{ $booking->title }}</td>
                        <td><span class="pill {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                        <td>{{ $booking->pic?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:var(--muted)">Belum ada event yang cocok dengan filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        @include('partials.pagination', ['paginator' => $bookings])
    </section>

@endsection
