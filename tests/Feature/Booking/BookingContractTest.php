<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingContractService;
use App\Services\PricingService;
use Illuminate\Support\Carbon;

function contractBooking(?BookingStatus $status = null): Booking
{
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 50,
        'weekly_rate' => null,
        'monthly_rate' => null,
        'deposit_amount' => 500,
    ]);

    $start = Carbon::parse('2026-06-01');
    $end = Carbon::parse('2026-06-04');

    $pricing = app(PricingService::class)->calculate(
        $vehicle,
        $start,
        $end,
        false,
        collect(),
    );

    $factory = Booking::factory();

    if ($status !== null) {
        $factory = match ($status) {
            BookingStatus::Pending => $factory->pending(),
            BookingStatus::Confirmed => $factory->confirmed(),
            BookingStatus::Active => $factory->active(),
            BookingStatus::Completed => $factory->completed(),
            BookingStatus::Cancelled => $factory->cancelled(),
        };
    } else {
        $factory = $factory->pending();
    }

    return $factory->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $user->customer->id,
        'start_date' => $start,
        'end_date' => $end,
        'total_price' => $pricing['total_price'],
        'pricing_breakdown' => $pricing['breakdown'],
        'deposit_amount' => $vehicle->deposit_amount,
    ]);
}

it('generates a valid PDF from BookingContractService', function () {
    $booking = contractBooking();

    $pdf = app(BookingContractService::class)->generate($booking);
    $output = $pdf->output();

    expect($output)->toStartWith('%PDF');
});

it('builds a filename from the booking reference', function () {
    $booking = contractBooking();

    $filename = app(BookingContractService::class)->filename($booking);

    expect($filename)->toBe("{$booking->reference()}-rental-agreement.pdf");
});

it('allows the booking owner to download the rental agreement', function (BookingStatus $status) {
    $booking = contractBooking($status);
    $owner = $booking->customer->user;

    $response = $this->actingAs($owner)
        ->get(route('bookings.contract', $booking));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
})->with([
    'confirmed' => BookingStatus::Confirmed,
    'active' => BookingStatus::Active,
    'completed' => BookingStatus::Completed,
]);

it('returns 403 when the booking is pending', function () {
    $booking = contractBooking(BookingStatus::Pending);
    $owner = $booking->customer->user;

    $response = $this->actingAs($owner)
        ->get(route('bookings.contract', $booking));

    $response->assertForbidden();
    expect($response->exception?->getMessage())->toBe(
        'The rental agreement will be available once your booking is confirmed.',
    );
});

it('returns 403 when a different customer requests the rental agreement', function () {
    $booking = contractBooking();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('bookings.contract', $booking))
        ->assertForbidden();
});

it('returns 403 when the booking is cancelled', function () {
    $booking = contractBooking(BookingStatus::Cancelled);
    $owner = $booking->customer->user;

    $this->actingAs($owner)
        ->get(route('bookings.contract', $booking))
        ->assertForbidden();
});
