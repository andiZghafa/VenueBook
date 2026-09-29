@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Pengguna</h1>
        </div>
        <div class="actions">
            <a href="{{ route('users.create') }}" class="btn primary">+ Tambah Pengguna</a>
        </div>
    </div>

    <section class="panel table-card">
        <table class="table">
            <thead>
                <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Destinasi</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><b>{{ $user->name }}</b></td>
                        <td>{{ $user->email }}</td>
                        <td><span class="pill {{ $user->role === 'admin' ? 'fix' : 'tentative' }}">{{ ucfirst($user->role) }}</span></td>
                        <td>{{ $user->destination?->name ?? '—' }}</td>
                        <td class="table-actions">
                            <a href="{{ route('users.edit', $user) }}" class="btn">Edit</a>
                            @unless (auth()->user()->is($user))
                                <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn danger">Hapus</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

@endsection
