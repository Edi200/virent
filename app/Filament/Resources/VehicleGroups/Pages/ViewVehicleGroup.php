<?php

namespace App\Filament\Resources\VehicleGroups\Pages;

use App\Filament\Resources\VehicleGroups\VehicleGroupResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVehicleGroup extends ViewRecord
{
    protected static string $resource = VehicleGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
