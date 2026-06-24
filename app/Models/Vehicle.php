<?php

namespace App\Models;

use App\Enums\CategoryAttributeFieldType;
use App\Enums\VehicleStatus;
use App\Services\FleetFilterService;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string|null $slug
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
    'slug',
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

    protected static function booted(): void
    {
        static::saving(function (Vehicle $vehicle): void {
            if (blank($vehicle->slug)) {
                $vehicle->slug = static::generateUniqueSlug($vehicle->name, $vehicle->id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return list<array{key: string, label: string, field_type: string, value: string}>
     */
    public function enrichedSpecs(): array
    {
        $specs = $this->specs ?? [];

        return $this->category->categoryAttributes
            ->sortBy('sort_order')
            ->map(function (CategoryAttribute $attribute) use ($specs): ?array {
                if (! array_key_exists($attribute->key, $specs) || $specs[$attribute->key] === null) {
                    return null;
                }

                $raw = $specs[$attribute->key];

                $value = match ($attribute->field_type) {
                    CategoryAttributeFieldType::Select => ($attribute->options ?? [])[(string) $raw] ?? (string) $raw,
                    CategoryAttributeFieldType::Boolean => $raw ? 'Yes' : 'No',
                    CategoryAttributeFieldType::Number => (string) $raw,
                    CategoryAttributeFieldType::Text => (string) $raw,
                };

                if ($value === '') {
                    return null;
                }

                return [
                    'key' => $attribute->key,
                    'label' => $attribute->label,
                    'field_type' => $attribute->field_type->value,
                    'value' => $value,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('fleet-images');
    }

    /**
     * @param  Builder<Vehicle>  $query
     * @return Builder<Vehicle>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::Available);
    }

    /**
     * @param  Builder<Vehicle>  $query
     * @return Builder<Vehicle>
     */
    public function scopeFleetFilter(Builder $query, Request $request): Builder
    {
        return app(FleetFilterService::class)->apply($query, $request);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
