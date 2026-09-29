<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use App\Models\EventType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    private static int $sequence = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::now()->startOfMonth()->addDays($this->faker->numberBetween(0, 27));
        $admin = User::where('role', 'admin')->first();

        return [
            'code' => sprintf('EV-%s-%04d', now()->year, self::$sequence++),
            'venue_id' => Venue::inRandomOrder()->value('id'),
            'event_type_id' => EventType::inRandomOrder()->value('id'),
            'client_id' => Client::inRandomOrder()->value('id'),
            'pic_user_id' => User::where('role', 'sales')->inRandomOrder()->value('id'),
            'created_by' => $admin?->id ?? User::query()->value('id'),
            'title' => $this->faker->randomElement([
                'Gala Dinner', 'Corporate Gathering', 'Wedding Reception', 'Product Launch',
                'Tech Conference', 'Festival Budaya', 'Award Night', 'Board Meeting', 'Team Building',
            ]),
            'start_date' => $start,
            'end_date' => $start,
            'guest_count' => $this->faker->numberBetween(50, 1500),
            'status' => $this->faker->randomElement(['hold', 'tentative', 'fix', 'fix', 'cancelled']),
            'notes' => null,
        ];
    }
}
