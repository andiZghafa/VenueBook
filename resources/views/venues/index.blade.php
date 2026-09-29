@extends('layouts.app')

@section('title', 'Venue')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Venue</h1>
        </div>
        <div class="actions">
            <a href="{{ route('venues.create') }}" class="btn primary">+ Tambah Venue</a>
        </div>
    </div>

    @foreach ($destinations as $destination)
        <section class="panel table-card" style="margin-bottom:14px">
            <h3>{{ $destination->name }}</h3>
            <table class="table">
                <thead>
                    <tr><th>Nama Venue</th><th>Tipe</th><th>Kapasitas</th><th>Luas (m²)</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($destination->venues as $venue)
                        <tr>
                            <td><b>{{ $venue->name }}</b></td>
                            <td>{{ ucfirst($venue->type) }}</td>
                            <td>{{ $venue->capacity ? number_format($venue->capacity).' orang' : '—' }}</td>
                            <td>{{ $venue->size_sqm ? number_format($venue->size_sqm).' m²' : 'Belum ada data' }}</td>
                            <td>{{ $venue->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="table-actions">
                                <a href="{{ route('venues.edit', $venue) }}" class="btn">Edit</a>
                                <form method="POST" action="{{ route('venues.destroy', $venue) }}" onsubmit="return confirm('Hapus venue ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="color:var(--muted)">Belum ada venue di destinasi ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endforeach

@endsection
