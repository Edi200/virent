<?php

use App\Models\Extra;
use App\Models\Vehicle;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->pricing = app(PricingService::class);
});

it('calculates daily-only short rental', function () {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => null,
        'monthly_rate' => null,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-04'),
        false,
        collect(),
    );

    expect($result['base_price'])->toBe('150.00')
        ->and($result['operator_price'])->toBe('0.00')
        ->and($result['extras_price'])->toBe('0.00')
        ->and($result['total_price'])->toBe('150.00')
        ->and($result['breakdown'])->toHaveCount(1)
        ->and($result['breakdown'][0]['label'])->toBe('3 days @ €50.00/day')
        ->and($result['breakdown'][0]['amount'])->toBe('150.00');
});

it('calculates weekly tier with daily remainder', function () {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => 300,
        'monthly_rate' => null,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-11'),
        false,
        collect(),
    );

    expect($result['base_price'])->toBe('450.00')
        ->and($result['total_price'])->toBe('450.00')
        ->and($result['breakdown'])->toHaveCount(2)
        ->and($result['breakdown'][0]['label'])->toBe('1 week @ €300.00/wk')
        ->and($result['breakdown'][0]['amount'])->toBe('300.00')
        ->and($result['breakdown'][1]['label'])->toBe('3 days @ €50.00/day')
        ->and($result['breakdown'][1]['amount'])->toBe('150.00');
});

it('calculates monthly tier with daily remainder', function () {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => 200,
        'monthly_rate' => 1000,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-07-06'),
        false,
        collect(),
    );

    expect($result['base_price'])->toBe('1350.00')
        ->and($result['total_price'])->toBe('1350.00')
        ->and($result['breakdown'])->toHaveCount(2)
        ->and($result['breakdown'][0]['label'])->toBe('1 month @ €1000.00/mo')
        ->and($result['breakdown'][0]['amount'])->toBe('1000.00')
        ->and($result['breakdown'][1]['label'])->toBe('7 days @ €50.00/day')
        ->and($result['breakdown'][1]['amount'])->toBe('350.00');
});

it('adds operator surcharge when requested', function () {
    $vehicle = Vehicle::factory()->withOperator()->create([
        'daily_rate' => 50,
        'weekly_rate' => null,
        'monthly_rate' => null,
        'operator_daily_rate' => 80,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-06'),
        true,
        collect(),
    );

    expect($result['base_price'])->toBe('250.00')
        ->and($result['operator_price'])->toBe('400.00')
        ->and($result['total_price'])->toBe('650.00')
        ->and($result['breakdown'][1]['label'])->toBe('Operator (5 days @ €80.00/day)')
        ->and($result['breakdown'][1]['amount'])->toBe('400.00');
});

it('returns zero operator price when vehicle does not support operator', function () {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'available_with_operator' => false,
        'operator_daily_rate' => null,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-06'),
        true,
        collect(),
    );

    expect($result['operator_price'])->toBe('0.00')
        ->and($result['total_price'])->toBe('250.00');
});

it('calculates flat extra price', function () {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => null,
        'monthly_rate' => null,
    ]);

    $extra = Extra::factory()->flat()->create([
        'name' => 'GPS',
        'price' => 25,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-04'),
        false,
        collect([$extra]),
    );

    expect($result['extras_price'])->toBe('25.00')
        ->and($result['total_price'])->toBe('175.00')
        ->and($result['breakdown'][1]['label'])->toBe('GPS (flat)')
        ->and($result['breakdown'][1]['amount'])->toBe('25.00');
});

it('calculates per-day extra price', function () {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => null,
        'monthly_rate' => null,
    ]);

    $extra = Extra::factory()->perDay()->create([
        'name' => 'Child seat',
        'price' => 10,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
        false,
        collect([$extra]),
    );

    expect($result['extras_price'])->toBe('40.00')
        ->and($result['total_price'])->toBe('240.00')
        ->and($result['breakdown'][1]['label'])->toBe('Child seat (per day × 4)')
        ->and($result['breakdown'][1]['amount'])->toBe('40.00');
});

it('calculates combined tier operator and mixed extras scenario', function () {
    $vehicle = Vehicle::factory()->withOperator()->create([
        'daily_rate' => 50,
        'weekly_rate' => 300,
        'monthly_rate' => 1000,
        'operator_daily_rate' => 50,
    ]);

    $flatExtra = Extra::factory()->flat()->create([
        'name' => 'Insurance',
        'price' => 25,
    ]);

    $perDayExtra = Extra::factory()->perDay()->create([
        'name' => 'GPS',
        'price' => 10,
    ]);

    $result = $this->pricing->calculate(
        $vehicle,
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-07-06'),
        true,
        new Collection([$flatExtra, $perDayExtra]),
    );

    expect($result['base_price'])->toBe('1350.00')
        ->and($result['operator_price'])->toBe('1750.00')
        ->and($result['extras_price'])->toBe('375.00')
        ->and($result['total_price'])->toBe('3475.00')
        ->and($result['breakdown'])->toHaveCount(5);
});
