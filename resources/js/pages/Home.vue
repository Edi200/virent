<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Car, SlidersHorizontal } from '@lucide/vue';
import { ref } from 'vue';
import FleetFilterPanel from '@/components/FleetFilterPanel.vue';
import FleetPagination from '@/components/FleetPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    useFleetFilters,
    type FleetFilters,
    type RangeBounds,
} from '@/composables/useFleetFilters';
import { show as fleetShow } from '@/routes/fleet';

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

type VehicleListItem = {
    slug: string;
    name: string;
    year: number;
    daily_rate: string;
    category: {
        name: string;
        slug: string;
    };
    thumbnail_url: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedVehicles = {
    data: VehicleListItem[];
    current_page: number;
    last_page: number;
    links: PaginationLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
    per_page: number;
};

const props = defineProps<{
    categories: Category[];
    vehicles: PaginatedVehicles;
    filters: FleetFilters;
    filterAttributes: FilterAttribute[];
    priceBounds: RangeBounds;
}>();

const isMobileFiltersOpen = ref(false);

const {
    searchQuery,
    selectCategory,
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
} = useFleetFilters(() => props.filters);

const eurFormatter = new Intl.NumberFormat('en-EU', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

function formatEur(amount: string | number): string {
    return eurFormatter.format(Number(amount));
}

function formatPrice(value: number): string {
    return formatEur(value);
}

function handleSelectCategory(slug: string | null): void {
    selectCategory(slug);
    isMobileFiltersOpen.value = false;
}
</script>

<template>
    <Head title="Fleet" />

    <main class="mx-auto max-w-7xl px-6 py-8">
        <div class="space-y-8">
            <header class="border-b border-border/60 pb-6">
                <p class="max-w-2xl text-sm text-muted-foreground md:text-base">
                    Cars, vans, and work machinery — browse what's available
                    and find the right vehicle for your job.
                </p>
            </header>

            <div class="flex flex-col gap-8 lg:flex-row lg:items-start">
                <aside
                    class="hidden w-full shrink-0 lg:block lg:w-64 xl:w-72"
                    aria-label="Fleet filters"
                >
                    <div
                        class="sticky top-24 rounded-xl border border-border/60 border-t-2 border-t-accent/45 bg-card p-5 shadow-sm"
                    >
                        <FleetFilterPanel
                            :categories="categories"
                            :filter-attributes="filterAttributes"
                            :selected-category="filters.category"
                            :select-category="selectCategory"
                            :search-query="searchQuery"
                            :price-bounds="priceBounds"
                            :get-price-range="getPriceRange"
                            :on-price-range-change="onPriceRangeChange"
                            :on-search-input="onSearchInput"
                            :set-attr="setAttr"
                            :get-number-range="getNumberRange"
                            :on-number-range-change="onNumberRangeChange"
                            :is-boolean-attr-checked="isBooleanAttrChecked"
                            :get-select-attr-value="getSelectAttrValue"
                            :is-attribute-disabled="isAttributeDisabled"
                            :format-price="formatPrice"
                        />
                    </div>
                </aside>

                <div class="min-w-0 flex-1 space-y-6">
                    <div class="flex items-center justify-between gap-3 lg:hidden">
                        <p class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">{{
                                vehicles.total
                            }}</span>
                            vehicles
                        </p>

                        <Sheet v-model:open="isMobileFiltersOpen">
                            <SheetTrigger as-child>
                                <Button variant="outline" size="sm" class="gap-2">
                                    <SlidersHorizontal class="size-4" />
                                    Filters
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                class="w-[min(100vw-2rem,320px)] gap-4 overflow-y-auto px-4 pt-4 pb-6"
                            >
                                <SheetHeader class="gap-0 p-0 pb-2 text-left">
                                    <SheetTitle class="font-heading text-lg">
                                        Filters
                                    </SheetTitle>
                                </SheetHeader>
                                <FleetFilterPanel
                                    :categories="categories"
                                    :filter-attributes="filterAttributes"
                                    :selected-category="filters.category"
                                    :select-category="handleSelectCategory"
                                    :search-query="searchQuery"
                                    :price-bounds="priceBounds"
                                    :get-price-range="getPriceRange"
                                    :on-price-range-change="onPriceRangeChange"
                                    :on-search-input="onSearchInput"
                                    :set-attr="setAttr"
                                    :get-number-range="getNumberRange"
                                    :on-number-range-change="onNumberRangeChange"
                                    :is-boolean-attr-checked="isBooleanAttrChecked"
                                    :get-select-attr-value="getSelectAttrValue"
                                    :is-attribute-disabled="isAttributeDisabled"
                                    :format-price="formatPrice"
                                />
                            </SheetContent>
                        </Sheet>
                    </div>

                    <p class="hidden text-sm text-muted-foreground lg:block">
                        <span class="font-medium text-foreground">{{
                            vehicles.total
                        }}</span>
                        {{ vehicles.total === 1 ? 'vehicle' : 'vehicles' }}
                        available
                    </p>

                    <div
                        v-if="vehicles.data.length > 0"
                        class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        <Link
                            v-for="vehicle in vehicles.data"
                            :key="vehicle.slug"
                            :href="fleetShow({ vehicle: vehicle.slug })"
                            class="group block rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                        >
                            <Card
                                class="h-full gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0 transition duration-200 group-hover:border-primary/30 group-hover:shadow-md"
                            >
                                <div class="relative aspect-video bg-muted">
                                    <img
                                        v-if="vehicle.thumbnail_url"
                                        :src="vehicle.thumbnail_url"
                                        :alt="vehicle.name"
                                        loading="lazy"
                                        class="size-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex size-full items-center justify-center"
                                    >
                                        <Car
                                            class="size-10 text-muted-foreground/50"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </div>

                                <CardContent class="space-y-3 p-4">
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0 space-y-1">
                                            <h2
                                                class="truncate font-heading text-base font-semibold text-foreground"
                                            >
                                                {{ vehicle.name }}
                                            </h2>
                                            <p class="text-xs text-muted-foreground">
                                                {{ vehicle.year }}
                                            </p>
                                        </div>
                                        <Badge variant="secondary" class="shrink-0">
                                            {{ vehicle.category.name }}
                                        </Badge>
                                    </div>

                                    <p
                                        class="font-heading text-lg font-semibold text-foreground"
                                    >
                                        {{ formatEur(vehicle.daily_rate) }}
                                        <span
                                            class="text-xs font-normal text-muted-foreground"
                                        >
                                            / day
                                        </span>
                                    </p>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-border/60 border-t-2 border-t-accent/45 bg-card px-8 py-12 text-center shadow-sm"
                    >
                        <p class="font-heading text-lg font-medium text-foreground">
                            No vehicles match your filters
                        </p>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Try adjusting your search, category, or attribute
                            filters.
                        </p>
                        <Button
                            v-if="hasActiveFilters()"
                            variant="outline"
                            size="sm"
                            class="mt-6"
                            @click="clearAllFilters"
                        >
                            Clear all filters
                        </Button>
                    </div>

                    <FleetPagination
                        v-if="vehicles.data.length > 0"
                        :paginator="vehicles"
                    />
                </div>
            </div>
        </div>
    </main>
</template>
