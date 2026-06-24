<?php

namespace Database\Seeders;

use App\Enums\CategoryAttributeFieldType;
use App\Enums\VehicleStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $carsGroup = $this->createGroup('Cars', 'cars', 1);
        $vansGroup = $this->createGroup('Vans', 'vans', 2);
        $machineryGroup = $this->createGroup('Machinery', 'machinery', 3);

        $car = $this->createCategory($carsGroup, 'Car', 'car', 1, $this->carCategoryAttributes());

        $van = $this->createCategory($vansGroup, 'Van', 'van', 2, [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'cargo_volume_m3', 'label' => 'Cargo volume (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'seats', 'label' => 'Seats', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'sliding_doors', 'label' => 'Sliding doors', 'field_type' => CategoryAttributeFieldType::Boolean, 'required' => false, 'sort_order' => 4],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 5],
        ]);

        $excavator = $this->createCategory($machineryGroup, 'Excavator', 'excavator', 3, [
            ['key' => 'operating_weight_t', 'label' => 'Operating weight (t)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 1],
            ['key' => 'bucket_capacity_m3', 'label' => 'Bucket capacity (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'max_dig_depth_m', 'label' => 'Max dig depth (m)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 4],
        ]);

        $this->seedVehicles($car, [
            ['name' => 'Toyota Corolla', 'year' => 2022, 'daily_rate' => 45.00, 'weekly_rate' => 280.00, 'monthly_rate' => 950.00, 'deposit_amount' => 400.00, 'specs' => ['make' => 'Toyota', 'model' => 'Corolla', 'body_type' => 'sedan', 'fuel_type' => 'hybrid', 'engine_displacement' => 1798, 'horsepower' => 140, 'transmission' => 'automatic', 'seats' => 5]],
            ['name' => 'BMW 3 Series', 'year' => 2023, 'daily_rate' => 89.00, 'weekly_rate' => 550.00, 'monthly_rate' => 1800.00, 'deposit_amount' => 800.00, 'specs' => ['make' => 'BMW', 'model' => '320i', 'body_type' => 'sedan', 'fuel_type' => 'petrol', 'engine_displacement' => 1998, 'horsepower' => 184, 'transmission' => 'automatic', 'seats' => 5]],
            ['name' => 'Volkswagen Passat', 'year' => 2021, 'daily_rate' => 55.00, 'weekly_rate' => 340.00, 'monthly_rate' => 1100.00, 'deposit_amount' => 500.00, 'specs' => ['make' => 'Volkswagen', 'model' => 'Passat', 'body_type' => 'sedan', 'fuel_type' => 'diesel', 'engine_displacement' => 1968, 'horsepower' => 150, 'transmission' => 'manual', 'seats' => 5]],
            ['name' => 'Volvo V60', 'year' => 2022, 'daily_rate' => 70.00, 'weekly_rate' => 430.00, 'monthly_rate' => 1400.00, 'deposit_amount' => 600.00, 'specs' => ['make' => 'Volvo', 'model' => 'V60', 'body_type' => 'wagon', 'fuel_type' => 'petrol', 'engine_displacement' => 1969, 'horsepower' => 250, 'transmission' => 'automatic', 'seats' => 5, 'cargo_volume_m3' => 0.54, 'towing_capacity_kg' => 1800, 'roof_rails' => true]],
            ['name' => 'Skoda Octavia Combi', 'year' => 2023, 'daily_rate' => 58.00, 'weekly_rate' => 360.00, 'monthly_rate' => 1150.00, 'deposit_amount' => 500.00, 'specs' => ['make' => 'Skoda', 'model' => 'Octavia Combi', 'body_type' => 'wagon', 'fuel_type' => 'petrol', 'engine_displacement' => 1498, 'horsepower' => 150, 'transmission' => 'automatic', 'seats' => 5, 'cargo_volume_m3' => 0.64, 'towing_capacity_kg' => 1500, 'roof_rails' => true]],
        ]);

        $this->seedVehicles($van, [
            ['name' => 'Ford Transit', 'year' => 2022, 'daily_rate' => 75.00, 'weekly_rate' => 460.00, 'monthly_rate' => 1500.00, 'deposit_amount' => 600.00, 'specs' => ['make' => 'Ford', 'cargo_volume_m3' => 9.5, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 170]],
            ['name' => 'Mercedes Sprinter', 'year' => 2023, 'daily_rate' => 95.00, 'weekly_rate' => 580.00, 'monthly_rate' => 1900.00, 'deposit_amount' => 750.00, 'specs' => ['make' => 'Mercedes-Benz', 'cargo_volume_m3' => 11.0, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 190]],
            ['name' => 'Renault Trafic', 'year' => 2021, 'daily_rate' => 65.00, 'weekly_rate' => 400.00, 'monthly_rate' => 1300.00, 'deposit_amount' => 550.00, 'specs' => ['make' => 'Renault', 'cargo_volume_m3' => 8.6, 'seats' => 3, 'sliding_doors' => false, 'horsepower' => 145]],
        ]);

        $this->seedVehicles($excavator, [
            ['name' => 'CAT 320', 'year' => 2020, 'daily_rate' => 350.00, 'weekly_rate' => 2100.00, 'monthly_rate' => 7000.00, 'deposit_amount' => 3000.00, 'available_with_operator' => true, 'operator_daily_rate' => 180.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 22.0, 'bucket_capacity_m3' => 1.2, 'max_dig_depth_m' => 6.7, 'horsepower' => 160]],
            ['name' => 'Komatsu PC210', 'year' => 2021, 'daily_rate' => 320.00, 'weekly_rate' => 1950.00, 'monthly_rate' => 6500.00, 'deposit_amount' => 2800.00, 'available_with_operator' => true, 'operator_daily_rate' => 165.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 21.5, 'bucket_capacity_m3' => 1.0, 'max_dig_depth_m' => 6.5, 'horsepower' => 158]],
        ]);

        $this->seedDemoCustomers();
    }

    private function createGroup(string $name, string $slug, int $sortOrder): VehicleGroup
    {
        return VehicleGroup::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $attributes
     */
    private function createCategory(
        VehicleGroup $group,
        string $name,
        string $slug,
        int $sortOrder,
        array $attributes,
    ): Category {
        $category = Category::query()->create([
            'group_id' => $group->id,
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
     * @return list<array<string, mixed>>
     */
    private function carCategoryAttributes(): array
    {
        return [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'model', 'label' => 'Model', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 2],
            ['key' => 'body_type', 'label' => 'Body type', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['sedan' => 'Sedan', 'wagon' => 'Wagon', 'hatchback' => 'Hatchback', 'suv' => 'SUV'], 'required' => true, 'sort_order' => 3],
            ['key' => 'fuel_type', 'label' => 'Fuel type', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['petrol' => 'Petrol', 'diesel' => 'Diesel', 'hybrid' => 'Hybrid', 'electric' => 'Electric'], 'required' => true, 'sort_order' => 4],
            ['key' => 'engine_displacement', 'label' => 'Engine displacement (cc)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 5],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 6],
            ['key' => 'transmission', 'label' => 'Transmission', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['manual' => 'Manual', 'automatic' => 'Automatic'], 'required' => true, 'sort_order' => 7],
            ['key' => 'seats', 'label' => 'Seats', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 8],
            ['key' => 'cargo_volume_m3', 'label' => 'Cargo volume (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => false, 'sort_order' => 9],
            ['key' => 'towing_capacity_kg', 'label' => 'Towing capacity (kg)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => false, 'sort_order' => 10],
            ['key' => 'roof_rails', 'label' => 'Roof rails', 'field_type' => CategoryAttributeFieldType::Boolean, 'required' => false, 'sort_order' => 11],
        ];
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
