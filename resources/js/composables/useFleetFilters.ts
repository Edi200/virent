import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { home } from '@/routes';

export type FleetAttrValue =
    | string
    | { min?: string; max?: string }
    | boolean;

export type FleetCategory = {
    name: string;
    slug: string;
    sort_order: number;
    group_slug: string | null;
};

export type FleetFilters = {
    group: string | null;
    category: string | null;
    search: string | null;
    price_min: string | null;
    price_max: string | null;
    attrs: Record<string, FleetAttrValue>;
};

export type RangeBounds = {
    min: number;
    max: number;
    step: number;
};

const DEBOUNCE_MS = 350;

function cleanAttrs(
    attrs: Record<string, FleetAttrValue>,
): Record<string, FleetAttrValue> {
    const result: Record<string, FleetAttrValue> = {};

    for (const [key, value] of Object.entries(attrs)) {
        if (
            value === ''
            || value === null
            || value === undefined
            || value === false
        ) {
            continue;
        }

        if (typeof value === 'object') {
            const range: { min?: string; max?: string } = {};

            if (value.min) {
                range.min = value.min;
            }

            if (value.max) {
                range.max = value.max;
            }

            if (Object.keys(range).length > 0) {
                result[key] = range;
            }

            continue;
        }

        result[key] = value;
    }

    return result;
}

function buildQueryFromFilters(input: FleetFilters): Record<string, unknown> {
    const query: Record<string, unknown> = {};

    if (input.group) {
        query.group = input.group;
    }

    if (input.category) {
        query.category = input.category;
    }

    if (input.search?.trim()) {
        query.search = input.search.trim();
    }

    if (input.price_min) {
        query.price_min = input.price_min;
    }

    if (input.price_max) {
        query.price_max = input.price_max;
    }

    if (input.category) {
        const attrs = cleanAttrs(input.attrs);

        if (Object.keys(attrs).length > 0) {
            query.attrs = attrs;
        }
    }

    return query;
}

export function rangeToFilterValues(
    values: [number, number],
    bounds: RangeBounds,
): { min: string | null; max: string | null } {
    const [low, high] = values;

    return {
        min: low <= bounds.min ? null : String(low),
        max: high >= bounds.max ? null : String(high),
    };
}

export function filterValuesToRange(
    min: string | null,
    max: string | null,
    bounds: RangeBounds,
): [number, number] {
    return [
        min !== null && min !== '' ? Number(min) : bounds.min,
        max !== null && max !== '' ? Number(max) : bounds.max,
    ];
}

