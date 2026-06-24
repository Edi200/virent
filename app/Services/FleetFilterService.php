<?php

namespace App\Services;

use App\Enums\CategoryAttributeFieldType;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FleetFilterService
{
    /**
     * @param  Builder<Vehicle>  $query
     * @return Builder<Vehicle>
     */
    public function apply(Builder $query, Request $request): Builder
    {
        $query->available();

        $category = $this->resolveCategory($request->query('category'));

        $query->when(
            $category !== null,
            fn (Builder $builder) => $builder->where('category_id', $category->id),
        );

        $this->applyPriceFilters($query, $request);
        $this->applySearchFilter($query, $request);

        if ($category !== null) {
            $this->applyAttributeFilters($query, $request, $category);
        }

        return $query;
    }

    /**
     * @return array{
     *     category: string|null,
     *     search: string|null,
     *     price_min: string|null,
     *     price_max: string|null,
     *     attrs: array<string, mixed>
     * }
     */
    public function filtersFromRequest(Request $request): array
    {
        $category = $request->query('category');
        $attrs = is_array($request->input('attrs')) ? $request->input('attrs') : [];

        if (blank($category)) {
            $attrs = [];
        }

        return [
            'category' => $category,
            'search' => $request->query('search'),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'attrs' => $attrs,
        ];
    }

    /**
     * Price bounds reflect category + search only — never attribute filters.
     *
     * @return array{min: float, max: float, step: float}
     */
    public function priceBounds(Request $request): array
    {
        $rates = $this->facetVehicleQuery(
            $request,
            excludePrice: true,
            excludeAttributes: true,
        )
            ->pluck('daily_rate')
            ->map(fn (string $rate) => (float) $rate)
            ->filter(fn (float $rate) => $rate > 0)
            ->values();

        if ($rates->isEmpty()) {
            return ['min' => 0.0, 'max' => 0.0, 'step' => 1.0];
        }

        return [
            'min' => (float) $rates->min(),
            'max' => (float) $rates->max(),
            'step' => 1.0,
        ];
    }

    /**
     * Distinct values and bounds are scoped to vehicles matching all active
     * filters except the attribute currently being edited (faceted search).
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     field_type: string,
     *     depends_on?: string,
     *     options?: list<string>|array<string, string>,
     *     bounds?: array{min: float, max: float, step: float}
     * }>
     */
    public function filterAttributes(Category $category, Request $request): array
    {
        $category->loadMissing('categoryAttributes');

        return array_values($category->categoryAttributes
            ->sortBy('sort_order')
            ->map(function (CategoryAttribute $attribute) use ($category, $request): array {
                $vehicles = $this->facetVehicleQuery(
                    $request,
                    $category,
                    excludeAttributeKey: $attribute->key,
                    excludePrice: true,
                )->get(['specs', 'daily_rate']);

                $meta = [
                    'key' => $attribute->key,
                    'label' => $attribute->label,
                    'field_type' => $attribute->field_type->value,
                ];

                if ($attribute->key === 'model') {
                    $meta['depends_on'] = 'make';
                }

                return match ($attribute->field_type) {
                    CategoryAttributeFieldType::Text => [
                        ...$meta,
                        'options' => $this->distinctTextValues($vehicles, $attribute->key),
                    ],
                    CategoryAttributeFieldType::Select => [
                        ...$meta,
                        'options' => $attribute->options ?? [],
                    ],
                    CategoryAttributeFieldType::Number => [
                        ...$meta,
                        'bounds' => $this->numberBounds($vehicles, $attribute->key),
                    ],
                    default => $meta,
                };
            })
            ->values()
            ->all());
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    private function applyPriceFilters(Builder $query, Request $request): void
    {
        $query->when(
            $request->filled('price_min'),
            fn (Builder $builder) => $builder->where('daily_rate', '>=', $request->query('price_min')),
        );

        $query->when(
            $request->filled('price_max'),
            fn (Builder $builder) => $builder->where('daily_rate', '<=', $request->query('price_max')),
        );
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    private function applySearchFilter(Builder $query, Request $request): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $term = (string) $request->query('search');
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $term);

        // LIKE '%term%' cannot use a btree index. At scale, upgrade to MySQL
        // FULLTEXT or Laravel Scout + Meilisearch — deliberately out of scope.
        $query->where('name', 'like', '%'.$escaped.'%');
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    private function applyAttributeFilters(
        Builder $query,
        Request $request,
        Category $category,
        ?string $exceptKey = null,
    ): void {
        $attrs = $request->input('attrs', []);

        if (! is_array($attrs) || $attrs === []) {
            return;
        }

        $category->loadMissing('categoryAttributes');
        $attributesByKey = $category->categoryAttributes->keyBy('key');

        foreach ($attrs as $key => $value) {
            if (! is_string($key) || $key === $exceptKey || ! $attributesByKey->has($key)) {
                continue;
            }

            $attribute = $attributesByKey->get($key);

            match ($attribute->field_type) {
                CategoryAttributeFieldType::Text,
                CategoryAttributeFieldType::Select => $this->applyExactSpecFilter($query, $key, $value),
                CategoryAttributeFieldType::Number => $this->applyNumberSpecFilter($query, $key, $value),
                CategoryAttributeFieldType::Boolean => $this->applyBooleanSpecFilter($query, $key, $value),
            };
        }
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    private function applyExactSpecFilter(Builder $query, string $key, mixed $value): void
    {
        if (! is_scalar($value) || $value === '') {
            return;
        }

        $query->where("specs->{$key}", (string) $value);
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    private function applyNumberSpecFilter(Builder $query, string $key, mixed $value): void
    {
        if (! is_array($value)) {
            return;
        }

        $jsonPath = '$.'.$key;

        if (array_key_exists('min', $value) && $value['min'] !== '' && $value['min'] !== null) {
            $query->whereRaw(
                'CAST(JSON_EXTRACT(specs, ?) AS DECIMAL(16,4)) >= ?',
                [$jsonPath, (float) $value['min']],
            );
        }

        if (array_key_exists('max', $value) && $value['max'] !== '' && $value['max'] !== null) {
            $query->whereRaw(
                'CAST(JSON_EXTRACT(specs, ?) AS DECIMAL(16,4)) <= ?',
                [$jsonPath, (float) $value['max']],
            );
        }
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    private function applyBooleanSpecFilter(Builder $query, string $key, mixed $value): void
    {
        if (! in_array($value, [true, 1, '1', 'true'], true)) {
            return;
        }

        $query->where("specs->{$key}", true);
    }

    /**
     * @return Builder<Vehicle>
     */
    private function facetVehicleQuery(
        Request $request,
        ?Category $category = null,
        ?string $excludeAttributeKey = null,
        bool $excludePrice = false,
        bool $excludeAttributes = false,
    ): Builder {
        $category ??= $this->resolveCategory($request->query('category'));

        $query = Vehicle::query()->available();

        if ($category !== null) {
            $query->where('category_id', $category->id);
        }

        if (! $excludePrice) {
            $this->applyPriceFilters($query, $request);
        }

        $this->applySearchFilter($query, $request);

        if ($category !== null && ! $excludeAttributes) {
            $this->applyAttributeFilters($query, $request, $category, $excludeAttributeKey);
        }

        return $query;
    }

    private function resolveCategory(?string $slug): ?Category
    {
        if (blank($slug)) {
            return null;
        }

        return Category::query()->where('slug', $slug)->first();
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @return list<string>
     */
    private function distinctTextValues(Collection $vehicles, string $key): array
    {
        return array_values($vehicles
            ->map(fn (Vehicle $vehicle) => ($vehicle->specs ?? [])[$key] ?? null)
            ->filter(fn ($value) => is_scalar($value) && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->sort()
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array{min: float, max: float, step: float}
     */
    private function numberBounds(Collection $vehicles, string $key): array
    {
        $values = $vehicles
            ->map(fn (Vehicle $vehicle) => ($vehicle->specs ?? [])[$key] ?? null)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($values->isEmpty()) {
            return ['min' => 0.0, 'max' => 0.0, 'step' => 1.0];
        }

        $min = (float) $values->min();
        $max = (float) $values->max();
        $step = $this->hasOnlyIntegerValues($values) ? 1.0 : 0.1;

        return compact('min', 'max', 'step');
    }

    /**
     * @param  Collection<int, float>  $values
     */
    private function hasOnlyIntegerValues(Collection $values): bool
    {
        return $values->every(fn (float $value) => floor($value) === $value);
    }
}
