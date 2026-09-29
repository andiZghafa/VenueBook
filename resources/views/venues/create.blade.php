@extends('layouts.app')

@section('title', 'Tambah Venue')

@section('content')

    <div class="topbar">
        <div class="title">
            <p style="margin:0 0 4px;font-size:12px;color:var(--muted)"><a href="{{ route('venues.index') }}">Venue</a> / Tambah Venue</p>
            <h1>Tambah Venue</h1>
        </div>
    </div>

    <section class="panel form-card">
        <form method="POST" action="{{ route('venues.store') }}">
            @csrf
            @include('venues.fields', ['venue' => new \App\Models\Venue])
            <div class="form-actions">
                <button type="submit" class="btn primary">Simpan Venue</button>
                <a href="{{ route('venues.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </section>

@endsection
