<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Support\RamayanaOutdoorLayout;
use App\Support\TheaterFanLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeatController extends Controller
{
    /**
     * All shows/events on seated venues, across destinations (scoped for sales users),
     * so seat maps can be managed without drilling into each event individually.
     */
    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->whereHas('venue', fn ($v) => $v->where('is_seated', true))
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->whereHas(
                'venue', fn ($v) => $v->where('destination_id', $request->user()->destination_id)
            ))
            ->with(['venue.destination', 'venue.seats', 'bookingSeats'])
            ->orderByDesc('start_date')
            ->get();

        return view('seats.index', compact('bookings'));
    }

    public function edit(Booking $booking): View
    {
        abort_unless($booking->venue->is_seated, 404);

        $booking->load('venue.destination');
        $seats = $booking->venue->seats;
        $seatsByRow = $seats->groupBy('row_label');
        $statusBySeatId = $booking->bookingSeats()->pluck('status', 'seat_id');
        $isTheaterFan = $booking->venue->seat_layout === 'theater-fan';
        $isOutdoor = $booking->venue->seat_layout === 'ramayana-outdoor';

        $seatPositions = [];
        $seatPositionsOutdoor = [];
        $rowLabelPositions = [];
        if ($isTheaterFan) {
            $classKeys = ['N1' => '1', 'S1' => '1', 'N2' => '2', 'S2' => '2', 'special' => 'special'];
            $realSeatX = []; // zone => every actual seat's x, used only to keep "special" clear of its neighbours.
            $realSeatY = []; // zone => every actual seat's y, used only for N2/S2's Y (see below).
            $naturalPointsByZone = []; // zone => row => the point right next to that row's own last seat.

            foreach ($seatsByRow as $rowLabel => $rowSeats) {
                $rowCount = $rowSeats->count();
                [$zone, $row] = explode('-', $rowLabel, 2);

                foreach ($rowSeats as $seat) {
                    $pos = TheaterFanLayout::seatPosition($zone, $row, $seat->seat_number - 1, $rowCount);
                    $seatPositions[$seat->id] = [
                        'xPct' => round($pos['x'] / TheaterFanLayout::VIEWBOX_WIDTH * 100, 3),
                        'yPct' => round($pos['y'] / TheaterFanLayout::VIEWBOX_HEIGHT * 100, 3),
                        'rotation' => $pos['rotation'] ?? 0,
                    ];
                    $realSeatX[$zone][] = $pos['x'];
                    $realSeatY[$zone][] = $pos['y'];
                }

                // One step past the last seat, in the same direction the row already
                // extends — sits right next to that row's own seats, not some shared
                // reference point, so the label never drifts away from what it labels.
                $naturalPointsByZone[$zone][$row] = TheaterFanLayout::seatPosition($zone, $row, $rowCount, $rowCount);
            }

            foreach ($naturalPointsByZone as $zone => $pointsByRow) {


                foreach (TheaterFanLayout::ROWS as $row) {
                    $point = $pointsByRow[$row];
                    $x = $point['x'];

                    // Special sits in a narrow gap between two neighbours — its own
                    // natural point can overshoot into N1/S1's seats, so it's centered
                    // in the real gap on each side instead, mirrored on the far side.
                    if ($zone === 'special' && isset($realSeatX['N1'])) {
                        $left = (max($realSeatX['N1']) + min($realSeatX['special'])) / 2;
                        $xs = [$left, TheaterFanLayout::VIEWBOX_WIDTH - $left];
                    } else {
                        $xs = [$x];
                    }

                    foreach ($xs as $labelX) {
                        $rowLabelPositions[] = [
                            'xPct' => round($labelX / TheaterFanLayout::VIEWBOX_WIDTH * 100, 3),
                            'yPct' => round($point['y'] / TheaterFanLayout::VIEWBOX_HEIGHT * 100, 3),
                            'letter' => $row,
                            'classKey' => $classKeys[$zone],
                        ];
                    }
                }
            }
        } elseif ($isOutdoor) {
            foreach ($seatsByRow as $rowLabel => $rowSeats) {
                $rowCount = $rowSeats->count();
                $parts = explode('-', $rowLabel, 2);
                $zone = $parts[0];
                $row = $parts[1] ?? 'A'; // fallback if no '-'

                foreach ($rowSeats as $seat) {
                    $pos = RamayanaOutdoorLayout::seatPosition($zone, $row, $seat->seat_number - 1, $rowCount);
                    $seatPositionsOutdoor[$seat->id] = [
                        'xPct' => round($pos['x'] / RamayanaOutdoorLayout::VIEWBOX_WIDTH * 100, 3),
                        'yPct' => round($pos['y'] / RamayanaOutdoorLayout::VIEWBOX_HEIGHT * 100, 3),
                        'rotation' => $pos['rotation'],
                    ];
                }
            }
        }

        return view('bookings.seats', [
            'booking' => $booking,
            'seatsByRow' => $seatsByRow,
            'maxSeatNumber' => (int) $seats->max('seat_number'),
            'statusBySeatId' => $statusBySeatId,
            'canManage' => Gate::allows('update', $booking),
            'isTheaterFan' => $isTheaterFan,
            'isOutdoor' => $isOutdoor,
            'seatPositions' => $seatPositions,
            'seatPositionsOutdoor' => $seatPositionsOutdoor,
            'rowLabelPositions' => $rowLabelPositions,
            'totalSeats' => $seats->count(),
            'bookedCount' => $statusBySeatId->filter(fn ($s) => $s === 'booked')->count(),
            'blockedCount' => $statusBySeatId->filter(fn ($s) => $s === 'blocked')->count(),
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);
        abort_unless($booking->venue->is_seated, 404);

        $existsInVenue = Rule::exists('seats', 'id')->where('venue_id', $booking->venue_id);

        $data = $request->validate([
            'book' => ['array'],
            'book.*' => [$existsInVenue],
            'block' => ['array'],
            'block.*' => [$existsInVenue],
            'release' => ['array'],
            'release.*' => [$existsInVenue],
        ]);

        $book = $data['book'] ?? [];
        $block = $data['block'] ?? [];
        $release = $data['release'] ?? [];

        DB::transaction(function () use ($booking, $book, $block, $release) {
            if ($release !== []) {
                $booking->bookingSeats()->whereIn('seat_id', $release)->delete();
            }

            foreach ($block as $seatId) {
                BookingSeat::updateOrCreate(
                    ['booking_id' => $booking->id, 'seat_id' => $seatId],
                    ['status' => 'blocked'],
                );
            }

            foreach ($book as $seatId) {
                BookingSeat::updateOrCreate(
                    ['booking_id' => $booking->id, 'seat_id' => $seatId],
                    ['status' => 'booked'],
                );
            }
        });

        return redirect()->route('bookings.seats.edit', $booking)->with('success', 'Perubahan kursi berhasil disimpan.');
    }
}
