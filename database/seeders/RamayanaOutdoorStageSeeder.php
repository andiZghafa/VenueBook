<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Destination;
use App\Models\EventType;
use App\Models\Seat;
use App\Models\User;
use App\Models\Venue;
use App\Support\RamayanaOutdoorLayout;
use Illuminate\Database\Seeder;

class RamayanaOutdoorStageSeeder extends Seeder
{
    public function run(): void
    {
        $destination = Destination::where('slug', 'prambanan')->first() ?? Destination::first();

        if (! $destination) {
            $this->command?->warn('No destination found — run DemoDataSeeder first.');
            return;
        }

        $totalSeats = array_sum(array_map(
            fn (string $zone) => array_sum(RamayanaOutdoorLayout::rowSeatCounts($zone)),
            RamayanaOutdoorLayout::zones(),
        ));

        $venue = Venue::firstOrCreate(
            ['slug' => 'ramayana-outdoor-stage'],
            [
                'destination_id' => $destination->id,
                'name' => 'Ramayana Outdoor Stage',
                'type' => 'outdoor',
                'capacity' => $totalSeats,
                'is_seated' => true,
                'seat_layout' => 'ramayana-outdoor',
                'description' => 'Panggung terbuka untuk Sendratari Ramayana, mengikuti denah kursi Ramayana Open Air Theater.',
                'is_active' => true,
            ]
        );

        $venue->update(['is_seated' => true, 'seat_layout' => 'ramayana-outdoor', 'capacity' => $totalSeats]);

        if ($venue->seats()->count() === $totalSeats) {
            $this->command?->info('Ramayana Outdoor Stage seat map already matches the ramayana-outdoor layout — skipping.');
        } else {
            $seatIds = $venue->seats()->pluck('id');
            BookingSeat::whereIn('seat_id', $seatIds)->delete();
            Seat::whereIn('id', $seatIds)->delete();

            foreach (RamayanaOutdoorLayout::zones() as $zone) {
                foreach (RamayanaOutdoorLayout::rowSeatCounts($zone) as $row => $count) {
                    for ($seatNumber = 1; $seatNumber <= $count; $seatNumber++) {
                        Seat::create([
                            'venue_id' => $venue->id,
                            'row_label' => "{$zone}-{$row}",
                            'seat_number' => $seatNumber,
                            'category' => $zone,
                            'sort_order' => array_search($zone, RamayanaOutdoorLayout::zones(), true),
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
            ['code' => 'EV-RAMAYANA-OUTDOOR-DEMO'],
            [
                'venue_id' => $venue->id,
                'event_type_id' => $eventType->id,
                'created_by' => $admin->id,
                'title' => 'Ramayana Ballet Outdoor',
                'start_date' => now()->addWeek()->addDays(2),
                'end_date' => now()->addWeek()->addDays(2),
                'guest_count' => 100,
                'status' => 'fix',
                'notes' => 'Contoh jadwal untuk simulasi booking per kursi outdoor.',
            ]
        );

        if (! $booking->bookingSeats()->exists()) {
            $demoBookedSeats = $venue->seats()->where('row_label', 'VIP-A')->whereIn('seat_number', [5, 6, 7])->get();
            foreach ($demoBookedSeats as $seat) {
                BookingSeat::create(['booking_id' => $booking->id, 'seat_id' => $seat->id, 'status' => 'booked']);
            }
        }
    }
}
