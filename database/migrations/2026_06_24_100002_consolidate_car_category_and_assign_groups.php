<?php

use App\Enums\CategoryAttributeFieldType;
use App\Models\Category;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sedan = Category::query()->where('slug', 'sedan')->first();

        if ($sedan === null) {
            return;
        }

        $carsGroup = VehicleGroup::query()->firstOrCreate(
            ['slug' => 'cars'],
            ['name' => 'Cars', 'sort_order' => 1],
        );

        $vansGroup = VehicleGroup::query()->firstOrCreate(
            ['slug' => 'vans'],
            ['name' => 'Vans', 'sort_order' => 2],
        );

        $machineryGroup = VehicleGroup::query()->firstOrCreate(
            ['slug' => 'machinery'],
            ['name' => 'Machinery', 'sort_order' => 3],
        );

        $wagon = Category::query()->where('slug', 'wagon')->first();

        if ($wagon !== null) {
            Vehicle::query()
                ->where('category_id', $wagon->id)
                ->update(['category_id' => $sedan->id]);

            $wagon->categoryAttributes()->delete();
            $wagon->delete();
        }

        $sedan->update([
            'name' => 'Car',
            'slug' => 'car',
        ]);

        $sedan->categoryAttributes()->delete();

        foreach ($this->carCategoryAttributes() as $attribute) {
            $sedan->categoryAttributes()->create($attribute);
        }

        $this->backfillCarVehicleSpecs($sedan);

        $van = Category::query()->where('slug', 'van')->first();

        if ($van !== null) {
            $this->ensureHorsepowerAttribute($van, sortOrder: 5);

            foreach ($this->vanHorsepowerByName() as $name => $horsepower) {
                $this->mergeVehicleSpec($van, $name, ['horsepower' => $horsepower]);
            }

            $van->update(['group_id' => $vansGroup->id]);
        }

        $excavator = Category::query()->where('slug', 'excavator')->first();

        if ($excavator !== null) {
            $this->ensureHorsepowerAttribute($excavator, sortOrder: 4);

            foreach ($this->excavatorHorsepowerByName() as $name => $horsepower) {
                $this->mergeVehicleSpec($excavator, $name, ['horsepower' => $horsepower]);
            }

            $excavator->update(['group_id' => $machineryGroup->id]);
        }

        $sedan->update(['group_id' => $carsGroup->id]);
    }

    public function down(): void
    {
        // Irreversible data migration.
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

    private function backfillCarVehicleSpecs(Category $car): void
    {
        $sedanBackfill = [
            'Toyota Corolla' => [
                'body_type' => 'sedan',
                'engine_displacement' => 1798,
                'horsepower' => 140,
            ],
            'BMW 3 Series' => [
                'body_type' => 'sedan',
                'engine_displacement' => 1998,
                'horsepower' => 184,
            ],
            'Volkswagen Passat' => [
                'body_type' => 'sedan',
                'engine_displacement' => 1968,
                'horsepower' => 150,
            ],
        ];

        $wagonBackfill = [
            'Volvo V60' => [
                'model' => 'V60',
                'body_type' => 'wagon',
                'fuel_type' => 'petrol',
                'engine_displacement' => 1969,
                'horsepower' => 250,
                'transmission' => 'automatic',
                'seats' => 5,
            ],
            'Skoda Octavia Combi' => [
                'model' => 'Octavia Combi',
                'body_type' => 'wagon',
                'fuel_type' => 'petrol',
                'engine_displacement' => 1498,
                'horsepower' => 150,
                'transmission' => 'automatic',
                'seats' => 5,
            ],
        ];

        foreach ($sedanBackfill as $name => $specs) {
            $this->mergeVehicleSpec($car, $name, $specs);
        }

        foreach ($wagonBackfill as $name => $specs) {
            $this->mergeVehicleSpec($car, $name, $specs);
        }
    }

    /**
     * @return array<string, int>
     */
    private function vanHorsepowerByName(): array
    {
        return [
            'Ford Transit' => 170,
            'Mercedes Sprinter' => 190,
            'Renault Trafic' => 145,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function excavatorHorsepowerByName(): array
    {
        return [
            'CAT 320' => 160,
            'Komatsu PC210' => 158,
        ];
    }

    private function ensureHorsepowerAttribute(Category $category, int $sortOrder): void
    {
        if ($category->categoryAttributes()->where('key', 'horsepower')->exists()) {
            return;
        }

        $category->categoryAttributes()->create([
            'key' => 'horsepower',
            'label' => 'Horsepower (hp)',
            'field_type' => CategoryAttributeFieldType::Number,
            'required' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  array<string, mixed>  $specs
     */
    private function mergeVehicleSpec(Category $category, string $name, array $specs): void
    {
        $vehicle = Vehicle::query()
            ->where('category_id', $category->id)
            ->where('name', $name)
            ->first();

        if ($vehicle === null) {
            return;
        }

        $vehicle->update([
            'specs' => array_merge($vehicle->specs ?? [], $specs),
        ]);
    }
};
