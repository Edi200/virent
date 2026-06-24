<?php

use App\Enums\BookingStatus;
use App\Mail\BookingCreatedMailable;
use App\Models\Booking;
use App\Models\Extra;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function flowCustomer(): User
{
    return User::factory()->create();
}

function flowVehicle(): Vehicle
{
    return Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => null,
        'monthly_rate' => null,
        'deposit_amount' => 500,
    ]);
}

function flowDates(): array
{
    $start = now()->addWeek()->startOfDay();
    $end = $start->copy()->addDays(3);

    return [
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
    ];
}

it('redirects guests to login with intended url when booking', function () {
    $vehicle = flowVehicle();

    $response = $this->get(route('fleet.book', $vehicle));

    $response->assertRedirect(route('login'));
    expect(session('url.intended'))->toBe(route('fleet.book', $vehicle));
});

it('allows an authenticated customer to view the booking form', function () {
    $user = flowCustomer();
    $vehicle = flowVehicle();

    $this->actingAs($user)
        ->get(route('fleet.book', $vehicle))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Booking/Create')
            ->has('vehicle', fn (Assert $vehiclePage) => $vehiclePage
                ->where('slug', $vehicle->slug)
                ->where('name', $vehicle->name)
                ->etc()
            )
            ->has('extras')
        );
});

it('returns a price preview with breakdown and availability flag', function () {
    $user = flowCustomer();
    $vehicle = flowVehicle();
    $dates = flowDates();

    $response = $this->actingAs($user)
        ->postJson(route('fleet.price-preview', $vehicle), [
            ...$dates,
            'with_operator' => false,
            'extras' => [],
        ]);

    $expected = app(PricingService::class)->calculate(
        $vehicle,
        Carbon::parse($dates['start_date']),
        Carbon::parse($dates['end_date']),
        false,
        collect(),
    );

    $response->assertSuccessful()
        ->assertJson([
            'base_price' => $expected['base_price'],
            'operator_price' => $expected['operator_price'],
            'extras_price' => $expected['extras_price'],
            'total_price' => $expected['total_price'],
            'breakdown' => $expected['breakdown'],
            'available' => true,
        ]);
});

it('returns available false in price preview when dates conflict', function () {
    $user = flowCustomer();
    $vehicle = flowVehicle();
    $dates = flowDates();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => $dates['start_date'],
        'end_date' => $dates['end_date'],
    ]);

    $this->actingAs($user)
        ->postJson(route('fleet.price-preview', $vehicle), [
            ...$dates,
            'with_operator' => false,
            'extras' => [],
        ])
        ->assertSuccessful()
        ->assertJson(['available' => false]);
});

it('creates a pending booking, sends email, and redirects to confirmation', function () {
    Mail::fake();

    $user = flowCustomer();
    $vehicle = flowVehicle();
    $dates = flowDates();
    $extra = Extra::factory()->flat()->create(['name' => 'GPS', 'price' => 25]);

    $response = $this->actingAs($user)
        ->post(route('fleet.book.store', $vehicle), [
            ...$dates,
            'with_operator' => false,
            'extras' => [$extra->id],
        ]);

    $booking = Booking::query()->sole();

    $response->assertRedirect(route('bookings.show', $booking));

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->customer_id)->toBe($user->customer->id)
        ->and($booking->pricing_breakdown)->toBeArray()
        ->and($booking->pricing_breakdown)->not->toBeEmpty();

    Mail::assertSent(BookingCreatedMailable::class, function (BookingCreatedMailable $mail) use ($user, $booking): bool {
        return $mail->hasTo($user->email)
            && $mail->booking->is($booking);
    });
});

it('does not create a booking or send email when dates conflict on submit', function () {
    Mail::fake();

    $user = flowCustomer();
    $vehicle = flowVehicle();
    $dates = flowDates();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => $dates['start_date'],
        'end_date' => $dates['end_date'],
    ]);

    $this->actingAs($user)
        ->from(route('fleet.book', $vehicle))
        ->post(route('fleet.book.store', $vehicle), [
            ...$dates,
            'with_operator' => false,
            'extras' => [],
        ])
        ->assertRedirect(route('fleet.book', $vehicle))
        ->assertSessionHasErrors('dates');

    expect(Booking::query()->where('vehicle_id', $vehicle->id)->count())->toBe(1);

    Mail::assertNothingSent();
});

it('allows the booking owner to view the confirmation page', function () {
    $user = flowCustomer();
    $vehicle = flowVehicle();
    $dates = flowDates();

    $pricing = app(PricingService::class)->calculate(
        $vehicle,
        Carbon::parse($dates['start_date']),
        Carbon::parse($dates['end_date']),
        false,
        collect(),
    );

    $booking = Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $user->customer->id,
        'start_date' => $dates['start_date'],
        'end_date' => $dates['end_date'],
        'total_price' => $pricing['total_price'],
        'pricing_breakdown' => $pricing['breakdown'],
        'deposit_amount' => $vehicle->deposit_amount,
    ]);

    $this->actingAs($user)
        ->get(route('bookings.show', $booking))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Booking/Show')
            ->where('booking.reference', 'VR-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT))
            ->where('booking.total_price', $booking->total_price)
            ->where('booking.pricing_breakdown', $pricing['breakdown'])
            ->where('vehicle.name', $vehicle->name)
        );
});

it('returns 403 when a different customer views the confirmation page', function () {
    $owner = flowCustomer();
    $other = flowCustomer();
    $vehicle = flowVehicle();

    $booking = Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $owner->customer->id,
    ]);

    $this->actingAs($other)
        ->get(route('bookings.show', $booking))
        ->assertForbidden();
});

it('does not block admin users from the booking form', function () {
    $admin = User::factory()->admin()->create();
    $vehicle = flowVehicle();

    $this->actingAs($admin)
        ->get(route('fleet.book', $vehicle))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Booking/Create'));
});

it('does not block staff users from the booking form', function () {
    $staff = User::factory()->staff()->create();
    $vehicle = flowVehicle();

    $this->actingAs($staff)
        ->get(route('fleet.book', $vehicle))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Booking/Create'));
});
