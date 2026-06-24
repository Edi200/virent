<?php

namespace Database\Seeders;

use App\Enums\CategoryAttributeFieldType;
use App\Enums\VehicleStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $sedan = $this->createCategory('Sedan', 'sedan', 1, [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'model', 'label' => 'Model', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 2],
            ['key' => 'fuel_type', 'label' => 'Fuel type', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['petrol' => 'Petrol', 'diesel' => 'Diesel', 'hybrid' => 'Hybrid', 'electric' => 'Electric'], 'required' => true, 'sort_order' => 3],
            ['key' => 'seats', 'label' => 'Seats', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 4],
            ['key' => 'transmission', 'label' => 'Transmission', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['manual' => 'Manual', 'automatic' => 'Automatic'], 'required' => true, 'sort_order' => 5],
        ]);

        $van = $this->createCategory('Van', 'van', 2, [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'cargo_volume_m3', 'label' => 'Cargo volume (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'seats', 'label' => 'Seats', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'sliding_doors', 'label' => 'Sliding doors', 'field_type' => CategoryAttributeFieldType::Boolean, 'required' => false, 'sort_order' => 4],
        ]);

        $wagon = $this->createCategory('Wagon', 'wagon', 3, [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'cargo_volume_m3', 'label' => 'Cargo volume (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'towing_capacity_kg', 'label' => 'Towing capacity (kg)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'roof_rails', 'label' => 'Roof rails', 'field_type' => CategoryAttributeFieldType::Boolean, 'required' => false, 'sort_order' => 4],
        ]);

        $excavator = $this->createCategory('Excavator', 'excavator', 4, [
            ['key' => 'operating_weight_t', 'label' => 'Operating weight (t)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 1],
            ['key' => 'bucket_capacity_m3', 'label' => 'Bucket capacity (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'max_dig_depth_m', 'label' => 'Max dig depth (m)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
        ]);

        $this->seedVehicles($sedan, [
            ['name' => 'Toyota Corolla', 'year' => 2022, 'daily_rate' => 45.00, 'weekly_rate' => 280.00, 'monthly_rate' => 950.00, 'deposit_amount' => 400.00, 'specs' => ['make' => 'Toyota', 'model' => 'Corolla', 'fuel_type' => 'hybrid', 'seats' => 5, 'transmission' => 'automatic']],
            ['name' => 'BMW 3 Series', 'year' => 2023, 'daily_rate' => 89.00, 'weekly_rate' => 550.00, 'monthly_rate' => 1800.00, 'deposit_amount' => 800.00, 'specs' => ['make' => 'BMW', 'model' => '320i', 'fuel_type' => 'petrol', 'seats' => 5, 'transmission' => 'automatic']],
            ['name' => 'Volkswagen Passat', 'year' => 2021, 'daily_rate' => 55.00, 'weekly_rate' => 340.00, 'monthly_rate' => 1100.00, 'deposit_amount' => 500.00, 'specs' => ['make' => 'Volkswagen', 'model' => 'Passat', 'fuel_type' => 'diesel', 'seats' => 5, 'transmission' => 'manual']],
        ]);

        $this->seedVehicles($van, [
            ['name' => 'Ford Transit', 'year' => 2022, 'daily_rate' => 75.00, 'weekly_rate' => 460.00, 'monthly_rate' => 1500.00, 'deposit_amount' => 600.00, 'specs' => ['make' => 'Ford', 'cargo_volume_m3' => 9.5, 'seats' => 3, 'sliding_doors' => true]],
            ['name' => 'Mercedes Sprinter', 'year' => 2023, 'daily_rate' => 95.00, 'weekly_rate' => 580.00, 'monthly_rate' => 1900.00, 'deposit_amount' => 750.00, 'specs' => ['make' => 'Mercedes-Benz', 'cargo_volume_m3' => 11.0, 'seats' => 3, 'sliding_doors' => true]],
            ['name' => 'Renault Trafic', 'year' => 2021, 'daily_rate' => 65.00, 'weekly_rate' => 400.00, 'monthly_rate' => 1300.00, 'deposit_amount' => 550.00, 'specs' => ['make' => 'Renault', 'cargo_volume_m3' => 8.6, 'seats' => 3, 'sliding_doors' => false]],
        ]);

        $this->seedVehicles($wagon, [
            ['name' => 'Volvo V60', 'year' => 2022, 'daily_rate' => 70.00, 'weekly_rate' => 430.00, 'monthly_rate' => 1400.00, 'deposit_amount' => 600.00, 'specs' => ['make' => 'Volvo', 'cargo_volume_m3' => 0.54, 'towing_capacity_kg' => 1800, 'roof_rails' => true]],
            ['name' => 'Skoda Octavia Combi', 'year' => 2023, 'daily_rate' => 58.00, 'weekly_rate' => 360.00, 'monthly_rate' => 1150.00, 'deposit_amount' => 500.00, 'specs' => ['make' => 'Skoda', 'cargo_volume_m3' => 0.64, 'towing_capacity_kg' => 1500, 'roof_rails' => true]],
        ]);

        $this->seedVehicles($excavator, [
            ['name' => 'CAT 320', 'year' => 2020, 'daily_rate' => 350.00, 'weekly_rate' => 2100.00, 'monthly_rate' => 7000.00, 'deposit_amount' => 3000.00, 'available_with_operator' => true, 'operator_daily_rate' => 180.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 22.0, 'bucket_capacity_m3' => 1.2, 'max_dig_depth_m' => 6.7]],
            ['name' => 'Komatsu PC210', 'year' => 2021, 'daily_rate' => 320.00, 'weekly_rate' => 1950.00, 'monthly_rate' => 6500.00, 'deposit_amount' => 2800.00, 'available_with_operator' => true, 'operator_daily_rate' => 165.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 21.5, 'bucket_capacity_m3' => 1.0, 'max_dig_depth_m' => 6.5]],
        ]);

        $this->seedDemoCustomers();
    }

    /**
     * @param  array<int, array<string, mixed>>  $attributes
     */
    private function createCategory(string $name, string $slug, int $sortOrder, array $attributes): Category
    {
        $category = Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sortOrder,
        ]);

        foreach ($attributes as $attribute) {
            $category->categoryAttributes()->create($attribute);
        }

        return $category;
    }

    /**
     * @param  array<int, array<string, mixed>>  $vehicles
     */
    private function seedVehicles(Category $category, array $vehicles): void
    {
        foreach ($vehicles as $vehicle) {
            Vehicle::query()->create([
                'category_id' => $category->id,
                'status' => VehicleStatus::Available,
                'description' => null,
                'requires_license_type' => null,
                'available_with_operator' => false,
                'operator_daily_rate' => null,
                'slug' => Vehicle::generateUniqueSlug($vehicle['name']),
                ...$vehicle,
            ]);
        }
    }

    private function seedDemoCustomers(): void
    {
        $retailUser = User::factory()->create([
            'name' => 'Anna Kowalski',
            'email' => 'anna.kowalski@example.com',
        ]);

        Customer::factory()->create([
            'user_id' => $retailUser->id,
            'driver_license_number' => 'PL12345678',
            'license_expiry' => now()->addYears(3),
            'company_name' => null,
            'tax_number' => null,
            'address' => 'ul. Marszałkowska 10, 00-590 Warsaw',
        ]);

        $businessUser = User::factory()->create([
            'name' => 'BuildCo Sp. z o.o.',
            'email' => 'fleet@buildco.example.com',
        ]);

        Customer::factory()->create([
            'user_id' => $businessUser->id,
            'driver_license_number' => 'PL87654321',
            'license_expiry' => now()->addYears(2),
            'company_name' => 'BuildCo Sp. z o.o.',
            'tax_number' => '1234567890',
            'address' => 'ul. Przemysłowa 25, 02-450 Warsaw',
        ]);
    }
}
