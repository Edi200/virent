<?php

namespace App\Filament\Resources\VehicleGroups\Pages;

use App\Filament\Resources\VehicleGroups\VehicleGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehicleGroups extends ListRecords
{
    protected static string $resource = VehicleGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
