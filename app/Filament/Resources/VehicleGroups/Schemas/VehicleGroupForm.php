<?php

namespace App\Filament\Resources\VehicleGroups\Schemas;

use App\Models\VehicleGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VehicleGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation !== 'create') {
                            return;
                        }

                        $set('slug', Str::slug($state ?? ''));
                    }),
                Toggle::make('_unlock_slug')
                    ->label('Change slug')
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->live()
                    ->dehydrated(false),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->readOnly(fn (Get $get, string $operation): bool => $operation === 'edit' && ! $get('_unlock_slug'))
                    ->dehydrated()
                    ->unique(VehicleGroup::class, 'slug', ignoreRecord: true),
                TextInput::make('icon')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
