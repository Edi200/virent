<?php

namespace App\Filament\Resources\Bookings\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Actions\Action;
use Illuminate\Support\Str;

class BookingTransitionActions
{
    /**
     * @return list<Action>
     */
    public static function make(): array
    {
        return [
            self::forStatus(BookingStatus::Confirmed, 'Confirm', __('Mark this booking as confirmed?')),
            self::forStatus(BookingStatus::Cancelled, 'Cancel', __('Cancel this booking?')),
            self::forStatus(BookingStatus::Active, 'Mark active', __('Mark this booking as active?')),
            self::forStatus(BookingStatus::Completed, 'Complete', __('Mark this booking as completed?')),
        ];
    }

    private static function forStatus(BookingStatus $target, string $label, string $modalDescription): Action
    {
        $name = 'transitionTo'.Str::studly($target->value);

        return Action::make($name)
            ->label(__($label))
            ->visible(fn (Booking $record): bool => $record->canTransitionTo($target))
            ->requiresConfirmation()
            ->modalDescription($modalDescription)
            ->action(function (Booking $record) use ($target): void {
                app(BookingService::class)->transitionTo($record, $target);
            });
    }
}
