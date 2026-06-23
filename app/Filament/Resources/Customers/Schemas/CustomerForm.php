<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('driver_license_number')
                    ->maxLength(255),
                DatePicker::make('license_expiry'),
                TextInput::make('company_name')
                    ->maxLength(255),
                TextInput::make('tax_number')
                    ->maxLength(255),
                Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }
}
