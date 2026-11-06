<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Destination;
use App\Models\EventType;
use App\Models\User;
use App\Models\Venue;
use App\Services\BookingExcelExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function exportForm(Request $request, BookingExcelExportService $exporter): View
    {
        $user = $request->user();
        [$startDate, $endDate] = $this->resolveExportDateRange($request);
        $sort = $this->resolveExportSort($request);

        $filters = $request->only(['destination_id', 'venue_id', 'event_type_id', 'status', 'pic_user_id', 'q']);
        if (! $user->isAdmin()) {
            $filters['destination_id'] = $user->destination_id;
        }

        $query = $this->buildExportQuery($request);
        $allMatching = $query->get();

        $availableColumns = $exporter->availableColumns();
        $rawColumns = $request->input('columns');
        if (is_string($rawColumns)) {
            $rawColumns = array_filter(explode(',', $rawColumns));
        }

        $selectedColumns = is_array($rawColumns) && $rawColumns !== []
            ? $exporter->resolveColumnKeys($rawColumns)
            : array_keys($availableColumns);

        $previewRows = $allMatching->take(10)->map(function (Booking $booking) use ($exporter) {
            return [
                'id' => $booking->id,
                'status_key' => $booking->status,
                'cells' => $exporter->mapKeyedRow($booking),
            ];
        })->values();

        $venuesQuery = Venue::with('destination')->orderBy('name');
        if (! $user->isAdmin()) {
            $venuesQuery->where('destination_id', $user->destination_id);
        }

        return view('bookings.export', [
            'filters' => $filters,
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'availableColumns' => $availableColumns,
            'defaultFormColumns' => $exporter->defaultColumnKeys(),
            'selectedColumns' => $selectedColumns,
            'totalMatching' => $allMatching->count(),
            'previewRows' => $previewRows,
            'destinations' => Destination::orderBy('name')->get(),
            'venues' => $venuesQuery->get(),
            'salesUsers' => User::where('role', '!=', 'superadmin')->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request, BookingExcelExportService $exporter): BinaryFileResponse
    {
        [$startDate, $endDate] = $this->resolveExportDateRange($request);
        $bookings = $this->buildExportQuery($request)->get();

        $rawColumns = $request->input('columns');
        if (is_string($rawColumns)) {
            $rawColumns = array_filter(explode(',', $rawColumns));
        }
        $columns = is_array($rawColumns) && $rawColumns !== []
            ? $exporter->resolveColumnKeys($rawColumns)
            : null;

        if ($startDate && $endDate) {
            $filename = sprintf('daftar-event-%s-sd-%s.xlsx', $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        } elseif ($startDate) {
            $filename = sprintf('daftar-event-mulai-%s.xlsx', $startDate->format('Y-m-d'));
        } elseif ($endDate) {
            $filename = sprintf('daftar-event-sampai-%s.xlsx', $endDate->format('Y-m-d'));
        } else {
            $filename = 'daftar-event-'.now()->format('Y-m-d').'.xlsx';
        }

        return $exporter->download($bookings, $filename, $columns);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveExportDateRange(Request $request): array
    {
        $startDate = null;
        $endDate = null;

        if ($request->filled('start_date')) {
            try {
                $startDate = Carbon::parse((string) $request->query('start_date'))->startOfDay();
            } catch (\Throwable) {
                $startDate = null;
            }
        }

        if ($request->filled('end_date')) {
            try {
                $endDate = Carbon::parse((string) $request->query('end_date'))->endOfDay();
            } catch (\Throwable) {
                $endDate = null;
            }
        }

        if (! $startDate && ! $endDate && $request->filled('y') && $request->filled('m')) {
            try {
                $month = Carbon::create((int) $request->query('y'), (int) $request->query('m'), 1)->startOfMonth();
                $startDate = $month->copy()->startOfDay();
                $endDate = $month->copy()->endOfMonth()->endOfDay();
            } catch (\Throwable) {
                // Ignore invalid month/year parameters
            }
        }

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function resolveExportSort(Request $request): string
    {
        $allowed = ['start_date_desc', 'start_date_asc', 'price_desc', 'title_asc'];
        $sort = (string) $request->query('sort', 'start_date_desc');

        return in_array($sort, $allowed, true) ? $sort : 'start_date_desc';
    }

    /**
     * @return Builder<Booking>
     */
    private function buildExportQuery(Request $request): Builder
    {
        $filters = $request->only(['destination_id', 'venue_id', 'event_type_id', 'status', 'pic_user_id', 'q']);
        $user = $request->user();

        if (! $user->isAdmin()) {
            $destinationsAllowed = $user->destinationIds();
            $filters['destination_id'] = empty($destinationsAllowed) ? -1 : $user->destination_id;
        }

        [$startDate, $endDate] = $this->resolveExportDateRange($request);
        $sort = $this->resolveExportSort($request);

        $query = Booking::filter($filters)
            ->with(['venue.destination', 'eventType', 'client', 'organizer', 'pic']);

        if ($startDate && $endDate) {
            $query->whereDate('start_date', '<=', $endDate->toDateString())
                ->whereDate('end_date', '>=', $startDate->toDateString());
        } elseif ($startDate) {
            $query->whereDate('end_date', '>=', $startDate->toDateString());
        } elseif ($endDate) {
            $query->whereDate('start_date', '<=', $endDate->toDateString());
        }

        return match ($sort) {
            'start_date_asc' => $query->orderBy('start_date')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('start_date'),
            'title_asc' => $query->orderBy('title')->orderByDesc('start_date'),
            default => $query->orderByDesc('start_date')->orderByDesc('id'),
        };
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
