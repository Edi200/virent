<?php

namespace App\Models;

use Database\Factories\VehicleGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Category> $categories
 */
#[Fillable(['name', 'slug', 'icon', 'sort_order'])]
class VehicleGroup extends Model
{
    /** @use HasFactory<VehicleGroupFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (VehicleGroup $vehicleGroup): void {
            if (blank($vehicleGroup->slug)) {
                $vehicleGroup->slug = Str::slug($vehicleGroup->name);
            }
        });
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'group_id');
    }
}
