<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 week', '+3 months');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 14).' days');

        return [
            'vehicle_id' => Vehicle::factory(),
            'customer_id' => fn () => User::factory()->create()->customer->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => BookingStatus::Pending,
            'total_price' => fake()->randomFloat(2, 100, 5000),
            'deposit_amount' => fake()->randomFloat(2, 250, 2500),
            'deposit_paid_at' => null,
            'with_operator' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Pending,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Confirmed,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Active,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Completed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Cancelled,
        ]);
    }
}
