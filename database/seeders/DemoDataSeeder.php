<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Destination;
use App\Models\EventType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = collect([
            ['name' => 'Candi Prambanan', 'slug' => 'prambanan'],
            ['name' => 'Ratu Boko', 'slug' => 'ratu-boko'],
            ['name' => 'Candi Borobudur', 'slug' => 'borobudur'],
        ])->mapWithKeys(fn ($d) => [$d['slug'] => Destination::create($d)]);

        $venues = [
            'prambanan' => [
                ['name' => 'Lapangan Garuda', 'type' => 'outdoor', 'capacity' => 5000, 'description' => 'Lapangan terbuka di kompleks Taman Wisata Candi Prambanan, biasa dipakai untuk konferensi dan acara skala besar.'],
                ['name' => 'Trimurti Open Stage', 'type' => 'outdoor', 'capacity' => 1500, 'description' => 'Panggung terbuka menghadap Candi Prambanan, ideal untuk pertunjukan budaya seperti Sendratari Ramayana.'],
                ['name' => 'Manohara Field', 'type' => 'outdoor', 'capacity' => 3000, 'description' => 'Area lapangan dekat Hotel Manohara, sering dipakai untuk gathering korporat dan resepsi.'],
            ],
            'ratu-boko' => [
                ['name' => 'Taman Gapura Ratu Boko', 'type' => 'outdoor', 'capacity' => 800, 'description' => 'Taman terbuka di area gapura utama dengan pemandangan matahari terbenam.'],
                ['name' => 'Ratu Boko Plaza Andrawina', 'type' => 'outdoor', 'capacity' => 600, 'description' => 'Plaza yang biasa dipakai untuk jamuan makan dan acara semi-formal.'],
                ['name' => 'Ratu Boko Meeting Room', 'type' => 'indoor', 'capacity' => 100, 'description' => 'Ruang pertemuan indoor untuk rapat dan acara korporat skala kecil.'],
            ],
            'borobudur' => [
                ['name' => 'Lumbini Ground', 'type' => 'outdoor', 'capacity' => 4000, 'description' => 'Lapangan terbuka di kawasan Taman Wisata Candi Borobudur.'],
                ['name' => 'Marga Utama Hall', 'type' => 'indoor', 'capacity' => 500, 'description' => 'Hall indoor untuk konferensi dan acara korporat.'],
                ['name' => 'Kujon Field', 'type' => 'outdoor', 'capacity' => 2000, 'description' => 'Lapangan yang sering dipakai untuk festival budaya dan acara komunitas.'],
            ],
        ];

        $venueModels = collect();
        foreach ($venues as $slug => $list) {
            foreach ($list as $venue) {
                $venueModels->push(Venue::create([
                    ...$venue,
                    'destination_id' => $destinations[$slug]->id,
                    'slug' => str($venue['name'])->slug(),
                    'is_active' => true,
                ]));
            }
        }

        $eventTypes = collect(['Corporate Event', 'Wedding', 'Conference', 'Cultural Performance', 'Internal'])
            ->mapWithKeys(fn ($name) => [$name => EventType::create(['name' => $name])]);

        $admin = User::create([
            'name' => 'Admin Venue Tracker',
            'email' => 'admin@venuetracker.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $salesUsers = collect([
            'prambanan' => ['name' => 'Andi Rachman', 'email' => 'sales.prambanan@venuetracker.test'],
            'ratu-boko' => ['name' => 'Sari Dewi', 'email' => 'sales.ratuboko@venuetracker.test'],
            'borobudur' => ['name' => 'Budi Santoso', 'email' => 'sales.borobudur@venuetracker.test'],
        ])->map(fn ($u, $slug) => User::create([
            ...$u,
            'password' => Hash::make('password'),
            'role' => 'sales',
            'destination_id' => $destinations[$slug]->id,
        ]));

        $clients = collect([
            'PT Maju Bersama Indonesia', 'BCA', 'Tech Nusantara', 'Kemendikbud',
            'Nusa Group', 'Delta Corp', 'Vista Indonesia', 'Auto Prima',
        ])->map(fn ($name) => Client::create(['name' => $name]));

        $month = Carbon::now()->startOfMonth();
        $statuses = ['hold', 'tentative', 'fix', 'fix', 'cancelled'];
        $titles = [
            'Gala Dinner', 'Corporate Gathering', 'Wedding Reception', 'Product Launch',
            'Tech Conference', 'Festival Budaya', 'Award Night', 'Board Meeting',
            'Sendratari Ramayana', 'Team Building', 'Preventive Maintenance', 'Company Outing',
        ];

        foreach (range(0, 23) as $i) {
            $venue = $venueModels[$i % $venueModels->count()];
            $sales = $salesUsers[$destinations->search(fn ($d) => $d->id === $venue->destination_id)] ?? $salesUsers->first();
            $start = $month->copy()->addDays(($i * 3) % 27);

            Booking::create([
                'code' => sprintf('EV-%s-%04d', $month->year, $i + 1),
                'venue_id' => $venue->id,
                'event_type_id' => $eventTypes->values()[$i % $eventTypes->count()]->id,
                'client_id' => $clients[$i % $clients->count()]->id,
                'pic_user_id' => $sales->id,
                'created_by' => $admin->id,
                'title' => $titles[$i % count($titles)],
                'start_date' => $start,
                'end_date' => $start,
                'guest_count' => [50, 150, 300, 800, 1500][$i % 5],
                'status' => $statuses[$i % count($statuses)],
                'notes' => null,
            ]);
        }

        // Two demo bookings that showcase the loading/unloading warning out of the box.
        Booking::create([
            'code' => sprintf('EV-%s-DEMO1', $month->year),
            'venue_id' => $venueModels->first()->id,
            'event_type_id' => $eventTypes->first()->id,
            'client_id' => $clients->first()->id,
            'pic_user_id' => $salesUsers->first()->id,
            'created_by' => $admin->id,
            'title' => 'Demo: Crew Sedang Loading',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay(),
            'load_at' => now()->subHours(2),
            'unload_at' => now()->addDay()->endOfDay(),
            'guest_count' => 200,
            'status' => 'fix',
            'notes' => 'Event besok, tapi kru sudah mulai setup 2 jam lalu — klik untuk lihat peringatan.',
        ]);

        Booking::create([
            'code' => sprintf('EV-%s-DEMO2', $month->year),
            'venue_id' => $venueModels->last()->id,
            'event_type_id' => $eventTypes->first()->id,
            'client_id' => $clients->last()->id,
            'pic_user_id' => $salesUsers->last()->id,
            'created_by' => $admin->id,
            'title' => 'Demo: Crew Sedang Unloading',
            'start_date' => now()->subDay(),
            'end_date' => now()->subDay(),
            'load_at' => now()->subDay()->startOfDay(),
            'unload_at' => now()->addHours(3),
            'guest_count' => 150,
            'status' => 'fix',
            'notes' => 'Event kemarin sudah selesai, tapi kru masih bongkar sampai 3 jam lagi — klik untuk lihat peringatan.',
        ]);
    }
}
