<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Exception;

class InvalidBookingTransitionException extends Exception
{
    public static function forBooking(Booking $booking, BookingStatus $target): self
    {
        return new self(
            "Booking [{$booking->id}] cannot transition from {$booking->status->value} to {$target->value}."
        );
    }
}
