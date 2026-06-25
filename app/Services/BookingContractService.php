<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Illuminate\Support\Carbon;

class BookingContractService
{
    public function generate(Booking $booking): PdfInstance
    {
        $booking->loadMissing([
            'vehicle.category.categoryAttributes',
            'customer.user',
            'extras',
        ]);

        return Pdf::loadView('contracts.rental-agreement', [
            'booking' => $booking,
            'vehicle' => $booking->vehicle,
            'customer' => $booking->customer,
            'specs' => $booking->vehicle->enrichedSpecs(),
            'company' => config('virent'),
            'generatedAt' => Carbon::now(),
        ]);
    }

    public function filename(Booking $booking): string
    {
        return "{$booking->reference()}-rental-agreement.pdf";
    }
}
