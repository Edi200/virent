<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\VehicleStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Vehicle details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (Set $set) => $set('specs', [])),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('year')
                            ->required()
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y') + 1),
                        Select::make('status')
                            ->options(VehicleStatus::class)
                            ->default(VehicleStatus::Available)
                            ->required(),
                        TextInput::make('daily_rate')
                            ->required()
                            ->numeric()
                            ->prefix('€'),
                        TextInput::make('weekly_rate')
                            ->numeric()
                            ->prefix('€'),
                        TextInput::make('monthly_rate')
                            ->numeric()
                            ->prefix('€'),
                        TextInput::make('deposit_amount')
                            ->required()
                            ->numeric()
                            ->prefix('€'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        TextInput::make('requires_license_type')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Operator rental')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Toggle::make('available_with_operator')
                            ->live()
                            ->default(false),
                        TextInput::make('operator_daily_rate')
                            ->numeric()
                            ->prefix('€')
                            ->visible(fn (Get $get): bool => (bool) $get('available_with_operator')),
                    ]),
                Section::make('Specifications')
                    ->columnSpanFull()
                    ->columns(2)
                    ->statePath('specs')
                    ->schema(fn (Get $get): array => VehicleSpecFields::build($get)),
                Section::make('Fleet images')
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('fleet-images')
                            ->collection('fleet-images')
                            ->disk('public')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
