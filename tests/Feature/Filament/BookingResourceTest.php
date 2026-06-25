<?php

use App\Enums\BookingStatus;
use App\Exceptions\InvalidBookingTransitionException;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Mail\BookingConfirmedMailable;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function filamentAdmin(): User
{
    return User::factory()->admin()->create();
}

function filamentBooking(BookingStatus $status = BookingStatus::Pending): Booking
{
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    return Booking::factory()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $user->customer->id,
        'status' => $status,
    ]);
}

it('sends BookingConfirmedMailable when confirming via table action', function () {
    Mail::fake();

    $admin = filamentAdmin();
    $booking = filamentBooking(BookingStatus::Pending);

    Livewire::actingAs($admin)
        ->test(ListBookings::class)
        ->callAction(TestAction::make('transitionToConfirmed')->table($booking));

    Mail::assertSent(BookingConfirmedMailable::class, function (BookingConfirmedMailable $mail) use ($booking): bool {
        return $mail->booking->is($booking->fresh())
            && $mail->booking->status === BookingStatus::Confirmed;
    });

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('hides transition actions for completed bookings', function () {
    $admin = filamentAdmin();
    $booking = filamentBooking(BookingStatus::Completed);

    Livewire::actingAs($admin)
        ->test(ListBookings::class)
        ->assertActionHidden(TestAction::make('transitionToConfirmed')->table($booking))
        ->assertActionHidden(TestAction::make('transitionToCancelled')->table($booking))
        ->assertActionHidden(TestAction::make('transitionToActive')->table($booking))
        ->assertActionHidden(TestAction::make('transitionToCompleted')->table($booking));
});

it('still throws InvalidBookingTransitionException for invalid service transitions', function () {
    Mail::fake();

    $booking = filamentBooking(BookingStatus::Completed);

    expect(fn () => app(BookingService::class)->transitionTo($booking, BookingStatus::Active))
        ->toThrow(InvalidBookingTransitionException::class);

    Mail::assertNothingSent();
});
