<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\EventType;
use App\Models\User;
use App\Models\Venue;
use App\Services\UpcomingEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected UpcomingEventService $upcomingEventService
    ) {}

    public function index(Request $request): View
    {
        $month = Carbon::create(
            (int) $request->query('y', now()->year),
            (int) $request->query('m', now()->month),
            1
        )->startOfMonth();

        $destinations = Destination::orderBy('name')->get();

        $filters = $request->only(['destination_id', 'venue_id', 'event_type_id', 'status', 'pic_user_id', 'q']);
        $user = $request->user();

        // Enforce destination scoping: sales users must only ever see their own destination(s)
        if (! $user->isAdmin()) {
            $destinationsAllowed = $user->destinationIds();
            if (empty($destinationsAllowed)) {
                $filters['destination_id'] = -1; // No destination assigned -> no results
            } else {
                $filters['destination_id'] = $user->destination_id;
            }
        }

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
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        // Scope venues for utilisation analysis: respect specific venue filter or all accessible venues
        $targetVenues = ($filters['venue_id'] ?? null)
            ? $venues->where('id', $filters['venue_id'])
            : $venues;

        $targetVenueIds = $targetVenues->pluck('id');

        // Query active month bookings for target venues to guarantee complete status accounting (including loading & unloading windows)
        $utilizationBookings = Booking::whereIn('venue_id', $targetVenueIds)
            ->where(function ($q) use ($monthEnd) {
                $q->whereDate('start_date', '<=', $monthEnd)
                    ->orWhereDate('load_at', '<=', $monthEnd);
            })
            ->where(function ($q) use ($monthStart) {
                $q->whereDate('end_date', '>=', $monthStart)
                    ->orWhereDate('unload_at', '>=', $monthStart);
            })
            ->where('status', '!=', 'cancelled')
            ->get();

        // Calculate per-venue utilisation and days breakdown (Ready, Terpakai [incl. Loading & Unloading], Maintenance)
        $venueStats = $targetVenues->map(function (Venue $venue) use ($utilizationBookings, $monthStart, $monthEnd, $daysInMonth) {
            $vBookings = $utilizationBookings
                ->where('venue_id', $venue->id)
                ->filter(fn (Booking $b) => $b->occupiedStartDate()->lte($monthEnd) && $b->occupiedEndDate()->gte($monthStart));

            $terpakaiDaysSet = [];
            $maintenanceDaysSet = [];
            $fixDaysSet = [];
            $eventDaysSet = [];
            $loadingUnloadingDaysSet = [];

            foreach ($vBookings as $b) {
                $bStart = $b->occupiedStartDate()->max($monthStart);
                $bEnd = $b->occupiedEndDate()->min($monthEnd);
                $evStart = $b->start_date->copy()->startOfDay();
                $evEnd = $b->end_date->copy()->startOfDay();

                for ($d = $bStart->copy(); $d->lte($bEnd); $d->addDay()) {
                    $dayKey = $d->day;
                    if ($b->status === 'maintenance') {
                        $maintenanceDaysSet[$dayKey] = true;
                    } elseif (in_array($b->status, ['fix', 'tentative', 'hold'])) {
                        $terpakaiDaysSet[$dayKey] = true;
                        if ($d->betweenIncluded($evStart, $evEnd)) {
                            $eventDaysSet[$dayKey] = true;
                        } else {
                            $loadingUnloadingDaysSet[$dayKey] = true;
                        }
                        if ($b->status === 'fix') {
                            $fixDaysSet[$dayKey] = true;
                        }
                    }
                }
            }

            // In case of overlap, active event booking takes precedence over maintenance
            $terpakaiDays = count($terpakaiDaysSet);
            $fixDays = count($fixDaysSet);
            $pipelineDays = $terpakaiDays - $fixDays;
            $loadingUnloadingDays = count(array_diff_key($loadingUnloadingDaysSet, $eventDaysSet));
            $maintenanceDays = count(array_diff_key($maintenanceDaysSet, $terpakaiDaysSet));
            $readyDays = max(0, $daysInMonth - $terpakaiDays - $maintenanceDays);
            $totalCapacity = $readyDays + $terpakaiDays + $maintenanceDays; // strictly equals $daysInMonth

            $utilizationRate = $totalCapacity > 0 ? round(($terpakaiDays / $totalCapacity) * 100, 1) : 0;
            $maintenanceRate = $totalCapacity > 0 ? round(($maintenanceDays / $totalCapacity) * 100, 1) : 0;
            $readyRate = $totalCapacity > 0 ? round(($readyDays / $totalCapacity) * 100, 1) : 0;

            return [
                'venue' => $venue,
                'terpakai_days' => $terpakaiDays,
                'fix_days' => $fixDays,
                'pipeline_days' => $pipelineDays,
                'loading_unloading_days' => $loadingUnloadingDays,
                'maintenance_days' => $maintenanceDays,
                'ready_days' => $readyDays,
                'total_capacity' => $totalCapacity,
                'utilization_rate' => $utilizationRate,
                'maintenance_rate' => $maintenanceRate,
                'ready_rate' => $readyRate,
                'events_count' => $vBookings->whereIn('status', ['fix', 'tentative', 'hold'])->count(),
                'is_unused' => ($terpakaiDays === 0),
            ];
        });

        $totalTerpakaiDays = $venueStats->sum('terpakai_days');
        $totalLoadingUnloadingDays = $venueStats->sum('loading_unloading_days');
        $totalMaintenanceDays = $venueStats->sum('maintenance_days');
        $totalReadyDays = $venueStats->sum('ready_days');
        $totalCapacityDays = $totalReadyDays + $totalTerpakaiDays + $totalMaintenanceDays;

        // Formula: Utilisasi Venue = Terpakai / (Ready + Terpakai + Maintenance) * 100%
        $utilization = $totalCapacityDays > 0
            ? round(($totalTerpakaiDays / $totalCapacityDays) * 100)
            : 0;

        $readyPercent = $totalCapacityDays > 0
            ? round(($totalReadyDays / $totalCapacityDays) * 100)
            : 0;

        $maintenancePercent = $totalCapacityDays > 0
            ? round(($totalMaintenanceDays / $totalCapacityDays) * 100)
            : 0;

        $sortedVenues = $venueStats->sortByDesc('terpakai_days')->values();

        $maxTerpakai = $sortedVenues->first()['terpakai_days'] ?? 0;
        $mostUsedVenues = $maxTerpakai > 0
            ? $sortedVenues->filter(fn ($v) => $v['terpakai_days'] === $maxTerpakai)->values()
            : collect();

        $unusedVenues = $sortedVenues->filter(fn ($v) => $v['terpakai_days'] === 0)->values();
        $leastUsedVenues = $unusedVenues->isNotEmpty()
            ? $unusedVenues
            : ($sortedVenues->count() > 1 ? collect([$sortedVenues->last()]) : collect());

        $utilizationData = [
            'terpakaiDays' => $totalTerpakaiDays,
            'loadingUnloadingDays' => $totalLoadingUnloadingDays,
            'readyDays' => $totalReadyDays,
            'maintenanceDays' => $totalMaintenanceDays,
            'totalCapacityDays' => $totalCapacityDays,
            'utilization' => $utilization,
            'readyPercent' => $readyPercent,
            'maintenancePercent' => $maintenancePercent,
            'venues' => $sortedVenues,
            'mostUsedVenues' => $mostUsedVenues,
            'unusedVenues' => $unusedVenues,
            'leastUsedVenues' => $leastUsedVenues,
        ];

        $expectedEarningFix = $monthBookings->where('status', 'fix')->sum('price');
        $expectedEarningPipeline = $monthBookings->whereIn('status', ['hold', 'tentative'])->sum('price');
        $expectedEarning = $expectedEarningFix + $expectedEarningPipeline;

        $warningIds = $bookings->filter(fn (Booking $b) => $b->isLoadingOrUnloading())->pluck('id');

        // Upcoming events visible to this user (single shared query)
        $upcomingEvents = $this->upcomingEventService->getUpcomingEvents($user, $filters['destination_id'] ?? null);
        [$upcomingStart, $upcomingEnd] = $this->upcomingEventService->getUpcomingWindow();

        $hasAnyUpcomingHighlight = false;

        $calendar = [];
        for ($date = $gridStart->copy(); $date->lte($gridEnd); $date->addDay()) {
            $dayEvents = $bookings->filter(fn (Booking $b) => $date->between($b->start_date, $b->end_date))->values();

            $isDateInUpcomingWindow = $date->betweenIncluded($upcomingStart, $upcomingEnd);
            $upcomingOnDay = $isDateInUpcomingWindow
                ? $upcomingEvents->filter(fn (Booking $b) => $date->between($b->start_date, $b->end_date))->values()
                : collect();

            $isUpcoming = $upcomingOnDay->isNotEmpty();
            if ($isUpcoming) {
                $hasAnyUpcomingHighlight = true;
            }

            $upcomingTooltip = '';
            if ($isUpcoming) {
                $count = $upcomingOnDay->count();
                $titles = $upcomingOnDay->pluck('title')->join(', ');
                $upcomingTooltip = "{$count} event mendatang: {$titles}";
            }

            $calendar[] = [
                'date' => $date->copy(),
                'inMonth' => $date->month === $month->month,
                'isToday' => $date->isToday(),
                'events' => $dayEvents,
                'hasWarning' => $isUpcoming,
                'isUpcoming' => $isUpcoming,
                'upcomingTooltip' => $upcomingTooltip,
            ];
        }

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
            'price' => $b->price !== null ? 'Rp '.number_format($b->price, 0, ',', '.') : '-',
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
            'hasAnyUpcomingHighlight' => $hasAnyUpcomingHighlight,
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
                'expectedEarning' => $expectedEarning,
                'expectedEarningFix' => $expectedEarningFix,
                'expectedEarningPipeline' => $expectedEarningPipeline,
            ],
            'recentBookings' => $monthBookings->sortBy('start_date')->take(8),
            'utilizationData' => $utilizationData,
        ]);
    }
}
