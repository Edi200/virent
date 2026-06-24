<script setup lang="ts">
import { computed } from 'vue';
import { SlidersHorizontal } from '@lucide/vue';
import FleetRangeSlider from '@/components/FleetRangeSlider.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    FleetAttrValue,
    RangeBounds,
} from '@/composables/useFleetFilters';

type Category = {
    name: string;
    slug: string;
    sort_order: number;
};

type FilterAttribute = {
    key: string;
    label: string;
    field_type: 'text' | 'select' | 'number' | 'boolean';
    depends_on?: string;
    options?: string[] | Record<string, string>;
    bounds?: RangeBounds;
};

const props = defineProps<{
    categories: Category[];
    filterAttributes: FilterAttribute[];
    selectedCategory: string | null;
    selectCategory: (slug: string | null) => void;
    searchQuery: string;
    priceBounds: RangeBounds;
    getPriceRange: (bounds: RangeBounds) => [number, number];
    onPriceRangeChange: (
        values: [number, number],
        bounds: RangeBounds,
    ) => void;
    onSearchInput: (value: string | number) => void;
    setAttr: (key: string, value: FleetAttrValue | null) => void;
    getNumberRange: (key: string, bounds: RangeBounds) => [number, number];
    onNumberRangeChange: (
        key: string,
        values: [number, number],
        bounds: RangeBounds,
    ) => void;
    isBooleanAttrChecked: (key: string) => boolean;
    getSelectAttrValue: (key: string) => string | undefined;
    isAttributeDisabled: (dependsOn: string | undefined) => boolean;
    formatPrice: (value: number) => string;
}>();

const anyOption = '__any__';

const selectedCategoryValue = computed(
    () => props.selectedCategory ?? anyOption,
);

function isStringOptions(
    options: string[] | Record<string, string>,
): options is string[] {
    return Array.isArray(options);
}

function numberBounds(attribute: FilterAttribute): RangeBounds {
    return (
        attribute.bounds ?? {
            min: 0,
            max: 0,
            step: 1,
        }
    );
}
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-2">
            <Label for="fleet-search">Search</Label>
            <Input
                id="fleet-search"
                :model-value="searchQuery"
                type="search"
                placeholder="Search by name…"
                autocomplete="off"
                @update:model-value="onSearchInput"
            />
        </div>

        <div class="space-y-2">
            <Label for="fleet-category">Category</Label>
            <Select
                :model-value="selectedCategoryValue"
                @update:model-value="(value) => selectCategory(
                    value === anyOption ? null : String(value),
                )"
            >
                <SelectTrigger id="fleet-category" class="w-full">
                    <SelectValue placeholder="All categories" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="anyOption">All</SelectItem>
                    <SelectItem
                        v-for="category in categories"
                        :key="category.slug"
                        :value="category.slug"
                    >
                        {{ category.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div
            v-if="priceBounds.max > 0"
            class="space-y-2"
        >
            <Label>Daily rate (EUR)</Label>
            <FleetRangeSlider
                :bounds="priceBounds"
                :model-value="getPriceRange(priceBounds)"
                :format-value="formatPrice"
                @update:model-value="(values) => onPriceRangeChange(values, priceBounds)"
            />
        </div>

        <div
            v-if="selectedCategory && filterAttributes.length > 0"
            class="space-y-4"
        >
            <div
                class="flex items-center gap-2 text-sm font-medium text-foreground"
            >
                <SlidersHorizontal class="size-4 text-muted-foreground" />
                Attributes
            </div>

            <div
                v-for="attribute in filterAttributes"
                :key="attribute.key"
                class="space-y-2"
            >
                <template v-if="attribute.field_type === 'text'">
                    <Label :for="`attr-${attribute.key}`">
                        {{ attribute.label }}
                    </Label>
                    <Select
                        :model-value="getSelectAttrValue(attribute.key) ?? anyOption"
                        :disabled="isAttributeDisabled(attribute.depends_on)"
                        @update:model-value="(value) => setAttr(
                            attribute.key,
                            value === anyOption ? null : String(value),
                        )"
                    >
                        <SelectTrigger
                            :id="`attr-${attribute.key}`"
                            class="w-full"
                        >
                            <SelectValue
                                :placeholder="isAttributeDisabled(attribute.depends_on)
                                    ? `Select ${attribute.depends_on} first`
                                    : 'Any'"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="anyOption">Any</SelectItem>
                            <SelectItem
                                v-for="option in (attribute.options as string[])"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>

                <template v-else-if="attribute.field_type === 'select'">
                    <Label :for="`attr-${attribute.key}`">
                        {{ attribute.label }}
                    </Label>
                    <Select
                        :model-value="getSelectAttrValue(attribute.key) ?? anyOption"
                        :disabled="isAttributeDisabled(attribute.depends_on)"
                        @update:model-value="(value) => setAttr(
                            attribute.key,
                            value === anyOption ? null : String(value),
                        )"
                    >
                        <SelectTrigger
                            :id="`attr-${attribute.key}`"
                            class="w-full"
                        >
                            <SelectValue placeholder="Any" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="anyOption">Any</SelectItem>
                            <template
                                v-if="attribute.options && !isStringOptions(attribute.options)"
                            >
                                <SelectItem
                                    v-for="(label, value) in attribute.options"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </SelectItem>
                            </template>
                        </SelectContent>
                    </Select>
                </template>

                <template v-else-if="attribute.field_type === 'number'">
                    <Label>{{ attribute.label }}</Label>
                    <FleetRangeSlider
                        :bounds="numberBounds(attribute)"
                        :model-value="getNumberRange(attribute.key, numberBounds(attribute))"
                        @update:model-value="(values) => onNumberRangeChange(
                            attribute.key,
                            values,
                            numberBounds(attribute),
                        )"
                    />
                </template>

                <template v-else-if="attribute.field_type === 'boolean'">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            :id="`attr-${attribute.key}`"
                            :model-value="isBooleanAttrChecked(attribute.key)"
                            @update:model-value="(checked) => setAttr(
                                attribute.key,
                                checked === true ? '1' : null,
                            )"
                        />
                        <Label
                            :for="`attr-${attribute.key}`"
                            class="cursor-pointer font-normal"
                        >
                            {{ attribute.label }}
                        </Label>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
