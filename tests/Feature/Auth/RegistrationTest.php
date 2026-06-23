<?php

use App\Models\Customer;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
    $response->assertSessionHas('inertia.flash_data.showRentalProfileModal', true);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->customer)->not->toBeNull()
        ->and($user->customer->user_id)->toBe($user->id);

    expect(Customer::query()->where('user_id', $user->id)->count())->toBe(1);
});
