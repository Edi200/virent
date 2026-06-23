<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'driver_license_number' => strtoupper(fake()->bothify('??######')),
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+5 years'),
            'company_name' => fake()->optional()->company(),
            'tax_number' => fake()->optional()->bothify('########'),
            'address' => fake()->optional()->address(),
        ];
    }
}
