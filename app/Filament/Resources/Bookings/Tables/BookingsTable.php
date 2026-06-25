<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Actions\BookingTransitionActions;
use App\Filament\Resources\Bookings\Support\BookingStatusColor;
use App\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->state(fn (Booking $record): string => $record->reference())
                    ->fontFamily('mono')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('id', $direction)),
                TextColumn::make('vehicle.name')
                    ->label('Vehicle')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (BookingStatus $state): string => BookingStatusColor::for($state))
                    ->sortable(),
                TextColumn::make('total_price')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(BookingStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                ...BookingTransitionActions::make(),
            ]);
    }
}
