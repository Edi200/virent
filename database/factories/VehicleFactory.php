<?php

namespace Database\Factories;

use App\Enums\CategoryAttributeFieldType;
use App\Enums\VehicleStatus;
use App\Models\Category;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(2, true),
            'year' => fake()->numberBetween(2018, (int) date('Y')),
            'daily_rate' => fake()->randomFloat(2, 35, 250),
            'weekly_rate' => fake()->randomFloat(2, 200, 1500),
            'monthly_rate' => fake()->randomFloat(2, 700, 5000),
            'deposit_amount' => fake()->randomFloat(2, 250, 2500),
            'status' => VehicleStatus::Available,
            'description' => fake()->optional()->paragraph(),
            'specs' => null,
            'requires_license_type' => null,
            'available_with_operator' => false,
            'operator_daily_rate' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Vehicle $vehicle): void {
            if ($vehicle->specs !== null) {
                return;
            }

            $category = $vehicle->relationLoaded('category')
                ? $vehicle->category
                : Category::query()->with('categoryAttributes')->find($vehicle->category_id);

            if ($category !== null) {
                $vehicle->specs = self::generateSpecs($category);
            }
        });
    }

    public function withOperator(): static
    {
        return $this->state(fn () => [
            'available_with_operator' => true,
            'operator_daily_rate' => fake()->randomFloat(2, 150, 600),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function generateSpecs(Category $category): array
    {
        $specs = [];

        foreach ($category->categoryAttributes as $attribute) {
            $specs[$attribute->key] = match ($attribute->field_type) {
                CategoryAttributeFieldType::Text => fake()->word(),
                CategoryAttributeFieldType::Number => fake()->randomFloat(1, 1, 50),
                CategoryAttributeFieldType::Select => fake()->randomElement(
                    array_keys($attribute->options ?? []) ?: ['unknown']
                ),
                CategoryAttributeFieldType::Boolean => fake()->boolean(),
            };
        }

        return $specs;
    }
}
