<?php

namespace Database\Factories;

use App\Models\BookingHold;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<BookingHold>
 */
class BookingHoldFactory extends Factory
{
    protected $model = BookingHold::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 week', '+3 months');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 7).' days');

        return [
            'vehicle_id' => Vehicle::factory(),
            'user_id' => User::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => Carbon::now()->subMinute(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
    }
}
