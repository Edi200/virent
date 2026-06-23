<?php

namespace Database\Factories;

use App\Enums\CategoryAttributeFieldType;
use App\Models\Category;
use App\Models\CategoryAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryAttribute>
 */
class CategoryAttributeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->word();

        return [
            'category_id' => Category::factory(),
            'key' => $key,
            'label' => ucfirst($key),
            'field_type' => CategoryAttributeFieldType::Text,
            'options' => null,
            'required' => false,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * @param  array<string, string>  $options
     */
    public function select(array $options): static
    {
        return $this->state(fn () => [
            'field_type' => CategoryAttributeFieldType::Select,
            'options' => $options,
        ]);
    }

    public function number(): static
    {
        return $this->state(fn () => [
            'field_type' => CategoryAttributeFieldType::Number,
        ]);
    }

    public function boolean(): static
    {
        return $this->state(fn () => [
            'field_type' => CategoryAttributeFieldType::Boolean,
        ]);
    }
}
