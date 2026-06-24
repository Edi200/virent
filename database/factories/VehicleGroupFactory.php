<?php

namespace Database\Factories;

use App\Models\VehicleGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleGroup>
 */
class VehicleGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $words = fake()->unique()->words(2);
        $name = is_array($words) ? implode(' ', $words) : $words;

        return [
            'name' => ucwords($name),
            'icon' => null,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
