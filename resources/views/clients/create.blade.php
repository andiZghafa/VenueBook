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
            <div class="form-grid">
                <div class="field full">
                    <label>Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                    @error('name') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="field">
                    <label>Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn primary">Simpan Client</button>
                <a href="{{ route('clients.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </section>

@endsection
