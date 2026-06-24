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
        $motorcyclesGroup = $this->createGroup('Motorcycles', 'motorcycles', 4);

        $car = $this->createCategory($carsGroup, 'Car', 'car', 1, $this->carCategoryAttributes());
        $van = $this->createCategory($vansGroup, 'Van', 'van', 2, $this->vanCategoryAttributes());
        $pickup = $this->createCategory($vansGroup, 'Pickup', 'pickup', 3, $this->pickupCategoryAttributes());
        $excavator = $this->createCategory($machineryGroup, 'Excavator', 'excavator', 4, $this->excavatorCategoryAttributes());
        $loader = $this->createCategory($machineryGroup, 'Loader', 'loader', 5, $this->loaderCategoryAttributes());
        $motorcycle = $this->createCategory($motorcyclesGroup, 'Motorcycle', 'motorcycle', 1, $this->motorcycleCategoryAttributes());

        $this->seedVehicles($car, $this->carFleet());
        $this->seedVehicles($van, $this->vanFleet());
        $this->seedVehicles($pickup, $this->pickupFleet());
        $this->seedVehicles($excavator, $this->excavatorFleet());
        $this->seedVehicles($loader, $this->loaderFleet());
        $this->seedVehicles($motorcycle, $this->motorcycleFleet());

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
     * @param  array<int, array<string, mixed>>  $vehicles
     */
    private function seedVehicles(Category $category, array $vehicles): void
    {
        foreach ($vehicles as $vehicle) {
            Vehicle::query()->create([
                'category_id' => $category->id,
                'status' => $vehicle['status'] ?? VehicleStatus::Available,
                'description' => $vehicle['description'] ?? null,
                'requires_license_type' => $vehicle['requires_license_type'] ?? null,
                'available_with_operator' => $vehicle['available_with_operator'] ?? false,
                'operator_daily_rate' => $vehicle['operator_daily_rate'] ?? null,
                'slug' => Vehicle::generateUniqueSlug($vehicle['name']),
                ...collect($vehicle)->except(['status', 'description', 'requires_license_type', 'available_with_operator', 'operator_daily_rate'])->all(),
            ]);
        }
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
     * @return list<array<string, mixed>>
     */
    private function vanCategoryAttributes(): array
    {
        return [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'cargo_volume_m3', 'label' => 'Cargo volume (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'seats', 'label' => 'Seats', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'sliding_doors', 'label' => 'Sliding doors', 'field_type' => CategoryAttributeFieldType::Boolean, 'required' => false, 'sort_order' => 4],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 5],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pickupCategoryAttributes(): array
    {
        return [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'model', 'label' => 'Model', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 2],
            ['key' => 'payload_capacity_kg', 'label' => 'Payload capacity (kg)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'bed_length_m', 'label' => 'Bed length (m)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 4],
            ['key' => 'fuel_type', 'label' => 'Fuel type', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['petrol' => 'Petrol', 'diesel' => 'Diesel', 'electric' => 'Electric'], 'required' => true, 'sort_order' => 5],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 6],
            ['key' => 'transmission', 'label' => 'Transmission', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['manual' => 'Manual', 'automatic' => 'Automatic'], 'required' => true, 'sort_order' => 7],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function excavatorCategoryAttributes(): array
    {
        return [
            ['key' => 'operating_weight_t', 'label' => 'Operating weight (t)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 1],
            ['key' => 'bucket_capacity_m3', 'label' => 'Bucket capacity (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'max_dig_depth_m', 'label' => 'Max dig depth (m)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 4],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loaderCategoryAttributes(): array
    {
        return [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'operating_weight_t', 'label' => 'Operating weight (t)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 2],
            ['key' => 'bucket_capacity_m3', 'label' => 'Bucket capacity (m³)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'lift_height_m', 'label' => 'Lift height (m)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 4],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 5],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function motorcycleCategoryAttributes(): array
    {
        return [
            ['key' => 'make', 'label' => 'Make', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 1],
            ['key' => 'model', 'label' => 'Model', 'field_type' => CategoryAttributeFieldType::Text, 'required' => true, 'sort_order' => 2],
            ['key' => 'engine_displacement', 'label' => 'Engine displacement (cc)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 3],
            ['key' => 'horsepower', 'label' => 'Horsepower (hp)', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 4],
            ['key' => 'fuel_type', 'label' => 'Fuel type', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['petrol' => 'Petrol', 'electric' => 'Electric'], 'required' => true, 'sort_order' => 5],
            ['key' => 'type', 'label' => 'Type', 'field_type' => CategoryAttributeFieldType::Select, 'options' => ['sport' => 'Sport', 'cruiser' => 'Cruiser', 'scooter' => 'Scooter', 'touring' => 'Touring', 'naked' => 'Naked'], 'required' => true, 'sort_order' => 6],
            ['key' => 'seats', 'label' => 'Seats', 'field_type' => CategoryAttributeFieldType::Number, 'required' => true, 'sort_order' => 7],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function carFleet(): array
    {
        return [
            ['name' => 'Toyota Corolla', 'year' => 2023, 'daily_rate' => 45.00, 'weekly_rate' => 280.00, 'monthly_rate' => 950.00, 'deposit_amount' => 400.00, 'specs' => ['make' => 'Toyota', 'model' => 'Corolla', 'body_type' => 'sedan', 'fuel_type' => 'hybrid', 'engine_displacement' => 1798, 'horsepower' => 140, 'transmission' => 'automatic', 'seats' => 5]],
            ['name' => 'BMW 3 Series', 'year' => 2024, 'daily_rate' => 89.00, 'weekly_rate' => 550.00, 'monthly_rate' => 1800.00, 'deposit_amount' => 800.00, 'specs' => ['make' => 'BMW', 'model' => '320i', 'body_type' => 'sedan', 'fuel_type' => 'petrol', 'engine_displacement' => 1998, 'horsepower' => 184, 'transmission' => 'automatic', 'seats' => 5]],
            ['name' => 'Volkswagen Passat', 'year' => 2021, 'daily_rate' => 55.00, 'weekly_rate' => 340.00, 'monthly_rate' => 1100.00, 'deposit_amount' => 500.00, 'status' => VehicleStatus::Maintenance, 'specs' => ['make' => 'Volkswagen', 'model' => 'Passat', 'body_type' => 'sedan', 'fuel_type' => 'diesel', 'engine_displacement' => 1968, 'horsepower' => 150, 'transmission' => 'manual', 'seats' => 5]],
            ['name' => 'Volvo V60', 'year' => 2022, 'daily_rate' => 70.00, 'weekly_rate' => 430.00, 'monthly_rate' => 1400.00, 'deposit_amount' => 600.00, 'specs' => ['make' => 'Volvo', 'model' => 'V60', 'body_type' => 'wagon', 'fuel_type' => 'petrol', 'engine_displacement' => 1969, 'horsepower' => 250, 'transmission' => 'automatic', 'seats' => 5, 'cargo_volume_m3' => 0.54, 'towing_capacity_kg' => 1800, 'roof_rails' => true]],
            ['name' => 'Skoda Octavia Combi', 'year' => 2023, 'daily_rate' => 58.00, 'weekly_rate' => 360.00, 'monthly_rate' => 1150.00, 'deposit_amount' => 500.00, 'status' => VehicleStatus::Rented, 'specs' => ['make' => 'Skoda', 'model' => 'Octavia Combi', 'body_type' => 'wagon', 'fuel_type' => 'petrol', 'engine_displacement' => 1498, 'horsepower' => 150, 'transmission' => 'automatic', 'seats' => 5, 'cargo_volume_m3' => 0.64, 'towing_capacity_kg' => 1500, 'roof_rails' => true]],
            ['name' => 'Audi A4', 'year' => 2023, 'daily_rate' => 82.00, 'weekly_rate' => 500.00, 'monthly_rate' => 1650.00, 'deposit_amount' => 700.00, 'specs' => ['make' => 'Audi', 'model' => 'A4', 'body_type' => 'sedan', 'fuel_type' => 'diesel', 'engine_displacement' => 1968, 'horsepower' => 190, 'transmission' => 'automatic', 'seats' => 5]],
            ['name' => 'Mercedes-Benz C-Class', 'year' => 2024, 'daily_rate' => 96.00, 'weekly_rate' => 590.00, 'monthly_rate' => 1950.00, 'deposit_amount' => 850.00, 'specs' => ['make' => 'Mercedes-Benz', 'model' => 'C 200', 'body_type' => 'sedan', 'fuel_type' => 'petrol', 'engine_displacement' => 1496, 'horsepower' => 204, 'transmission' => 'automatic', 'seats' => 5]],
            ['name' => 'Ford Focus', 'year' => 2022, 'daily_rate' => 48.00, 'weekly_rate' => 295.00, 'monthly_rate' => 980.00, 'deposit_amount' => 400.00, 'specs' => ['make' => 'Ford', 'model' => 'Focus', 'body_type' => 'hatchback', 'fuel_type' => 'petrol', 'engine_displacement' => 1496, 'horsepower' => 125, 'transmission' => 'manual', 'seats' => 5]],
            ['name' => 'Honda Civic', 'year' => 2023, 'daily_rate' => 52.00, 'weekly_rate' => 320.00, 'monthly_rate' => 1050.00, 'deposit_amount' => 450.00, 'specs' => ['make' => 'Honda', 'model' => 'Civic', 'body_type' => 'hatchback', 'fuel_type' => 'hybrid', 'engine_displacement' => 1993, 'horsepower' => 181, 'transmission' => 'automatic', 'seats' => 5]],
            ['name' => 'Hyundai i30', 'year' => 2022, 'daily_rate' => 46.00, 'weekly_rate' => 285.00, 'monthly_rate' => 940.00, 'deposit_amount' => 400.00, 'specs' => ['make' => 'Hyundai', 'model' => 'i30', 'body_type' => 'hatchback', 'fuel_type' => 'petrol', 'engine_displacement' => 1591, 'horsepower' => 123, 'transmission' => 'manual', 'seats' => 5]],
            ['name' => 'BMW X3', 'year' => 2024, 'daily_rate' => 98.00, 'weekly_rate' => 610.00, 'monthly_rate' => 2000.00, 'deposit_amount' => 900.00, 'specs' => ['make' => 'BMW', 'model' => 'X3 xDrive20d', 'body_type' => 'suv', 'fuel_type' => 'diesel', 'engine_displacement' => 1995, 'horsepower' => 190, 'transmission' => 'automatic', 'seats' => 5, 'towing_capacity_kg' => 2400, 'roof_rails' => true]],
            ['name' => 'Audi Q5', 'year' => 2023, 'daily_rate' => 105.00, 'weekly_rate' => 650.00, 'monthly_rate' => 2150.00, 'deposit_amount' => 950.00, 'specs' => ['make' => 'Audi', 'model' => 'Q5 40 TDI', 'body_type' => 'suv', 'fuel_type' => 'diesel', 'engine_displacement' => 1968, 'horsepower' => 204, 'transmission' => 'automatic', 'seats' => 5, 'towing_capacity_kg' => 2400, 'roof_rails' => true]],
            ['name' => 'Toyota RAV4', 'year' => 2024, 'daily_rate' => 78.00, 'weekly_rate' => 480.00, 'monthly_rate' => 1580.00, 'deposit_amount' => 650.00, 'specs' => ['make' => 'Toyota', 'model' => 'RAV4 Hybrid', 'body_type' => 'suv', 'fuel_type' => 'hybrid', 'engine_displacement' => 2487, 'horsepower' => 222, 'transmission' => 'automatic', 'seats' => 5, 'towing_capacity_kg' => 1650, 'roof_rails' => true]],
            ['name' => 'Volkswagen Golf', 'year' => 2022, 'daily_rate' => 50.00, 'weekly_rate' => 310.00, 'monthly_rate' => 1020.00, 'deposit_amount' => 420.00, 'specs' => ['make' => 'Volkswagen', 'model' => 'Golf', 'body_type' => 'hatchback', 'fuel_type' => 'petrol', 'engine_displacement' => 1498, 'horsepower' => 150, 'transmission' => 'manual', 'seats' => 5]],
            ['name' => 'Mercedes-Benz E-Class', 'year' => 2024, 'daily_rate' => 115.00, 'weekly_rate' => 710.00, 'monthly_rate' => 2350.00, 'deposit_amount' => 1000.00, 'specs' => ['make' => 'Mercedes-Benz', 'model' => 'E 220d', 'body_type' => 'sedan', 'fuel_type' => 'diesel', 'engine_displacement' => 1993, 'horsepower' => 194, 'transmission' => 'automatic', 'seats' => 5]],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vanFleet(): array
    {
        return [
            ['name' => 'Ford Transit L3H2', 'year' => 2023, 'daily_rate' => 75.00, 'weekly_rate' => 460.00, 'monthly_rate' => 1500.00, 'deposit_amount' => 600.00, 'status' => VehicleStatus::Maintenance, 'specs' => ['make' => 'Ford', 'cargo_volume_m3' => 9.5, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 170]],
            ['name' => 'Mercedes Sprinter 314', 'year' => 2024, 'daily_rate' => 95.00, 'weekly_rate' => 580.00, 'monthly_rate' => 1900.00, 'deposit_amount' => 750.00, 'specs' => ['make' => 'Mercedes-Benz', 'cargo_volume_m3' => 11.0, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 190]],
            ['name' => 'Renault Master L3H2', 'year' => 2022, 'daily_rate' => 68.00, 'weekly_rate' => 420.00, 'monthly_rate' => 1380.00, 'deposit_amount' => 550.00, 'specs' => ['make' => 'Renault', 'cargo_volume_m3' => 10.8, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 150]],
            ['name' => 'Iveco Daily 35S14', 'year' => 2023, 'daily_rate' => 72.00, 'weekly_rate' => 445.00, 'monthly_rate' => 1460.00, 'deposit_amount' => 580.00, 'specs' => ['make' => 'Iveco', 'cargo_volume_m3' => 12.4, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 136]],
            ['name' => 'Volkswagen Crafter 35', 'year' => 2024, 'daily_rate' => 88.00, 'weekly_rate' => 540.00, 'monthly_rate' => 1780.00, 'deposit_amount' => 700.00, 'specs' => ['make' => 'Volkswagen', 'cargo_volume_m3' => 11.3, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 177]],
            ['name' => 'Ford Transit Custom', 'year' => 2023, 'daily_rate' => 70.00, 'weekly_rate' => 430.00, 'monthly_rate' => 1420.00, 'deposit_amount' => 560.00, 'specs' => ['make' => 'Ford', 'cargo_volume_m3' => 6.8, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 136]],
            ['name' => 'Mercedes Sprinter 316 LWB', 'year' => 2022, 'daily_rate' => 99.00, 'weekly_rate' => 610.00, 'monthly_rate' => 2000.00, 'deposit_amount' => 780.00, 'specs' => ['make' => 'Mercedes-Benz', 'cargo_volume_m3' => 14.0, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 163]],
            ['name' => 'Renault Master L4H3', 'year' => 2024, 'daily_rate' => 74.00, 'weekly_rate' => 455.00, 'monthly_rate' => 1495.00, 'deposit_amount' => 590.00, 'specs' => ['make' => 'Renault', 'cargo_volume_m3' => 13.2, 'seats' => 3, 'sliding_doors' => true, 'horsepower' => 165]],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pickupFleet(): array
    {
        return [
            ['name' => 'Ford Ranger Wildtrak', 'year' => 2024, 'daily_rate' => 92.00, 'weekly_rate' => 565.00, 'monthly_rate' => 1860.00, 'deposit_amount' => 750.00, 'specs' => ['make' => 'Ford', 'model' => 'Ranger Wildtrak', 'payload_capacity_kg' => 1000, 'bed_length_m' => 1.54, 'fuel_type' => 'diesel', 'horsepower' => 205, 'transmission' => 'automatic']],
            ['name' => 'Toyota Hilux Invincible', 'year' => 2023, 'daily_rate' => 88.00, 'weekly_rate' => 540.00, 'monthly_rate' => 1780.00, 'deposit_amount' => 720.00, 'specs' => ['make' => 'Toyota', 'model' => 'Hilux Invincible', 'payload_capacity_kg' => 1050, 'bed_length_m' => 1.52, 'fuel_type' => 'diesel', 'horsepower' => 204, 'transmission' => 'automatic']],
            ['name' => 'Volkswagen Amarok Aventura', 'year' => 2024, 'daily_rate' => 95.00, 'weekly_rate' => 585.00, 'monthly_rate' => 1920.00, 'deposit_amount' => 760.00, 'specs' => ['make' => 'Volkswagen', 'model' => 'Amarok Aventura', 'payload_capacity_kg' => 980, 'bed_length_m' => 1.55, 'fuel_type' => 'diesel', 'horsepower' => 240, 'transmission' => 'automatic']],
            ['name' => 'Isuzu D-Max V-Cross', 'year' => 2023, 'daily_rate' => 85.00, 'weekly_rate' => 525.00, 'monthly_rate' => 1720.00, 'deposit_amount' => 700.00, 'specs' => ['make' => 'Isuzu', 'model' => 'D-Max V-Cross', 'payload_capacity_kg' => 1100, 'bed_length_m' => 1.48, 'fuel_type' => 'diesel', 'horsepower' => 190, 'transmission' => 'automatic']],
            ['name' => 'Ford Ranger XLT', 'year' => 2022, 'daily_rate' => 80.00, 'weekly_rate' => 495.00, 'monthly_rate' => 1620.00, 'deposit_amount' => 680.00, 'specs' => ['make' => 'Ford', 'model' => 'Ranger XLT', 'payload_capacity_kg' => 1020, 'bed_length_m' => 1.54, 'fuel_type' => 'diesel', 'horsepower' => 170, 'transmission' => 'manual']],
            ['name' => 'Toyota Hilux Active', 'year' => 2022, 'daily_rate' => 78.00, 'weekly_rate' => 480.00, 'monthly_rate' => 1580.00, 'deposit_amount' => 650.00, 'specs' => ['make' => 'Toyota', 'model' => 'Hilux Active', 'payload_capacity_kg' => 1030, 'bed_length_m' => 1.52, 'fuel_type' => 'diesel', 'horsepower' => 150, 'transmission' => 'manual']],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function excavatorFleet(): array
    {
        return [
            ['name' => 'CAT 320 GC', 'year' => 2022, 'daily_rate' => 350.00, 'weekly_rate' => 2100.00, 'monthly_rate' => 7000.00, 'deposit_amount' => 3000.00, 'available_with_operator' => true, 'operator_daily_rate' => 180.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 22.0, 'bucket_capacity_m3' => 1.2, 'max_dig_depth_m' => 6.7, 'horsepower' => 160]],
            ['name' => 'Komatsu PC210-11', 'year' => 2023, 'daily_rate' => 340.00, 'weekly_rate' => 2050.00, 'monthly_rate' => 6800.00, 'deposit_amount' => 2900.00, 'available_with_operator' => true, 'operator_daily_rate' => 175.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 21.5, 'bucket_capacity_m3' => 1.0, 'max_dig_depth_m' => 6.5, 'horsepower' => 158]],
            ['name' => 'Volvo EC220E', 'year' => 2024, 'daily_rate' => 365.00, 'weekly_rate' => 2200.00, 'monthly_rate' => 7300.00, 'deposit_amount' => 3100.00, 'available_with_operator' => false, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 22.8, 'bucket_capacity_m3' => 1.1, 'max_dig_depth_m' => 6.8, 'horsepower' => 173]],
            ['name' => 'JCB JS220 LC', 'year' => 2023, 'daily_rate' => 330.00, 'weekly_rate' => 1980.00, 'monthly_rate' => 6600.00, 'deposit_amount' => 2800.00, 'available_with_operator' => true, 'operator_daily_rate' => 165.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 21.8, 'bucket_capacity_m3' => 1.05, 'max_dig_depth_m' => 6.6, 'horsepower' => 172]],
            ['name' => 'Liebherr R 920', 'year' => 2022, 'daily_rate' => 355.00, 'weekly_rate' => 2130.00, 'monthly_rate' => 7100.00, 'deposit_amount' => 3000.00, 'available_with_operator' => false, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 20.5, 'bucket_capacity_m3' => 0.95, 'max_dig_depth_m' => 6.4, 'horsepower' => 152]],
            ['name' => 'CAT 323', 'year' => 2024, 'daily_rate' => 380.00, 'weekly_rate' => 2280.00, 'monthly_rate' => 7600.00, 'deposit_amount' => 3200.00, 'status' => VehicleStatus::Maintenance, 'available_with_operator' => true, 'operator_daily_rate' => 190.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['operating_weight_t' => 24.0, 'bucket_capacity_m3' => 1.3, 'max_dig_depth_m' => 7.0, 'horsepower' => 178]],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loaderFleet(): array
    {
        return [
            ['name' => 'CAT 950M', 'year' => 2023, 'daily_rate' => 390.00, 'weekly_rate' => 2340.00, 'monthly_rate' => 7800.00, 'deposit_amount' => 3300.00, 'available_with_operator' => true, 'operator_daily_rate' => 195.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['make' => 'CAT', 'operating_weight_t' => 18.5, 'bucket_capacity_m3' => 3.3, 'lift_height_m' => 3.4, 'horsepower' => 230]],
            ['name' => 'Komatsu WA320-8', 'year' => 2022, 'daily_rate' => 370.00, 'weekly_rate' => 2220.00, 'monthly_rate' => 7400.00, 'deposit_amount' => 3100.00, 'available_with_operator' => true, 'operator_daily_rate' => 185.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['make' => 'Komatsu', 'operating_weight_t' => 17.2, 'bucket_capacity_m3' => 3.0, 'lift_height_m' => 3.2, 'horsepower' => 171]],
            ['name' => 'Volvo L120H', 'year' => 2024, 'daily_rate' => 400.00, 'weekly_rate' => 2400.00, 'monthly_rate' => 8000.00, 'deposit_amount' => 3400.00, 'available_with_operator' => false, 'requires_license_type' => 'heavy machinery', 'specs' => ['make' => 'Volvo CE', 'operating_weight_t' => 19.8, 'bucket_capacity_m3' => 3.5, 'lift_height_m' => 3.6, 'horsepower' => 245]],
            ['name' => 'JCB 457', 'year' => 2023, 'daily_rate' => 360.00, 'weekly_rate' => 2160.00, 'monthly_rate' => 7200.00, 'deposit_amount' => 3000.00, 'available_with_operator' => true, 'operator_daily_rate' => 180.00, 'requires_license_type' => 'heavy machinery', 'specs' => ['make' => 'JCB', 'operating_weight_t' => 16.5, 'bucket_capacity_m3' => 2.8, 'lift_height_m' => 3.1, 'horsepower' => 155]],
            ['name' => 'Liebherr L 566', 'year' => 2022, 'daily_rate' => 385.00, 'weekly_rate' => 2310.00, 'monthly_rate' => 7700.00, 'deposit_amount' => 3250.00, 'available_with_operator' => false, 'requires_license_type' => 'heavy machinery', 'specs' => ['make' => 'Liebherr', 'operating_weight_t' => 20.2, 'bucket_capacity_m3' => 3.6, 'lift_height_m' => 3.7, 'horsepower' => 250]],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function motorcycleFleet(): array
    {
        return [
            ['name' => 'Honda CB650R', 'year' => 2024, 'daily_rate' => 42.00, 'weekly_rate' => 260.00, 'monthly_rate' => 850.00, 'deposit_amount' => 350.00, 'requires_license_type' => 'A2 motorcycle', 'specs' => ['make' => 'Honda', 'model' => 'CB650R', 'engine_displacement' => 649, 'horsepower' => 95, 'fuel_type' => 'petrol', 'type' => 'naked', 'seats' => 2]],
            ['name' => 'Yamaha MT-07', 'year' => 2023, 'daily_rate' => 40.00, 'weekly_rate' => 248.00, 'monthly_rate' => 820.00, 'deposit_amount' => 340.00, 'requires_license_type' => 'A2 motorcycle', 'specs' => ['make' => 'Yamaha', 'model' => 'MT-07', 'engine_displacement' => 689, 'horsepower' => 73, 'fuel_type' => 'petrol', 'type' => 'naked', 'seats' => 2]],
            ['name' => 'Kawasaki Ninja 650', 'year' => 2024, 'daily_rate' => 45.00, 'weekly_rate' => 278.00, 'monthly_rate' => 910.00, 'deposit_amount' => 360.00, 'requires_license_type' => 'A2 motorcycle', 'specs' => ['make' => 'Kawasaki', 'model' => 'Ninja 650', 'engine_displacement' => 649, 'horsepower' => 68, 'fuel_type' => 'petrol', 'type' => 'sport', 'seats' => 2]],
            ['name' => 'Suzuki GSX-S750', 'year' => 2022, 'daily_rate' => 48.00, 'weekly_rate' => 295.00, 'monthly_rate' => 960.00, 'deposit_amount' => 380.00, 'requires_license_type' => 'A motorcycle', 'specs' => ['make' => 'Suzuki', 'model' => 'GSX-S750', 'engine_displacement' => 749, 'horsepower' => 114, 'fuel_type' => 'petrol', 'type' => 'sport', 'seats' => 2]],
            ['name' => 'KTM 390 Duke', 'year' => 2023, 'daily_rate' => 35.00, 'weekly_rate' => 215.00, 'monthly_rate' => 700.00, 'deposit_amount' => 300.00, 'requires_license_type' => 'A2 motorcycle', 'specs' => ['make' => 'KTM', 'model' => '390 Duke', 'engine_displacement' => 373, 'horsepower' => 44, 'fuel_type' => 'petrol', 'type' => 'naked', 'seats' => 2]],
            ['name' => 'Harley-Davidson Iron 883', 'year' => 2021, 'daily_rate' => 55.00, 'weekly_rate' => 340.00, 'monthly_rate' => 1100.00, 'deposit_amount' => 450.00, 'status' => VehicleStatus::Rented, 'requires_license_type' => 'A motorcycle', 'specs' => ['make' => 'Harley-Davidson', 'model' => 'Iron 883', 'engine_displacement' => 883, 'horsepower' => 52, 'fuel_type' => 'petrol', 'type' => 'cruiser', 'seats' => 2]],
            ['name' => 'BMW R 1250 GS', 'year' => 2024, 'daily_rate' => 62.00, 'weekly_rate' => 385.00, 'monthly_rate' => 1260.00, 'deposit_amount' => 500.00, 'requires_license_type' => 'A motorcycle', 'specs' => ['make' => 'BMW Motorrad', 'model' => 'R 1250 GS', 'engine_displacement' => 1254, 'horsepower' => 136, 'fuel_type' => 'petrol', 'type' => 'touring', 'seats' => 2]],
            ['name' => 'Honda PCX 125', 'year' => 2023, 'daily_rate' => 28.00, 'weekly_rate' => 172.00, 'monthly_rate' => 560.00, 'deposit_amount' => 200.00, 'requires_license_type' => 'AM scooter', 'specs' => ['make' => 'Honda', 'model' => 'PCX 125', 'engine_displacement' => 125, 'horsepower' => 12, 'fuel_type' => 'petrol', 'type' => 'scooter', 'seats' => 2]],
            ['name' => 'Yamaha Tracer 9', 'year' => 2024, 'daily_rate' => 58.00, 'weekly_rate' => 358.00, 'monthly_rate' => 1170.00, 'deposit_amount' => 480.00, 'requires_license_type' => 'A motorcycle', 'specs' => ['make' => 'Yamaha', 'model' => 'Tracer 9', 'engine_displacement' => 890, 'horsepower' => 119, 'fuel_type' => 'petrol', 'type' => 'touring', 'seats' => 2]],
            ['name' => 'Kawasaki Versys 650', 'year' => 2022, 'daily_rate' => 46.00, 'weekly_rate' => 284.00, 'monthly_rate' => 930.00, 'deposit_amount' => 370.00, 'requires_license_type' => 'A2 motorcycle', 'specs' => ['make' => 'Kawasaki', 'model' => 'Versys 650', 'engine_displacement' => 649, 'horsepower' => 70, 'fuel_type' => 'petrol', 'type' => 'touring', 'seats' => 2]],
        ];
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
