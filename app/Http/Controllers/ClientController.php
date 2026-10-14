<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index', [
            'clients' => Client::withCount('bookings')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama wajib diisi',
        ]);

        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Client berhasil ditambahkan.');
    }

    public function show(Client $client): JsonResponse
    {
        $client->loadCount('bookings');

        return response()->json([
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama wajib diisi',
        ]);

        $client->update($data);
        $client->loadCount('bookings');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Client berhasil diperbarui.',
                'client' => $client,
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Client berhasil diperbarui.');
    }
}
