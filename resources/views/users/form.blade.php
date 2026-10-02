@extends('layouts.app')

@section('title', $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna')

@section('content')

    <div class="topbar">
        <div class="title">
            <p style="margin:0 0 4px;font-size:12px;color:var(--muted)"><a href="{{ route('users.index') }}">Pengguna</a> / {{ $user->exists ? $user->name : 'Tambah Pengguna' }}</p>
            <h1>{{ $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h1>
        </div>
    </div>

    <section class="panel form-card">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field full">
                    <label>Nama</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field full">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Peran</label>
                    <select name="role" id="role" onchange="document.getElementById('destination-field').style.display = this.value === 'sales' ? '' : 'none'">
                        <option value="sales" @selected(old('role', $user->role) === 'sales')>Sales</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                        @if (auth()->user()->isSuperAdmin() || $user->isSuperAdmin())
                            <option value="superadmin" @selected(old('role', $user->role) === 'superadmin')>Super Admin</option>
                        @endif
                    </select>
                </div>
                <div class="field" id="destination-field" style="display:{{ in_array(old('role', $user->role), ['admin', 'superadmin']) ? 'none' : '' }}">
                    <label>Destinasi (unit sales)</label>
                    <select name="destination_id">
                        <option value="">— Pilih Destinasi —</option>
                        @foreach ($destinations as $d)
                            <option value="{{ $d->id }}" @selected(old('destination_id', $user->destination_id) == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    @error('destination_id') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : '' }}</label>
                    <input type="password" name="password" {{ $user->exists ? '' : 'required' }}>
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn primary">{{ $user->exists ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</button>
                <a href="{{ route('users.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </section>

@endsection
