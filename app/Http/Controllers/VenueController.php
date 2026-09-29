<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(): View
    {
        $destinations = Destination::with('venues')->orderBy('name')->get();

        return view('venues.index', compact('destinations'));
    }

    public function create(): View
    {
        return view('venues.create', [
            'destinations' => Destination::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateVenue($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $venue = Venue::create($data);

        return redirect()->route('venues.index')->with('success', "Venue \"{$venue->name}\" berhasil dibuat.");
    }

    public function edit(Venue $venue): View
    {
        $venue->load('destination');

        return view('venues.edit', [
            'venue' => $venue,
            'destinations' => Destination::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $venue->update($this->validateVenue($request));

        return redirect()->route('venues.index')->with('success', "Venue \"{$venue->name}\" berhasil diperbarui.");
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if ($venue->bookings()->withTrashed()->exists()) {
            return redirect()->route('venues.index')
                ->with('error', "Venue \"{$venue->name}\" tidak bisa dihapus karena masih memiliki data event.");
        }

        $venue->delete();

        return redirect()->route('venues.index')->with('success', "Venue \"{$venue->name}\" berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVenue(Request $request): array
    {
        $data = $request->validate([
            'destination_id' => ['required', 'exists:destinations,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:indoor,outdoor'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'size_sqm' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_seated'] = $request->boolean('is_seated');

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = str($name)->slug();
        $slug = $base;
        $i = 2;

        while (Venue::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
