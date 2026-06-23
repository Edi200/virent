<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('redirects admin users to profile settings', function () {
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('rental-profile.edit'))
        ->assertRedirect(route('profile.edit'));
});

it('redirects staff users to profile settings', function () {
    $staff = User::query()->where('email', 'staff@example.com')->firstOrFail();

    $this->actingAs($staff)
        ->get(route('rental-profile.edit'))
        ->assertRedirect(route('profile.edit'));
});

it('renders the rental profile page for customers and lazily creates a customer record', function () {
    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->customer)->toBeNull();

    $response = $this->actingAs($user)
        ->get(route('rental-profile.edit'))
        ->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->has('customer', fn (Assert $customer) => $customer
            ->where('driver_license_number', null)
            ->where('license_expiry', null)
            ->where('company_name', null)
            ->where('tax_number', null)
            ->where('address', null)
        )
    );

    $user->refresh();

    expect($user->customer)->not->toBeNull()
        ->and(Customer::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('persists rental profile fields on update', function () {
    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    $this->actingAs($user)
        ->patch(route('rental-profile.update'), [
            'driver_license_number' => 'PL12345678',
            'license_expiry' => now()->addYear()->format('Y-m-d'),
            'company_name' => 'Acme Rentals',
            'tax_number' => '9876543210',
            'address' => 'ul. Testowa 1, Warsaw',
        ])
        ->assertRedirect(route('rental-profile.edit'));

    $customer = $user->refresh()->ensureCustomerRecord();

    expect($customer->driver_license_number)->toBe('PL12345678')
        ->and($customer->license_expiry?->format('Y-m-d'))->toBe(now()->addYear()->format('Y-m-d'))
        ->and($customer->company_name)->toBe('Acme Rentals')
        ->and($customer->tax_number)->toBe('9876543210')
        ->and($customer->address)->toBe('ul. Testowa 1, Warsaw');
});

it('rejects a license expiry date in the past', function () {
    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    $this->actingAs($user)
        ->from(route('rental-profile.edit'))
        ->patch(route('rental-profile.update'), [
            'license_expiry' => now()->subDay()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('license_expiry');
});

it('redirects admin users to profile settings on update', function () {
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('rental-profile.update'), [
            'driver_license_number' => 'PL00000000',
        ])
        ->assertRedirect(route('profile.edit'));
});

it('redirects staff users to profile settings on update', function () {
    $staff = User::query()->where('email', 'staff@example.com')->firstOrFail();

    $this->actingAs($staff)
        ->patch(route('rental-profile.update'), [
            'driver_license_number' => 'PL00000000',
        ])
        ->assertRedirect(route('profile.edit'));
});