export function useFleetFilters(
    getFilters: () => FleetFilters,
    getCategories: () => FleetCategory[],
) {
    const searchQuery = ref('');

    let searchTimer: ReturnType<typeof setTimeout> | undefined;
    let priceRangeTimer: ReturnType<typeof setTimeout> | undefined;
    const numberRangeTimers = new Map<string, ReturnType<typeof setTimeout>>();
    const priceRangeDraft = ref<[number, number] | null>(null);
    const numberRangeDrafts = ref<Record<string, [number, number]>>({});

    watch(
        () => getFilters().search,
        (value) => {
            searchQuery.value = value ?? '';
        },
        { immediate: true },
    );

    watch(
        () => [getFilters().price_min, getFilters().price_max],
        () => {
            priceRangeDraft.value = null;
        },
    );

    watch(
        () => getFilters().attrs,
        () => {
            numberRangeDrafts.value = {};
        },
        { deep: true },
    );

    function navigate(query: Record<string, unknown>): void {
        router.get(
            home.url(Object.keys(query).length > 0 ? { query } : undefined),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function applyFilters(partial: Partial<FleetFilters>): void {
        const current = getFilters();

        const next: FleetFilters = {
            group:
                partial.group !== undefined ? partial.group : current.group,
            category:
                partial.category !== undefined
                    ? partial.category
                    : current.category,
            search:
                partial.search !== undefined ? partial.search : current.search,
            price_min:
                partial.price_min !== undefined
                    ? partial.price_min
                    : current.price_min,
            price_max:
                partial.price_max !== undefined
                    ? partial.price_max
                    : current.price_max,
            attrs:
                partial.attrs !== undefined ? partial.attrs : current.attrs,
        };

        if (
            partial.group !== undefined
            && partial.group !== current.group
        ) {
            next.category = partial.category ?? null;
            next.attrs = partial.attrs ?? {};
        }

        if (
            partial.category !== undefined
            && partial.category !== current.category
        ) {
            next.attrs = partial.attrs ?? {};
        }

        if (blankCategory(next.category)) {
            next.attrs = {};
        }

        navigate(buildQueryFromFilters(next));
    }

    function blankCategory(category: string | null | undefined): boolean {
        return category === null || category === undefined || category === '';
    }

    function selectCategory(slug: string | null): void {
        applyFilters({ category: slug, attrs: {} });
    }

    function categoriesForGroup(groupSlug: string | null): FleetCategory[] {
        if (!groupSlug) {
            return getCategories();
        }

        return getCategories().filter(
            (category) => category.group_slug === groupSlug,
        );
    }

    function selectGroup(slug: string | null): void {
        const scopedCategories = slug ? categoriesForGroup(slug) : [];
        const category =
            scopedCategories.length === 1 ? scopedCategories[0].slug : null;

        applyFilters({ group: slug, category, attrs: {} });
    }

    function onSearchInput(value: string | number): void {
        const term = String(value);

        searchQuery.value = term;

        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        searchTimer = setTimeout(() => {
            applyFilters({ search: term.trim() || null });
        }, DEBOUNCE_MS);
    }

    function getPriceRange(bounds: RangeBounds): [number, number] {
        if (priceRangeDraft.value) {
            return priceRangeDraft.value;
        }

        return filterValuesToRange(
            getFilters().price_min,
            getFilters().price_max,
            bounds,
        );
    }

    function onPriceRangeChange(
        values: [number, number],
        bounds: RangeBounds,
    ): void {
        priceRangeDraft.value = values;

        if (priceRangeTimer) {
            clearTimeout(priceRangeTimer);
        }

        priceRangeTimer = setTimeout(() => {
            const { min, max } = rangeToFilterValues(values, bounds);

            applyFilters({ price_min: min, price_max: max });
        }, DEBOUNCE_MS);
    }

    function getNumberRange(
        key: string,
        bounds: RangeBounds,
    ): [number, number] {
        if (numberRangeDrafts.value[key]) {
            return numberRangeDrafts.value[key];
        }

        const current = getFilters().attrs[key];

        if (
            typeof current === 'object'
            && current !== null
            && !Array.isArray(current)
        ) {
            return filterValuesToRange(
                current.min ?? null,
                current.max ?? null,
                bounds,
            );
        }

        return [bounds.min, bounds.max];
    }

    function onNumberRangeChange(
        key: string,
        values: [number, number],
        bounds: RangeBounds,
    ): void {
        numberRangeDrafts.value[key] = values;

        const existing = numberRangeTimers.get(key);

        if (existing) {
            clearTimeout(existing);
        }

        numberRangeTimers.set(
            key,
            setTimeout(() => {
                const { min, max } = rangeToFilterValues(values, bounds);
                const attrs = { ...getFilters().attrs };

                if (min === null && max === null) {
                    delete attrs[key];
                } else {
                    const range: { min?: string; max?: string } = {};

                    if (min !== null) {
                        range.min = min;
                    }

                    if (max !== null) {
                        range.max = max;
                    }

                    attrs[key] = range;
                }

                applyFilters({ attrs });
            }, DEBOUNCE_MS),
        );
    }

    function setAttr(key: string, value: FleetAttrValue | null): void {
        const attrs = { ...getFilters().attrs };

        if (key === 'make') {
            delete attrs.model;
        }

        if (value === null || value === '' || value === false) {
            delete attrs[key];
        } else {
            attrs[key] = value;
        }

        applyFilters({ attrs });
    }

    function isBooleanAttrChecked(key: string): boolean {
        const value = getFilters().attrs[key];

        return value === true || value === '1' || value === 1;
    }

    function getSelectAttrValue(key: string): string | undefined {
        const value = getFilters().attrs[key];

        return typeof value === 'string' && value !== '' ? value : undefined;
    }

    function isAttributeDisabled(
        dependsOn: string | undefined,
    ): boolean {
        if (!dependsOn) {
            return false;
        }

        return getSelectAttrValue(dependsOn) === undefined;
    }

    function clearAllFilters(): void {
        searchQuery.value = '';
        priceRangeDraft.value = null;
        numberRangeDrafts.value = {};
        navigate({});
    }

    function hasActiveFilters(): boolean {
        const current = getFilters();

        return Boolean(
            current.group
            || current.category
            || current.search
            || current.price_min
            || current.price_max
            || Object.keys(cleanAttrs(current.attrs)).length > 0,
        );
    }

    return {
        searchQuery,
        selectCategory,
        selectGroup,
        onSearchInput,
        getPriceRange,
        onPriceRangeChange,
        getNumberRange,
        onNumberRangeChange,
        setAttr,
        isBooleanAttrChecked,
        getSelectAttrValue,
        isAttributeDisabled,
        clearAllFilters,
        hasActiveFilters,
    };
}
