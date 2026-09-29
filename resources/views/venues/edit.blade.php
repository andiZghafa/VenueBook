@extends('layouts.app')

@section('title', 'Edit Venue')

@section('content')

    <div class="topbar">
        <div class="title">
            <p style="margin:0 0 4px;font-size:12px;color:var(--muted)"><a href="{{ route('venues.index') }}">Venue</a> / {{ $venue->name }}</p>
            <h1>Edit Venue</h1>
        </div>
    </div>

    <section class="panel form-card">
        <form method="POST" action="{{ route('venues.update', $venue) }}">
            @csrf
            @method('PUT')
            @include('venues.fields', ['venue' => $venue])
            <div class="form-actions">
                <button type="submit" class="btn primary">Simpan Perubahan</button>
                <a href="{{ route('venues.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </section>

@endsection
