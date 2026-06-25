<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Resources\Bookings\Support\BookingStatusColor;
use App\Models\Booking;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Booking')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reference')
                            ->label('Reference')
                            ->state(fn (Booking $record): string => $record->reference())
                            ->fontFamily('mono'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (Booking $record): string => BookingStatusColor::for($record->status)),
                    ]),
                Section::make('Vehicle')
                    ->schema([
                        TextEntry::make('vehicle.name')
                            ->label('Vehicle'),
                    ]),
                Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer.user.name')
                            ->label('Name'),
                        TextEntry::make('customer.user.email')
                            ->label('Email'),
                    ]),
                Section::make('Dates')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('start_date')
                            ->date(),
                        TextEntry::make('end_date')
                            ->date(),
                        IconEntry::make('with_operator')
                            ->label('With operator')
                            ->boolean(),
                    ]),
                Section::make('Pricing')
                    ->schema([
                        RepeatableEntry::make('pricing_breakdown')
                            ->label('Breakdown')
                            ->schema([
                                TextEntry::make('label'),
                                TextEntry::make('amount')
                                    ->money('EUR'),
                            ])
                            ->placeholder('-'),
                        TextEntry::make('total_price')
                            ->money('EUR'),
                        TextEntry::make('deposit_amount')
                            ->money('EUR'),
                    ]),
                Section::make('Extras')
                    ->schema([
                        RepeatableEntry::make('extras')
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('pivot.price_at_booking')
                                    ->label('Price at booking')
                                    ->money('EUR'),
                            ])
                            ->placeholder('-'),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
