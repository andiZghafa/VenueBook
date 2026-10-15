<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Destination;
use App\Models\EventType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['destination_id', 'venue_id', 'event_type_id', 'status', 'pic_user_id', 'q']);

        $bookings = Booking::filter($filters)
            ->with(['venue.destination', 'eventType', 'client', 'pic'])
            ->orderByDesc('start_date')
            ->paginate(12)
            ->withQueryString();

        session(['last_booking_index_url' => $request->fullUrl()]);

        return view('bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'destinations' => Destination::orderBy('name')->get(),
            'venues' => Venue::with('destination')->orderBy('name')->get(),
            'eventTypes' => EventType::orderBy('name')->get(),
            'salesUsers' => User::where('role', '!=', 'superadmin')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Booking::class);

        $seatedOnly = $request->boolean('seated');
        $venues = $this->venuesFor($request->user(), $seatedOnly);

        $booking = new Booking(['status' => 'tentative']);
        if ($seatedOnly && $venues->count() === 1) {
            $booking->venue_id = $venues->first()->id;
        }
        if ($seatedOnly) {
            $booking->event_type_id = EventType::where('name', 'Cultural Performance')->value('id');
        }

        return view('bookings.form', [
            'booking' => $booking,
            'venues' => $venues,
            'venueInfo' => $this->venueInfo($venues),
            'eventTypes' => EventType::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
            'salesUsers' => User::where('role', '!=', 'superadmin')->orderBy('name')->get(),
            'seatedOnly' => $seatedOnly,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Booking::class);

        $data = $this->validateBooking($request);
        $this->authorizeVenueForSales($request->user(), (int) $data['venue_id']);

        $booking = Booking::create([
            ...$data,
            'code' => $this->nextCode(),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('bookings.show', $booking)->with('success', "Event \"{$booking->title}\" berhasil dibuat.");
    }

    public function show(string $booking): View|Response
    {
        $item = Booking::withTrashed()->find($booking);

        if (! $item || $item->trashed()) {
            return response()->view('bookings.not_found', [
                'booking' => $item,
            ], 404);
        }

        $this->authorize('view', $item);

        $item->load(['venue.destination', 'eventType', 'client', 'organizer', 'pic', 'creator', 'bookingSeats']);

        return view('bookings.show', ['booking' => $item]);
    }

    public function edit(Request $request, Booking $booking): View
    {
        $this->authorize('update', $booking);

        $venues = $this->venuesFor($request->user());

        return view('bookings.form', [
            'booking' => $booking,
            'venues' => $venues,
            'venueInfo' => $this->venueInfo($venues),
            'eventTypes' => EventType::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
            'salesUsers' => User::where('role', '!=', 'superadmin')->orderBy('name')->get(),
            'seatedOnly' => false,
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $data = $this->validateBooking($request, $booking);
        $this->authorizeVenueForSales($request->user(), (int) $data['venue_id']);

        $booking->update($data);

        return redirect()->route('bookings.show', $booking)->with('success', "Event \"{$booking->title}\" berhasil diperbarui.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        $booking->delete();

        return redirect(session('last_booking_index_url', route('bookings.index')))
            ->with('success', "Event \"{$booking->title}\" berhasil dihapus.");
    }

    /**
     * @return Collection<int, Venue>
     */
    private function venuesFor(User $user, bool $seatedOnly = false)
    {
        $query = Venue::with('destination')->orderBy('name');

        if (! $user->isAdmin()) {
            $query->where('destination_id', $user->destination_id);
        }

        if ($seatedOnly) {
            $query->where('is_seated', true);
        }

        return $query->get()->sortBy(fn (Venue $v) => $v->destination->name.$v->name)->values();
    }

    /**
     * @param  Collection<int, Venue>  $venues
     * @return array<int, array<string, mixed>>
     */
    private function venueInfo(Collection $venues): array
    {
        return $venues->mapWithKeys(fn (Venue $v) => [$v->id => [
            'type' => ucfirst($v->type),
            'capacity' => $v->capacity,
            'sizeSqm' => $v->size_sqm,
            'description' => $v->description,
        ]])->all();
    }

    private function authorizeVenueForSales(User $user, int $venueId): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $belongsToOwnDestination = Venue::where('id', $venueId)
            ->where('destination_id', $user->destination_id)
            ->exists();

        abort_unless($belongsToOwnDestination, 403, 'Anda hanya dapat mengelola event untuk destinasi Anda sendiri.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBooking(Request $request, ?Booking $booking = null): array
    {
        $clientName = $request->input('client_name');
        if (! empty($clientName)) {
            $client = Client::firstOrCreate(['name' => $clientName]);
            $request->merge(['client_id' => $client->id]);
        } else {
            $request->merge(['client_id' => null]);
        }

        if ($request->boolean('is_separate_organizer')) {
            $organizerName = $request->input('organizer_name');
            if (! empty($organizerName)) {
                $organizer = Client::firstOrCreate(['name' => $organizerName]);
                $request->merge(['organizer_id' => $organizer->id]);
            } else {
                $request->merge(['organizer_id' => null]);
            }
        } else {
            $request->merge(['organizer_id' => $request->input('client_id')]);
        }

        return $request->validate([
            'venue_id' => ['required', 'exists:venues,id'],
            'event_type_id' => ['required', 'exists:event_types,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'organizer_id' => ['nullable', 'exists:clients,id'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'load_at' => ['nullable', 'date'],
            'unload_at' => ['nullable', 'date'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:hold,tentative,fix,cancelled,maintenance'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function nextCode(): string
    {
        $year = now()->year;
        $count = Booking::whereYear('created_at', $year)->withTrashed()->count() + 1;

        return sprintf('EV-%s-%04d', $year, $count);
    }
}
