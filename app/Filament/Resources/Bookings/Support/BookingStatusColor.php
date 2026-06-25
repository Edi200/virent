<?php

namespace App\Filament\Resources\Bookings\Support;

use App\Enums\BookingStatus;

class BookingStatusColor
{
    public static function for(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::Pending => 'warning',
            BookingStatus::Confirmed => 'info',
            BookingStatus::Active => 'primary',
            BookingStatus::Completed => 'success',
            BookingStatus::Cancelled => 'danger',
        };
    }
}
