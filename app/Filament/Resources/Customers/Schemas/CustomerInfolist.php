<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Name'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                    ]),
                Section::make('Rental profile')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('driver_license_number')
                            ->placeholder('-'),
                        TextEntry::make('license_expiry')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('company_name')
                            ->placeholder('-'),
                        TextEntry::make('tax_number')
                            ->placeholder('-'),
                        TextEntry::make('address')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
