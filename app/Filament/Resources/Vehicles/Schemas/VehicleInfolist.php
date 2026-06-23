<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Overview')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('name'),
                        TextEntry::make('year')
                            ->numeric(),
                        TextEntry::make('status')
                            ->badge(),
                    ]),
                Section::make('Pricing')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('daily_rate')
                            ->money('EUR'),
                        TextEntry::make('weekly_rate')
                            ->money('EUR')
                            ->placeholder('-'),
                        TextEntry::make('monthly_rate')
                            ->money('EUR')
                            ->placeholder('-'),
                        TextEntry::make('deposit_amount')
                            ->money('EUR'),
                    ]),
                Section::make('Operator')
                    ->columns(2)
                    ->schema([
                        IconEntry::make('available_with_operator')
                            ->boolean(),
                        TextEntry::make('operator_daily_rate')
                            ->money('EUR')
                            ->placeholder('-'),
                        TextEntry::make('requires_license_type')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->placeholder('-'),
                    ]),
                Section::make('Specifications')
                    ->schema([
                        KeyValueEntry::make('specs')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->placeholder('-'),
                    ]),
                Section::make('Media')
                    ->schema([
                        RepeatableEntry::make('media')
                            ->contained(false)
                            ->grid(3)
                            ->schema([
                                ImageEntry::make('original_url')
                                    ->label('')
                                    ->square(),
                            ]),
                    ]),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->size('xs')
                    ->color('gray'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->size('xs')
                    ->color('gray'),
            ]);
    }
}
