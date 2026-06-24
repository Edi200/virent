<?php

namespace Database\Factories;

use App\Enums\ExtraPriceType;
use App\Models\Extra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Extra>
 */
class ExtraFactory extends Factory
{
    protected $model = Extra::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'price' => fake()->randomFloat(2, 5, 150),
            'price_type' => ExtraPriceType::Flat,
        ];
    }

    public function flat(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price_type' => ExtraPriceType::Flat,
        ]);
    }

    public function perDay(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price_type' => ExtraPriceType::PerDay,
        ]);
    }
}
