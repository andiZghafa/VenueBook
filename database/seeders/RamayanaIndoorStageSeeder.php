<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Destination;
use App\Models\EventType;
use App\Models\Seat;
use App\Models\User;
use App\Models\Venue;
use App\Support\TheaterFanLayout;
use Illuminate\Database\Seeder;

/**
 * Adds the "Ramayana Indoor Stage" venue using the real Trimurti Theatre seating chart:
 * a center "Special" block facing the stage, flanked by N1/S1 (inner) and N2/S2 (outer)
 * blocks — 378 seats total. Safe to re-run: it regenerates the seat map only when it
 * doesn't already match this layout, and never touches any other venue's data.
 */
class RamayanaIndoorStageSeeder extends Seeder
{
    public function run(): void
    {
        $destination = Destination::where('slug', 'prambanan')->first() ?? Destination::first();

        if (! $destination) {
            $this->command?->warn('No destination found — run DemoDataSeeder first.');

            return;
        }

        $totalSeats = array_sum(array_map(
            fn (string $zone) => TheaterFanLayout::zoneTotal($zone),
            TheaterFanLayout::zones(),
        ));

        $venue = Venue::firstOrCreate(
            ['slug' => 'ramayana-indoor-stage'],
            [
                'destination_id' => $destination->id,
                'name' => 'Ramayana Indoor Stage',
                'type' => 'indoor',
                'capacity' => $totalSeats,
                'is_seated' => true,
                'seat_layout' => 'theater-fan',
                'description' => 'Panggung indoor untuk Sendratari Ramayana, mengikuti denah kursi Trimurti Theatre (N2/N1/Special/S1/S2) — booking dilakukan per kursi.',
                'is_active' => true,
            ]
        );

        $venue->update(['is_seated' => true, 'seat_layout' => 'theater-fan', 'capacity' => $totalSeats]);

        if ($venue->seats()->count() === $totalSeats) {
            $this->command?->info('Ramayana Indoor Stage seat map already matches the theater-fan layout — skipping.');
        } else {
            // Layout changed shape (e.g. an older demo grid) — regenerate from scratch rather
            // than leave stale rows that no longer correspond to any zone/row on screen.
            $seatIds = $venue->seats()->pluck('id');
            BookingSeat::whereIn('seat_id', $seatIds)->delete();
            Seat::whereIn('id', $seatIds)->delete();

            foreach (TheaterFanLayout::zones() as $zone) {
                foreach (TheaterFanLayout::rowSeatCounts($zone) as $row => $count) {
                    for ($seatNumber = 1; $seatNumber <= $count; $seatNumber++) {
                        Seat::create([
                            'venue_id' => $venue->id,
                            'row_label' => "{$zone}-{$row}",
                            'seat_number' => $seatNumber,
                            'category' => $zone,
                            'sort_order' => array_search($zone, TheaterFanLayout::zones(), true),
                        ]);
                    }
                }
            }
        }

        $eventType = EventType::firstOrCreate(['name' => 'Cultural Performance']);
        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->command?->warn('No admin user found — skipping demo booking.');

            return;
        }

        $booking = Booking::firstOrCreate(
            ['code' => 'EV-RAMAYANA-INDOOR-DEMO'],
            [
                'venue_id' => $venue->id,
                'event_type_id' => $eventType->id,
                'created_by' => $admin->id,
                'title' => 'Ramayana Ballet Indoor',
                'start_date' => now()->addWeek(),
                'end_date' => now()->addWeek(),
                'guest_count' => 60,
                'status' => 'fix',
                'notes' => 'Contoh jadwal untuk simulasi booking per kursi.',
            ]
        );

        if (! $booking->bookingSeats()->exists()) {
            $demoBookedSeats = $venue->seats()->where('row_label', 'special-B')->whereIn('seat_number', [5, 6, 7])->get();
            foreach ($demoBookedSeats as $seat) {
                BookingSeat::create(['booking_id' => $booking->id, 'seat_id' => $seat->id, 'status' => 'booked']);
            }

            $demoBlockedSeat = $venue->seats()->where('row_label', 'N1-C')->where('seat_number', 4)->first();
            if ($demoBlockedSeat) {
                BookingSeat::create(['booking_id' => $booking->id, 'seat_id' => $demoBlockedSeat->id, 'status' => 'blocked']);
            }
        }
    }
}
