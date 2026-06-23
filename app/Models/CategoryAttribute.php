<?php

namespace App\Models;

use App\Enums\CategoryAttributeFieldType;
use Database\Factories\CategoryAttributeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $key
 * @property string $label
 * @property CategoryAttributeFieldType $field_type
 * @property array<string, string>|null $options
 * @property bool $required
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Fillable(['category_id', 'key', 'label', 'field_type', 'options', 'required', 'sort_order'])]
class CategoryAttribute extends Model
{
    /** @use HasFactory<CategoryAttributeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field_type' => CategoryAttributeFieldType::class,
            'options' => 'array',
            'required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
