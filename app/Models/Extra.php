<?php

namespace App\Models;

use App\Enums\ExtraPriceType;
use Database\Factories\ExtraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $price
 * @property ExtraPriceType $price_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Booking> $bookings
 */
#[Fillable(['name', 'price', 'price_type'])]
class Extra extends Model
{
    /** @use HasFactory<ExtraFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_type' => ExtraPriceType::class,
        ];
    }

    /**
     * @return BelongsToMany<Booking, $this>
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_extras')
            ->withPivot('price_at_booking')
            ->withCasts(['price_at_booking' => 'decimal:2']);
    }
}
