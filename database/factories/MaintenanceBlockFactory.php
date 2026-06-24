<?php

namespace Database\Factories;

use App\Models\MaintenanceBlock;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceBlock>
 */
class MaintenanceBlockFactory extends Factory
{
    protected $model = MaintenanceBlock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 week', '+3 months');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 7).' days');

        return [
            'vehicle_id' => Vehicle::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
