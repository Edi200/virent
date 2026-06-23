<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('allows admin to access fleet and customer resources', function () {
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->get('/admin/categories')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/vehicles')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/customers')
        ->assertSuccessful();
});

it('denies customer role access to the admin panel', function () {
    $customer = User::query()->where('email', 'test@example.com')->firstOrFail();

    $this->actingAs($customer)
        ->get('/admin')
        ->assertForbidden();
});
