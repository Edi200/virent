<?php

use App\Enums\VehicleStatus;
use App\Models\Category;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('lists only available vehicles on the home fleet listing', function () {
    $maintenanceVehicle = Vehicle::query()->firstOrFail();
    $maintenanceVehicle->update(['status' => VehicleStatus::Maintenance]);

    $availableCount = Vehicle::query()->where('status', VehicleStatus::Available)->count();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('vehicles.data', $availableCount)
        );
});

it('redirects fleet index to home preserving query string', function () {
    $this->get(route('fleet.index', ['category' => 'sedan', 'search' => 'Toyota']))
        ->assertRedirect(route('home', ['category' => 'sedan', 'search' => 'Toyota']));
});

it('filters the fleet listing by category slug', function () {
    $this->get(route('home', ['category' => 'sedan']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('filters.category', 'sedan')
            ->has('vehicles.data', 3)
            ->etc()
        );
});

it('filters the fleet listing by price range', function () {
    $this->get(route('home', ['price_min' => 80, 'price_max' => 100]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('filters.price_min', '80')
            ->where('filters.price_max', '100')
            ->has('vehicles.data', 2)
            ->where('vehicles.data', fn ($vehicles) => collect($vehicles)->every(
                fn ($vehicle) => (float) $vehicle['daily_rate'] >= 80
                    && (float) $vehicle['daily_rate'] <= 100,
            ))
        );
});

it('filters the fleet listing by search term on vehicle name', function () {
    $this->get(route('home', ['search' => 'Toyota']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('filters.search', 'Toyota')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.name', 'Toyota Corolla')
        );
});

it('combines category search and attribute filters', function () {
    $this->get(route('home', [
        'category' => 'sedan',
        'search' => 'BMW',
        'attrs' => [
            'fuel_type' => 'petrol',
            'seats' => ['min' => 5],
        ],
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('filters.category', 'sedan')
            ->where('filters.search', 'BMW')
            ->where('filters.attrs.fuel_type', 'petrol')
            ->where('filters.attrs.seats.min', '5')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.name', 'BMW 3 Series')
        );
});

it('returns filter attribute metadata when a category is selected', function () {
    $this->get(route('home', ['category' => 'sedan']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('filterAttributes', 5)
            ->where('filterAttributes', fn ($attributes) => collect($attributes)->contains(
                fn ($attribute) => $attribute['key'] === 'make'
                    && $attribute['field_type'] === 'text'
                    && collect($attribute['options'])->sort()->values()->all() === ['BMW', 'Toyota', 'Volkswagen'],
            ))
            ->where('filterAttributes', fn ($attributes) => collect($attributes)->contains(
                fn ($attribute) => $attribute['key'] === 'fuel_type'
                    && $attribute['field_type'] === 'select'
                    && $attribute['options'] === [
                        'petrol' => 'Petrol',
                        'diesel' => 'Diesel',
                        'hybrid' => 'Hybrid',
                        'electric' => 'Electric',
                    ],
            ))
            ->where('filterAttributes', fn ($attributes) => collect($attributes)->contains(
                fn ($attribute) => $attribute['key'] === 'seats'
                    && $attribute['field_type'] === 'number'
                    && (float) $attribute['bounds']['min'] === 5.0
                    && (float) $attribute['bounds']['max'] === 5.0,
            ))
        );
});

it('scopes model filter options to the selected make', function () {
    $this->get(route('home', [
        'category' => 'sedan',
        'attrs' => ['make' => 'Toyota'],
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('filterAttributes', fn ($attributes) => collect($attributes)->contains(
                fn ($attribute) => $attribute['key'] === 'model'
                    && $attribute['depends_on'] === 'make'
                    && $attribute['options'] === ['Corolla'],
            ))
        );
});

it('returns price bounds for the fleet listing', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('priceBounds.min')
            ->has('priceBounds.max')
            ->where('priceBounds', fn ($bounds) => (float) data_get($bounds, 'min') > 0
                && (float) data_get($bounds, 'max') > (float) data_get($bounds, 'min'))
        );
});

it('returns empty filter attributes when no category is selected', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('filterAttributes', 0)
        );
});

it('ignores attribute query params when no category is selected', function () {
    $this->get(route('home', ['attrs' => ['make' => 'Toyota']]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('filterAttributes', 0)
            ->where('filters.attrs', [])
        );
});

it('returns full category price bounds when attribute filters are active', function () {
    $this->get(route('home', [
        'category' => 'sedan',
        'attrs' => ['make' => 'Toyota'],
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('priceBounds', fn ($bounds) => (float) data_get($bounds, 'min') === 45.0
                && (float) data_get($bounds, 'max') === 89.0)
        );
});

it('returns vehicles in laravel flat paginator shape', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('vehicles.data')
            ->has('vehicles.last_page')
            ->has('vehicles.links')
            ->has('vehicles.prev_page_url')
            ->has('vehicles.next_page_url')
            ->missing('vehicles.meta')
            ->where('vehicles.last_page', 1)
        );
});

it('returns a single-page empty result for category filters with no vehicles', function () {
    $this->get(route('home', ['category' => 'sedan']))
        ->assertSuccessful();

    Vehicle::query()->whereHas('category', fn ($query) => $query->where('slug', 'sedan'))
        ->update(['status' => VehicleStatus::Maintenance]);

    $this->get(route('home', ['category' => 'sedan']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 0)
            ->where('vehicles.last_page', 1)
            ->where('vehicles.total', 0)
        );
});

it('includes multiple pagination pages when results exceed the page size', function () {
    $category = Category::query()->where('slug', 'sedan')->firstOrFail();

    for ($i = 0; $i < 10; $i++) {
        Vehicle::factory()->create([
            'category_id' => $category->id,
            'name' => "Extra Sedan {$i}",
            'slug' => null,
        ]);
    }

    $this->get(route('home', ['category' => 'sedan']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('vehicles.last_page', fn ($lastPage) => $lastPage > 1)
            ->where('vehicles.links', fn ($links) => collect($links)->contains(
                fn ($link) => $link['label'] === '2' && $link['url'] !== null,
            ))
            ->where('vehicles.next_page_url', fn ($url) => $url !== null)
        );
});

it('returns enriched specs with formatted select and boolean values on show', function () {
    $vehicle = Vehicle::query()
        ->where('name', 'Toyota Corolla')
        ->firstOrFail();

    $this->get(route('fleet.show', $vehicle))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Fleet/Show')
            ->has('vehicle.specs', 5)
            ->where('vehicle.specs.0', fn ($spec) => $spec['key'] === 'make'
                && $spec['label'] === 'Make'
                && $spec['value'] === 'Toyota')
            ->where('vehicle.specs.2', fn ($spec) => $spec['key'] === 'fuel_type'
                && $spec['label'] === 'Fuel type'
                && $spec['value'] === 'Hybrid')
            ->where('vehicle.specs.4', fn ($spec) => $spec['key'] === 'transmission'
                && $spec['label'] === 'Transmission'
                && $spec['value'] === 'Automatic')
        );
});

it('returns boolean specs as yes or no on show', function () {
    $vehicle = Vehicle::query()
        ->where('name', 'Ford Transit')
        ->firstOrFail();

    $this->get(route('fleet.show', $vehicle))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Fleet/Show')
            ->where('vehicle.specs', fn ($specs) => collect($specs)->contains(
                fn ($spec) => $spec['key'] === 'sliding_doors'
                    && $spec['label'] === 'Sliding doors'
                    && $spec['value'] === 'Yes',
            ))
        );
});

it('returns 404 for an unknown vehicle slug', function () {
    $this->get(route('fleet.show', ['vehicle' => 'nonexistent-slug']))
        ->assertNotFound();
});

it('returns 404 when showing a non-available vehicle by slug', function () {
    $vehicle = Vehicle::query()->firstOrFail();
    $vehicle->update(['status' => VehicleStatus::Rented]);

    $this->get(route('fleet.show', $vehicle))
        ->assertNotFound();
});

it('redirects guests to login with intended url when booking', function () {
    $vehicle = Vehicle::query()
        ->where('name', 'Toyota Corolla')
        ->firstOrFail();

    $response = $this->get(route('fleet.book', $vehicle));

    $response->assertRedirect(route('login'));
    expect(session('url.intended'))->toBe(route('fleet.show', $vehicle));
});

it('generates unique slug suffixes for vehicles with the same name', function () {
    $category = Category::query()->where('slug', 'sedan')->firstOrFail();

    $first = Vehicle::factory()->create([
        'category_id' => $category->id,
        'name' => 'Collision Test Vehicle',
        'slug' => null,
    ]);

    $second = Vehicle::factory()->create([
        'category_id' => $category->id,
        'name' => 'Collision Test Vehicle',
        'slug' => null,
    ]);

    expect($first->slug)->toBe('collision-test-vehicle')
        ->and($second->slug)->toBe('collision-test-vehicle-2');
});
