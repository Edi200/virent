<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('category.name')
                    ->label('Category'),
                TextEntry::make('name'),
                TextEntry::make('year')
                    ->numeric(),
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
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('requires_license_type')
                    ->placeholder('-'),
                IconEntry::make('available_with_operator')
                    ->boolean(),
                TextEntry::make('operator_daily_rate')
                    ->money('EUR')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
