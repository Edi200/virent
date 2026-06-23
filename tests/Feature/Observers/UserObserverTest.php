<?php

use App\Models\Customer;
use App\Models\User;

it('creates a linked customer record when a customer-role user is created', function () {
    $user = User::factory()->create();

    expect($user->customer)->not->toBeNull()
        ->and($user->customer->user_id)->toBe($user->id)
        ->and($user->customer->driver_license_number)->toBeNull()
        ->and($user->customer->license_expiry)->toBeNull()
        ->and($user->customer->company_name)->toBeNull()
        ->and($user->customer->tax_number)->toBeNull()
        ->and($user->customer->address)->toBeNull();

    expect(Customer::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('does not create a customer record when an admin user is created', function () {
    $user = User::factory()->admin()->create();

    expect($user->customer)->toBeNull();
    expect(Customer::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('does not create a customer record when a staff user is created', function () {
    $user = User::factory()->staff()->create();

    expect($user->customer)->toBeNull();
    expect(Customer::query()->where('user_id', $user->id)->count())->toBe(0);
});
