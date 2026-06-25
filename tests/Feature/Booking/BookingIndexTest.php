<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

function indexCustomer(): User
{
    return User::factory()->create();
}

function indexVehicle(): Vehicle
{
    return Vehicle::factory()->create();
}

it('redirects admin users from the bookings index to profile settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('bookings.index'))
        ->assertRedirect(route('profile.edit'));
});

it('redirects staff users from the bookings index to profile settings', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('bookings.index'))
        ->assertRedirect(route('profile.edit'));
});

it('lists only the authenticated customer bookings newest first', function () {
    $user = indexCustomer();
    $other = indexCustomer();
    $vehicle = indexVehicle();

    $olderBooking = Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $user->customer->id,
        'created_at' => now()->subDay(),
    ]);

    $newerBooking = Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $user->customer->id,
        'created_at' => now(),
    ]);

    Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $other->customer->id,
    ]);

    $this->actingAs($user)
        ->get(route('bookings.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Bookings/Index')
            ->has('bookings.data', 2)
            ->where('bookings.data.0.id', $newerBooking->id)
            ->where('bookings.data.1.id', $olderBooking->id)
            ->where('bookings.data.0.reference', $newerBooking->reference())
            ->where('bookings.data.0.vehicle.name', $vehicle->name)
            ->where('bookings.data.0.status', $newerBooking->status->value)
            ->where('bookings.data.0.total_price', $newerBooking->total_price)
        );
});

it('paginates bookings when more than ten exist', function () {
    $user = indexCustomer();
    $vehicle = indexVehicle();

    Booking::factory()
        ->count(11)
        ->pending()
        ->create([
            'vehicle_id' => $vehicle->id,
            'customer_id' => $user->customer->id,
        ]);

    $this->actingAs($user)
        ->get(route('bookings.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Bookings/Index')
            ->has('bookings.data', 10)
            ->has('bookings.last_page')
            ->has('bookings.links')
            ->has('bookings.next_page_url')
            ->where('bookings.last_page', 2)
            ->where('bookings.next_page_url', fn ($url) => $url !== null)
        );
});

it('redirects guests from the bookings index to login', function () {
    $this->get(route('bookings.index'))
        ->assertRedirect(route('login'));
});
