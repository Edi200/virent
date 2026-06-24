<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $group_id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property int $sort_order
 * @property int $buffer_hours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read VehicleGroup|null $vehicleGroup
 * @property-read Collection<int, CategoryAttribute> $categoryAttributes
 * @property-read Collection<int, Vehicle> $vehicles
 */
#[Fillable(['group_id', 'name', 'slug', 'icon', 'sort_order', 'buffer_hours'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buffer_hours' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * @return BelongsTo<VehicleGroup, $this>
     */
    public function vehicleGroup(): BelongsTo
    {
        return $this->belongsTo(VehicleGroup::class, 'group_id');
    }

    /**
     * @return HasMany<CategoryAttribute, $this>
     */
    public function categoryAttributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
