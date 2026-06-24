<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int $customer_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property BookingStatus $status
 * @property string $total_price
 * @property string $deposit_amount
 * @property Carbon|null $deposit_paid_at
 * @property bool $with_operator
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vehicle $vehicle
 * @property-read Customer $customer
 * @property-read Collection<int, Extra> $extras
 */
#[Fillable([
    'vehicle_id',
    'customer_id',
    'start_date',
    'end_date',
    'status',
    'total_price',
    'deposit_amount',
    'deposit_paid_at',
    'with_operator',
    'notes',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => BookingStatus::class,
            'total_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'deposit_paid_at' => 'datetime',
            'with_operator' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsToMany<Extra, $this>
     */
    public function extras(): BelongsToMany
    {
        return $this->belongsToMany(Extra::class, 'booking_extras')
            ->withPivot('price_at_booking')
            ->withCasts(['price_at_booking' => 'decimal:2']);
    }
}
