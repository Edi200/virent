<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property int $year
 * @property string $daily_rate
 * @property string|null $weekly_rate
 * @property string|null $monthly_rate
 * @property string $deposit_amount
 * @property VehicleStatus $status
 * @property string|null $description
 * @property array<string, mixed>|null $specs
 * @property string|null $requires_license_type
 * @property bool $available_with_operator
 * @property string|null $operator_daily_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Fillable([
    'category_id',
    'name',
    'year',
    'daily_rate',
    'weekly_rate',
    'monthly_rate',
    'deposit_amount',
    'status',
    'description',
    'specs',
    'requires_license_type',
    'available_with_operator',
    'operator_daily_rate',
])]
class Vehicle extends Model implements HasMedia
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'specs' => 'array',
            'available_with_operator' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('fleet-images');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
