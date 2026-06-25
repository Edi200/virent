<?php

use App\Enums\BookingStatus;
use App\Exceptions\InvalidBookingTransitionException;
use App\Mail\BookingCancelledMailable;
use App\Mail\BookingConfirmedMailable;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->bookingService = app(BookingService::class);
});

function transitionBooking(BookingStatus $status): Booking
{
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    return Booking::factory()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $user->customer->id,
        'status' => $status,
    ]);
}

describe('Booking::canTransitionTo', function () {
    it('allows valid transitions from pending', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Pending);

        expect($booking->canTransitionTo($target))->toBeTrue();
    })->with([
        'confirmed' => BookingStatus::Confirmed,
        'cancelled' => BookingStatus::Cancelled,
    ]);

    it('allows valid transitions from confirmed', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Confirmed);

        expect($booking->canTransitionTo($target))->toBeTrue();
    })->with([
        'active' => BookingStatus::Active,
        'cancelled' => BookingStatus::Cancelled,
    ]);

    it('allows valid transitions from active', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Active);

        expect($booking->canTransitionTo($target))->toBeTrue();
    })->with([
        'completed' => BookingStatus::Completed,
        'cancelled' => BookingStatus::Cancelled,
    ]);

    it('rejects invalid transitions from pending', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Pending);

        expect($booking->canTransitionTo($target))->toBeFalse();
    })->with([
        'pending' => BookingStatus::Pending,
        'active' => BookingStatus::Active,
        'completed' => BookingStatus::Completed,
    ]);

    it('rejects invalid transitions from confirmed', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Confirmed);

        expect($booking->canTransitionTo($target))->toBeFalse();
    })->with([
        'pending' => BookingStatus::Pending,
        'confirmed' => BookingStatus::Confirmed,
        'completed' => BookingStatus::Completed,
    ]);

    it('rejects invalid transitions from active', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Active);

        expect($booking->canTransitionTo($target))->toBeFalse();
    })->with([
        'pending' => BookingStatus::Pending,
        'confirmed' => BookingStatus::Confirmed,
        'active' => BookingStatus::Active,
    ]);

    it('rejects all transitions from completed', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Completed);

        expect($booking->canTransitionTo($target))->toBeFalse();
    })->with(BookingStatus::cases());

    it('rejects all transitions from cancelled', function (BookingStatus $target) {
        $booking = transitionBooking(BookingStatus::Cancelled);

        expect($booking->canTransitionTo($target))->toBeFalse();
    })->with(BookingStatus::cases());
});

it('formats booking reference with zero-padded id', function () {
    $booking = transitionBooking(BookingStatus::Pending);

    expect($booking->reference())->toBe('VR-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT));
});

it('returns allowed transitions for each non-terminal status', function () {
    expect(transitionBooking(BookingStatus::Pending)->allowedTransitions())->toBe([
        BookingStatus::Confirmed,
        BookingStatus::Cancelled,
    ])->and(transitionBooking(BookingStatus::Confirmed)->allowedTransitions())->toBe([
        BookingStatus::Active,
        BookingStatus::Cancelled,
    ])->and(transitionBooking(BookingStatus::Active)->allowedTransitions())->toBe([
        BookingStatus::Completed,
        BookingStatus::Cancelled,
    ])->and(transitionBooking(BookingStatus::Completed)->allowedTransitions())->toBe([])
        ->and(transitionBooking(BookingStatus::Cancelled)->allowedTransitions())->toBe([]);
});

describe('BookingService::transitionTo', function () {
    it('updates status on valid transition', function () {
        Mail::fake();

        $booking = transitionBooking(BookingStatus::Pending);

        $result = $this->bookingService->transitionTo($booking, BookingStatus::Confirmed);

        expect($result->status)->toBe(BookingStatus::Confirmed)
            ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    });

    it('throws on invalid transition', function () {
        Mail::fake();

        $booking = transitionBooking(BookingStatus::Completed);

        expect(fn () => $this->bookingService->transitionTo($booking, BookingStatus::Active))
            ->toThrow(InvalidBookingTransitionException::class);

        expect($booking->fresh()->status)->toBe(BookingStatus::Completed);

        Mail::assertNothingSent();
    });

    it('sends BookingConfirmedMailable when transitioning to confirmed', function () {
        Mail::fake();

        $booking = transitionBooking(BookingStatus::Pending);
        $user = $booking->customer->user;

        $this->bookingService->transitionTo($booking, BookingStatus::Confirmed);

        Mail::assertSent(BookingConfirmedMailable::class, function (BookingConfirmedMailable $mail) use ($user, $booking): bool {
            return $mail->hasTo($user->email)
                && $mail->booking->is($booking->fresh());
        });

        Mail::assertNotSent(BookingCancelledMailable::class);
    });

    it('sends BookingCancelledMailable when transitioning to cancelled', function () {
        Mail::fake();

        $booking = transitionBooking(BookingStatus::Pending);
        $user = $booking->customer->user;

        $this->bookingService->transitionTo($booking, BookingStatus::Cancelled);

        Mail::assertSent(BookingCancelledMailable::class, function (BookingCancelledMailable $mail) use ($user, $booking): bool {
            return $mail->hasTo($user->email)
                && $mail->booking->is($booking->fresh());
        });

        Mail::assertNotSent(BookingConfirmedMailable::class);
    });

    it('sends no email when transitioning to active', function () {
        Mail::fake();

        $booking = transitionBooking(BookingStatus::Confirmed);

        $this->bookingService->transitionTo($booking, BookingStatus::Active);

        Mail::assertNothingSent();
    });

    it('sends no email when transitioning to completed', function () {
        Mail::fake();

        $booking = transitionBooking(BookingStatus::Active);

        $this->bookingService->transitionTo($booking, BookingStatus::Completed);

        Mail::assertNothingSent();
    });
});

it('exposes bookings on the customer relationship', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $booking = Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'customer_id' => $user->customer->id,
    ]);

    expect($user->customer->bookings)->toHaveCount(1)
        ->and($user->customer->bookings->first()->is($booking))->toBeTrue();
});
