@extends('layouts.app')

@section('title', 'Tambah Client')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Tambah Client/Organizer</h1>
            <p>Data ini akan tersedia sebagai pilihan saat membuat event.</p>
        </div>
    </div>

    <section class="panel form-card">
        <form method="POST" action="{{ route('clients.store') }}">
            @csrf
            @include('clients.form-fields')
            <div class="form-actions">
                <button type="submit" class="btn primary">Simpan Client</button>
                <a href="{{ route('clients.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </section>

@endsection
