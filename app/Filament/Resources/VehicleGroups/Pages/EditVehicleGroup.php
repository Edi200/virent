<?php

namespace App\Filament\Resources\VehicleGroups\Pages;

use App\Filament\Resources\VehicleGroups\VehicleGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicleGroup extends EditRecord
{
    protected static string $resource = VehicleGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
