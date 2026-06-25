<?php

use App\Events\VehicleAvailabilityChanged;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Event;

function holdVehicle(): Vehicle
{
    return Vehicle::factory()->create();
}

function holdDates(int $startOffsetDays = 7, int $durationDays = 3): array
{
    $start = now()->addDays($startOffsetDays)->startOfDay();

    return [
        'start_date' => $start->toDateString(),
        'end_date' => $start->copy()->addDays($durationDays)->toDateString(),
    ];
}

it('creates or refreshes a hold and dispatches VehicleAvailabilityChanged', function () {
    Event::fake([VehicleAvailabilityChanged::class]);

    $user = User::factory()->create();
    $vehicle = holdVehicle();
    $dates = holdDates();

    $this->actingAs($user)
        ->postJson(route('fleet.hold.store', $vehicle), $dates)
        ->assertSuccessful()
        ->assertJson([
            'start_date' => $dates['start_date'],
            'end_date' => $dates['end_date'],
        ]);

    expect(BookingHold::query()->count())->toBe(1);

    $originalExpiresAt = BookingHold::query()->value('expires_at');

    $this->travel(5)->minutes();

    $refreshedDates = holdDates(startOffsetDays: 14);

    $this->actingAs($user)
        ->postJson(route('fleet.hold.store', $vehicle), $refreshedDates)
        ->assertSuccessful()
        ->assertJson([
            'start_date' => $refreshedDates['start_date'],
            'end_date' => $refreshedDates['end_date'],
        ]);

    expect(BookingHold::query()->count())->toBe(1);

    $hold = BookingHold::query()->first();

    expect($hold)->not->toBeNull()
        ->and($hold->start_date->toDateString())->toBe($refreshedDates['start_date'])
        ->and($hold->end_date->toDateString())->toBe($refreshedDates['end_date'])
        ->and($hold->expires_at->greaterThan($originalExpiresAt))->toBeTrue();

    Event::assertDispatched(VehicleAvailabilityChanged::class, fn (VehicleAvailabilityChanged $event): bool => $event->vehicleId === $vehicle->id);
    Event::assertDispatchedTimes(VehicleAvailabilityChanged::class, 2);
});

it('rejects hold creation when dates are unavailable without dispatching an event', function () {
    Event::fake([VehicleAvailabilityChanged::class]);

    $user = User::factory()->create();
    $vehicle = holdVehicle();
    $dates = holdDates();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => $dates['start_date'],
        'end_date' => $dates['end_date'],
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('fleet.hold.store', $vehicle), $dates);

    $response->assertUnprocessable();

    expect($response->json('errors.dates'))->not->toBeEmpty();

    expect(BookingHold::query()->count())->toBe(0);

    Event::assertNotDispatched(VehicleAvailabilityChanged::class);
});

it('deletes the requesters hold, dispatches the event, and is idempotent when absent', function () {
    Event::fake([VehicleAvailabilityChanged::class]);

    $user = User::factory()->create();
    $vehicle = holdVehicle();
    $dates = holdDates();

    BookingHold::factory()->active()->create([
        'vehicle_id' => $vehicle->id,
        'user_id' => $user->id,
        'start_date' => $dates['start_date'],
        'end_date' => $dates['end_date'],
    ]);

    $this->actingAs($user)
        ->deleteJson(route('fleet.hold.destroy', $vehicle))
        ->assertNoContent();

    expect(BookingHold::query()->count())->toBe(0);

    Event::assertDispatched(VehicleAvailabilityChanged::class, fn (VehicleAvailabilityChanged $event): bool => $event->vehicleId === $vehicle->id);

    Event::fake([VehicleAvailabilityChanged::class]);

    $this->actingAs($user)
        ->deleteJson(route('fleet.hold.destroy', $vehicle))
        ->assertNoContent();

    Event::assertDispatched(VehicleAvailabilityChanged::class, fn (VehicleAvailabilityChanged $event): bool => $event->vehicleId === $vehicle->id);
});
