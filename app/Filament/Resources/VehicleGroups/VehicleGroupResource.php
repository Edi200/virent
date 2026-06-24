<?php

namespace App\Filament\Resources\VehicleGroups;

use App\Filament\Resources\VehicleGroups\Pages\CreateVehicleGroup;
use App\Filament\Resources\VehicleGroups\Pages\EditVehicleGroup;
use App\Filament\Resources\VehicleGroups\Pages\ListVehicleGroups;
use App\Filament\Resources\VehicleGroups\Pages\ViewVehicleGroup;
use App\Filament\Resources\VehicleGroups\Schemas\VehicleGroupForm;
use App\Filament\Resources\VehicleGroups\Schemas\VehicleGroupInfolist;
use App\Filament\Resources\VehicleGroups\Tables\VehicleGroupsTable;
use App\Models\VehicleGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehicleGroupResource extends Resource
{
    protected static ?string $model = VehicleGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return VehicleGroupForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VehicleGroupInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleGroupsTable::configure($table);
    }

    /**
     * @return Builder<VehicleGroup>
     */
    public static function getEloquentQuery(): Builder
    {
        return VehicleGroup::query()
            ->withCount('categories');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicleGroups::route('/'),
            'create' => CreateVehicleGroup::route('/create'),
            'view' => ViewVehicleGroup::route('/{record}'),
            'edit' => EditVehicleGroup::route('/{record}/edit'),
        ];
    }
}
