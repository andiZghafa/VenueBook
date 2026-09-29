@extends('layouts.app')

@section('title', 'Klien / Organizer')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Klien / Organizer</h1>
        </div>
        <div class="actions">
            <a href="{{ route('clients.create') }}" class="btn primary">+ Tambah Klien</a>
        </div>
    </div>

    <section class="panel table-card">
        <table class="table">
            <thead>
                <tr><th>Nama</th><th>Kontak</th><th>Jumlah Event</th></tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td><b>{{ $client->name }}</b></td>
                        <td>{{ $client->email ?: '—' }}@if($client->phone) <div style="color:var(--muted);font-size:11px">{{ $client->phone }}</div>@endif</td>
                        <td>{{ $client->bookings_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="color:var(--muted)">Belum ada klien terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>

        @include('partials.pagination', ['paginator' => $clients])
    </section>

@endsection
