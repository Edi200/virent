<?php

use App\Enums\BookingStatus;
use App\Exceptions\VehicleUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Extra;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

function bookingCustomer(): Customer
{
    return User::factory()->create()->customer;
}

beforeEach(function () {
    $this->bookingService = app(BookingService::class);
});

it('creates a booking with snapshotted extras and pricing', function () {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => null,
        'monthly_rate' => null,
        'deposit_amount' => 500,
    ]);

    $customer = bookingCustomer();

    $flatExtra = Extra::factory()->flat()->create(['name' => 'GPS', 'price' => 25]);
    $perDayExtra = Extra::factory()->perDay()->create(['name' => 'Child seat', 'price' => 10]);

    $start = Carbon::parse('2026-06-01');
    $end = Carbon::parse('2026-06-04');

    $booking = $this->bookingService->create(
        $vehicle,
        $customer,
        $start,
        $end,
        false,
        new Collection([$flatExtra, $perDayExtra]),
        'Please deliver early.',
    );

    $expectedTotal = app(PricingService::class)->calculate(
        $vehicle,
        $start,
        $end,
        false,
        new Collection([$flatExtra, $perDayExtra]),
    );

    expect($booking->vehicle_id)->toBe($vehicle->id)
        ->and($booking->customer_id)->toBe($customer->id)
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->total_price)->toBe($expectedTotal['total_price'])
        ->and($booking->pricing_breakdown)->toBe($expectedTotal['breakdown'])
        ->and($booking->deposit_amount)->toBe('500.00')
        ->and($booking->with_operator)->toBeFalse()
        ->and($booking->notes)->toBe('Please deliver early.')
        ->and($booking->extras)->toHaveCount(2);

    $gps = $booking->extras->firstWhere('id', $flatExtra->id);
    $seat = $booking->extras->firstWhere('id', $perDayExtra->id);

    expect(number_format((float) $gps->pivot->price_at_booking, 2, '.', ''))->toBe('25.00')
        ->and(number_format((float) $seat->pivot->price_at_booking, 2, '.', ''))->toBe('10.00');

    $flatExtra->update(['price' => 99]);

    expect(number_format((float) $booking->fresh()->extras->firstWhere('id', $flatExtra->id)->pivot->price_at_booking, 2, '.', ''))
        ->toBe('25.00');
});

it('throws when attempting to double-book the same dates', function () {
    $vehicle = Vehicle::factory()->create();
    $firstCustomer = bookingCustomer();
    $secondCustomer = bookingCustomer();

    $start = Carbon::parse('2026-08-01');
    $end = Carbon::parse('2026-08-10');

    $this->bookingService->create($vehicle, $firstCustomer, $start, $end, false, collect());

    expect(fn () => $this->bookingService->create(
        $vehicle,
        $secondCustomer,
        $start,
        $end,
        false,
        collect(),
    ))->toThrow(VehicleUnavailableException::class);

    expect(Booking::query()->where('vehicle_id', $vehicle->id)->count())->toBe(1);
});

it('serializes sequential booking attempts via row lock', function () {
    // SQLite in-memory (phpunit.xml) uses a single connection, so true parallel
    // lockForUpdate contention cannot be exercised here. This test proves that
    // a second sequential create after the first booking is rejected.
    $vehicle = Vehicle::factory()->create();

    $this->bookingService->create(
        $vehicle,
        bookingCustomer(),
        Carbon::parse('2026-09-01'),
        Carbon::parse('2026-09-05'),
        false,
        collect(),
    );

    $this->bookingService->create(
        $vehicle,
        bookingCustomer(),
        Carbon::parse('2026-09-10'),
        Carbon::parse('2026-09-15'),
        false,
        collect(),
    );

    expect(Booking::query()->where('vehicle_id', $vehicle->id)->count())->toBe(2);

    expect(fn () => $this->bookingService->create(
        $vehicle,
        bookingCustomer(),
        Carbon::parse('2026-09-12'),
        Carbon::parse('2026-09-18'),
        false,
        collect(),
    ))->toThrow(VehicleUnavailableException::class);
});

it('rejects operator rental when the vehicle does not support it', function () {
    $vehicle = Vehicle::factory()->create([
        'available_with_operator' => false,
        'operator_daily_rate' => null,
    ]);

    expect(fn () => $this->bookingService->create(
        $vehicle,
        bookingCustomer(),
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
        true,
        collect(),
    ))->toThrow(InvalidArgumentException::class, 'This vehicle does not support operator rental.');

    expect(Booking::query()->count())->toBe(0);
});

it('allows operator rental when the vehicle supports it', function () {
    $vehicle = Vehicle::factory()->withOperator()->create([
        'daily_rate' => 100,
        'weekly_rate' => null,
        'monthly_rate' => null,
    ]);

    $booking = $this->bookingService->create(
        $vehicle,
        bookingCustomer(),
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-03'),
        true,
        collect(),
    );

    expect($booking->with_operator)->toBeTrue()
        ->and((float) $booking->total_price)->toBeGreaterThan(200.0);
});
