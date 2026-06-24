<?php

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\MaintenanceBlock;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

function availabilityVehicle(int $bufferHours = 0): Vehicle
{
    $category = Category::factory()->create(['buffer_hours' => $bufferHours]);

    return Vehicle::factory()->for($category)->create();
}

it('reports available when there are no conflicts', function () {
    $vehicle = availabilityVehicle();

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
    ))->toBeTrue();
});

it('reports unavailable on exact booking overlap', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-10'),
    ))->toBeFalse();
});

it('reports unavailable when candidate start falls inside existing booking', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-15'),
    ))->toBeFalse();
});

it('reports unavailable when candidate end falls inside existing booking', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-20',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-15'),
    ))->toBeFalse();
});

it('reports unavailable when candidate fully encompasses existing booking', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-20'),
    ))->toBeFalse();
});

it('reports unavailable for back-to-back bookings within buffer hours', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-05',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-10'),
    ))->toBeFalse();
});

it('reports available for back-to-back bookings beyond buffer hours', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-05',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-06'),
        Carbon::parse('2026-06-10'),
    ))->toBeTrue();
});

it('reports unavailable when candidate ends shortly before a future booking within buffer hours', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-15',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-10'),
    ))->toBeFalse();
});

it('reports available when candidate ends with exactly the required buffer before a future booking', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-15',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-09'),
    ))->toBeTrue();
});

it('reports unavailable when overlapping a maintenance block', function () {
    $vehicle = availabilityVehicle();

    MaintenanceBlock::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-07'),
        Carbon::parse('2026-06-12'),
    ))->toBeFalse();
});

it('does not block availability for completed or cancelled bookings', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->completed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-10',
    ]);

    Booking::factory()->cancelled()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-20',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-20'),
    ))->toBeTrue();
});

it('reports unavailable when vehicle status is not available', function () {
    $vehicle = availabilityVehicle();
    $vehicle->update(['status' => VehicleStatus::Maintenance]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
    ))->toBeFalse();
});

it('blocks pending and active bookings but not completed ones', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-07-05'),
        Carbon::parse('2026-07-15'),
    ))->toBeFalse();

    $vehicle->bookings()->update(['status' => BookingStatus::Completed]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-07-05'),
        Carbon::parse('2026-07-15'),
    ))->toBeTrue();
});
