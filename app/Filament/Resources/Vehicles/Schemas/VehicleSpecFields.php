<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\CategoryAttributeFieldType;
use App\Models\CategoryAttribute;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

class VehicleSpecFields
{
    /**
     * @return array<int, Placeholder|Select|TextInput|Toggle>
     */
    public static function build(Get $get): array
    {
        $categoryId = $get('category_id');

        if (blank($categoryId)) {
            return [
                Placeholder::make('specs_hint')
                    ->content('Select a category to configure specifications.'),
            ];
        }

        return CategoryAttribute::query()
            ->where('category_id', $categoryId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CategoryAttribute $attribute) => self::mapAttributeToField($attribute))
            ->all();
    }

    private static function mapAttributeToField(CategoryAttribute $attribute): Select|TextInput|Toggle
    {
        $field = match ($attribute->field_type) {
            CategoryAttributeFieldType::Text => TextInput::make($attribute->key)
                ->label($attribute->label),
            CategoryAttributeFieldType::Number => TextInput::make($attribute->key)
                ->label($attribute->label)
                ->numeric(),
            CategoryAttributeFieldType::Select => Select::make($attribute->key)
                ->label($attribute->label)
                ->options($attribute->options ?? []),
            CategoryAttributeFieldType::Boolean => Toggle::make($attribute->key)
                ->label($attribute->label),
        };

        if ($attribute->required) {
            $field->required();
        }

        return $field;
    }
}
