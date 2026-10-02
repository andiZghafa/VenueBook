<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\EventType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $month = Carbon::create(
            (int) $request->query('y', now()->year),
            (int) $request->query('m', now()->month),
            1
        )->startOfMonth();

        $destinations = Destination::orderBy('name')->get();

        $filters = $request->only(['destination_id', 'venue_id', 'event_type_id', 'status', 'pic_user_id', 'q']);

        $venuesQuery = Venue::with('destination')->orderBy('name');
        if ($filters['destination_id'] ?? null) {
            $venuesQuery->where('destination_id', $filters['destination_id']);
        }
        $venues = $venuesQuery->get();

        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $bookings = Booking::filter($filters)
            ->whereDate('start_date', '<=', $gridEnd)
            ->whereDate('end_date', '>=', $gridStart)
            ->with(['venue.destination', 'eventType', 'client', 'pic'])
            ->get();

        $monthBookings = $bookings->filter(fn (Booking $b) => $b->start_date->lte($month->copy()->endOfMonth())
            && $b->end_date->gte($month));

        $venueCount = $venues->count();
        $daysInMonth = $month->daysInMonth;
        $bookedVenueDays = $monthBookings
            ->reject(fn (Booking $b) => $b->status === 'cancelled')
            ->sum(function (Booking $b) use ($month) {
                $start = $b->start_date->max($month->copy()->startOfMonth());
                $end = $b->end_date->min($month->copy()->endOfMonth());

                return $start->diffInDays($end) + 1;
            });
        $utilization = $venueCount > 0
            ? min(100, round(($bookedVenueDays / ($venueCount * $daysInMonth)) * 100))
            : 0;

        $warningIds = $bookings->filter(fn (Booking $b) => $b->isLoadingOrUnloading())->pluck('id');

        $calendar = [];
        for ($date = $gridStart->copy(); $date->lte($gridEnd); $date->addDay()) {
            $dayEvents = $bookings->filter(fn (Booking $b) => $date->between($b->start_date, $b->end_date))->values();

            $calendar[] = [
                'date' => $date->copy(),
                'inMonth' => $date->month === $month->month,
                'isToday' => $date->isToday(),
                'events' => $dayEvents,
                'hasWarning' => $dayEvents->pluck('id')->intersect($warningIds)->isNotEmpty(),
            ];
        }

        $user = $request->user();
        $eventDetails = $bookings->mapWithKeys(fn (Booking $b) => [$b->id => [
            'id' => $b->id,
            'code' => $b->code,
            'title' => $b->title,
            'status' => $b->status,
            'statusLabel' => ucfirst($b->status),
            'destination' => $b->venue->destination->name,
            'venue' => $b->venue->name,
            'eventType' => $b->eventType->name,
            'client' => $b->client?->name,
            'pic' => $b->pic?->name,
            'guestCount' => $b->guest_count,
            'notes' => $b->notes,
            'dateLabel' => $b->start_date->eq($b->end_date)
                ? $b->start_date->translatedFormat('d M Y')
                : $b->start_date->translatedFormat('d M Y').' – '.$b->end_date->translatedFormat('d M Y'),
            'loadAt' => $b->load_at?->translatedFormat('d M Y H:i'),
            'unloadAt' => $b->unload_at?->translatedFormat('d M Y H:i'),
            'isLoadingOrUnloading' => $warningIds->contains($b->id),
            'showUrl' => route('bookings.show', $b),
            'editUrl' => route('bookings.edit', $b),
            'canManage' => $user->can('update', $b),
        ]]);

        return view('dashboard', [
            'month' => $month,
            'monthOptions' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(2000, $m, 1)->translatedFormat('F')]),
            'yearOptions' => range(now()->year - 2, now()->year + 2),
            'calendar' => $calendar,
            'destinations' => $destinations,
            'venues' => $venues,
            'eventTypes' => EventType::orderBy('name')->get(),
            'salesUsers' => User::where('role', '!=', 'superadmin')->orderBy('name')->get(),
            'filters' => $filters,
            'eventDetails' => $eventDetails,
            'kpis' => [
                'venueCount' => $venueCount,
                'eventCount' => $monthBookings->count(),
                'holdCount' => $monthBookings->where('status', 'hold')->count(),
                'fixCount' => $monthBookings->where('status', 'fix')->count(),
                'utilization' => $utilization,
            ],
            'recentBookings' => $monthBookings->sortBy('start_date')->take(8),
        ]);
    }
}
