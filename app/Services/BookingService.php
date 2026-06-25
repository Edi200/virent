<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\VehicleAvailabilityChanged;
use App\Exceptions\InvalidBookingTransitionException;
use App\Exceptions\VehicleUnavailableException;
use App\Mail\BookingCancelledMailable;
use App\Mail\BookingConfirmedMailable;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Customer;
use App\Models\Extra;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class BookingService
{
    public function __construct(
        private readonly PricingService $pricingService,
    ) {}

    /**
     * @param  Collection<int, Extra>  $extras
     */
    public function create(
        Vehicle $vehicle,
        Customer $customer,
        Carbon $start,
        Carbon $end,
        bool $withOperator,
        Collection $extras,
        ?string $notes = null,
    ): Booking {
        if ($end->lt($start)) {
            throw new InvalidArgumentException('Booking end date must be on or after the start date.');
        }

        if ($withOperator && (! $vehicle->available_with_operator || $vehicle->operator_daily_rate === null)) {
            throw new InvalidArgumentException('This vehicle does not support operator rental.');
        }

        $booking = DB::transaction(function () use ($vehicle, $customer, $start, $end, $withOperator, $extras, $notes): Booking {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedVehicle->loadMissing('category');

            if (! $lockedVehicle->isAvailableBetween($start, $end, $customer->user_id)) {
                throw VehicleUnavailableException::forVehicle(
                    $lockedVehicle->id,
                    $start->toDateString(),
                    $end->toDateString(),
                );
            }

            $pricing = $this->pricingService->calculate(
                $lockedVehicle,
                $start,
                $end,
                $withOperator,
                $extras,
            );

            $booking = Booking::query()->create([
                'vehicle_id' => $lockedVehicle->id,
                'customer_id' => $customer->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => BookingStatus::Pending,
                'total_price' => $pricing['total_price'],
                'pricing_breakdown' => $pricing['breakdown'],
                'deposit_amount' => $lockedVehicle->deposit_amount,
                'with_operator' => $withOperator,
                'notes' => $notes,
            ]);

            foreach ($extras as $extra) {
                $booking->extras()->attach($extra->id, [
                    'price_at_booking' => number_format((float) $extra->price, 2, '.', ''),
                ]);
            }

            BookingHold::query()
                ->where('vehicle_id', $lockedVehicle->id)
                ->where('user_id', $customer->user_id)
                ->delete();

            return $booking->load('extras');
        });

        VehicleAvailabilityChanged::dispatch($vehicle->id);

        return $booking;
    }

    public function transitionTo(Booking $booking, BookingStatus $status): Booking
    {
        if (! $booking->canTransitionTo($status)) {
            throw InvalidBookingTransitionException::forBooking($booking, $status);
        }

        $booking->update(['status' => $status]);

        $booking->load(['vehicle', 'customer.user', 'extras']);

        match ($status) {
            BookingStatus::Confirmed => Mail::to($booking->customer->user)
                ->send(new BookingConfirmedMailable($booking)),
            BookingStatus::Cancelled => Mail::to($booking->customer->user)
                ->send(new BookingCancelledMailable($booking)),
            default => null,
        };

        return $booking->fresh();
    }
}
