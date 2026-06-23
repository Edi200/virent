<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Enums\CategoryAttributeFieldType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryAttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'categoryAttributes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[a-z0-9_]+$/'),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Select::make('field_type')
                    ->options(CategoryAttributeFieldType::class)
                    ->live()
                    ->required(),
                KeyValue::make('options')
                    ->keyLabel('Value')
                    ->valueLabel('Label')
                    ->visible(fn (Get $get): bool => $get('field_type') === CategoryAttributeFieldType::Select->value)
                    ->dehydrated(fn (Get $get): bool => $get('field_type') === CategoryAttributeFieldType::Select->value)
                    ->required(fn (Get $get): bool => $get('field_type') === CategoryAttributeFieldType::Select->value),
                Toggle::make('required')
                    ->default(false),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('key')
                    ->searchable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('field_type')
                    ->badge(),
                IconColumn::make('required')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
