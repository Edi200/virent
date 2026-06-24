<?php

namespace App\Exceptions;

use Exception;

class VehicleUnavailableException extends Exception
{
    public static function forVehicle(int $vehicleId, string $startDate, string $endDate): self
    {
        return new self(
            "Vehicle [{$vehicleId}] is not available between {$startDate} and {$endDate}."
        );
    }
}
