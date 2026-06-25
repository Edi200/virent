<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Car } from '@lucide/vue';
import { computed, ref, toRef } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useLightbox } from '@/composables/useLightbox';
import { home } from '@/routes';
import { book as fleetBook } from '@/routes/fleet';

type VehicleSpec = {
    key: string;
    label: string;
    value: string;
};

type VehicleImage = {
    src: string;
    width: number;
    height: number;
    alt: string;
};

type VehicleShow = {
    slug: string;
    name: string;
    year: number;
    description: string | null;
    daily_rate: string;
    weekly_rate: string | null;
    monthly_rate: string | null;
    deposit_amount: string;
    available_with_operator: boolean;
    operator_daily_rate: string | null;
    requires_license_type: string | null;
    category: {
        name: string;
        slug: string;
    };
    specs: VehicleSpec[];
    images: VehicleImage[];
};

const props = defineProps<{
    vehicle: VehicleShow;
    back_url: string | null;
}>();

const galleryRef = ref<HTMLElement | null>(null);
const { open: openLightbox } = useLightbox(
    galleryRef,
    toRef(() => props.vehicle.images),
);

const eurFormatter = new Intl.NumberFormat('en-EU', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

function formatEur(amount: string | number): string {
    return eurFormatter.format(Number(amount));
}

function isSafeInternalPath(value: string): boolean {
    return value.startsWith('/')
        && !value.startsWith('//')
        && !value.includes('://');
}

const backHref = computed(() => {
    if (props.back_url === null || !isSafeInternalPath(props.back_url)) {
        return home();
    }

    return props.back_url;
});

function handleBackClick(): void {
    router.visit(backHref.value);
}
</script>

<template>
    <Head :title="vehicle.name" />

    <main class="mx-auto max-w-7xl px-6 py-8">
        <div class="space-y-8">
            <Button
                variant="outline"
                size="sm"
                class="w-fit gap-2"
                @click="handleBackClick"
            >
                <ArrowLeft class="size-4 shrink-0" aria-hidden="true" />
                Back to fleet
            </Button>

            <div class="lg:grid lg:grid-cols-3 lg:items-start lg:gap-8">
                <div class="min-w-0 lg:col-span-2">
                    <Card
                        class="gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0"
                    >
                        <CardContent class="space-y-6 p-5">
                            <header class="space-y-3">
                                <div
                                    class="flex flex-wrap items-start justify-between gap-3"
                                >
                                    <div class="min-w-0 space-y-1">
                                        <h1
                                            class="font-heading text-2xl font-semibold text-foreground md:text-3xl"
                                        >
                                            {{ vehicle.name }}
                                        </h1>
                                        <p class="text-sm text-muted-foreground">
                                            {{ vehicle.year }}
                                        </p>
                                    </div>
                                    <Badge variant="secondary" class="shrink-0">
                                        {{ vehicle.category.name }}
                                    </Badge>
                                </div>

                                <p
                                    v-if="vehicle.description"
                                    class="max-w-3xl text-sm text-muted-foreground md:text-base"
                                >
                                    {{ vehicle.description }}
                                </p>
                            </header>

                            <section class="space-y-3 border-t border-border pt-6">
                                <h2
                                    class="font-heading text-lg font-semibold text-foreground"
                                >
                                    Gallery
                                </h2>

                                <div
                                    v-if="vehicle.images.length > 0"
                                    ref="galleryRef"
                                    class="grid grid-cols-2 gap-2 md:grid-cols-3"
                                >
                                    <button
                                        v-for="(image, index) in vehicle.images"
                                        :key="`${image.src}-${index}`"
                                        type="button"
                                        class="overflow-hidden rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                        @click="openLightbox(index)"
                                    >
                                        <img
                                            :src="image.src"
                                            :alt="image.alt"
                                            :width="image.width"
                                            :height="image.height"
                                            loading="lazy"
                                            class="aspect-video size-full object-cover transition duration-200 hover:scale-105"
                                        />
                                    </button>
                                </div>

                                <div
                                    v-else
                                    class="overflow-hidden rounded-xl border border-border/60 bg-muted"
                                >
                                    <div
                                        class="flex aspect-video items-center justify-center"
                                    >
                                        <Car
                                            class="size-10 text-muted-foreground/50"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </div>
                            </section>

                            <section
                                v-if="vehicle.specs.length > 0"
                                class="space-y-4 border-t border-border pt-6"
                            >
                                <h2
                                    class="font-heading text-lg font-semibold text-foreground"
                                >
                                    Specifications
                                </h2>

                                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                                    <div
                                        v-for="spec in vehicle.specs"
                                        :key="spec.key"
                                        class="space-y-1"
                                    >
                                        <dt class="text-sm text-muted-foreground">
                                            {{ spec.label }}
                                        </dt>
                                        <dd class="text-sm font-medium text-foreground">
                                            {{ spec.value }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>
                        </CardContent>
                    </Card>
                </div>

                <aside class="mt-8 lg:col-span-1 lg:mt-0">
                    <Card
                        class="gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0 lg:sticky lg:top-24"
                    >
                        <CardContent class="space-y-5 p-5">
                            <div class="space-y-1">
                                <p class="text-sm text-muted-foreground">
                                    Daily rate
                                </p>
                                <p
                                    class="font-heading text-2xl font-semibold text-foreground"
                                >
                                    {{ formatEur(vehicle.daily_rate) }}
                                    <span
                                        class="text-sm font-normal text-muted-foreground"
                                    >
                                        / day
                                    </span>
                                </p>
                            </div>

                            <div
                                v-if="vehicle.weekly_rate !== null"
                                class="flex items-baseline justify-between gap-3 text-sm"
                            >
                                <span class="text-muted-foreground">Weekly</span>
                                <span class="font-medium text-foreground">
                                    {{ formatEur(vehicle.weekly_rate) }}
                                </span>
                            </div>

                            <div
                                v-if="vehicle.monthly_rate !== null"
                                class="flex items-baseline justify-between gap-3 text-sm"
                            >
                                <span class="text-muted-foreground">Monthly</span>
                                <span class="font-medium text-foreground">
                                    {{ formatEur(vehicle.monthly_rate) }}
                                </span>
                            </div>

                            <div
                                class="flex items-baseline justify-between gap-3 border-t border-border/60 pt-4 text-sm"
                            >
                                <span class="text-muted-foreground">Deposit</span>
                                <span class="font-medium text-foreground">
                                    {{ formatEur(vehicle.deposit_amount) }}
                                </span>
                            </div>

                            <div
                                v-if="vehicle.available_with_operator"
                                class="space-y-2 rounded-lg bg-muted/60 p-4 text-sm"
                            >
                                <p class="font-medium text-foreground">
                                    Available with operator
                                </p>
                                <p
                                    v-if="vehicle.operator_daily_rate !== null"
                                    class="text-muted-foreground"
                                >
                                    Operator rate:
                                    <span class="font-medium text-foreground">
                                        {{ formatEur(vehicle.operator_daily_rate) }}
                                        / day
                                    </span>
                                </p>
                                <p
                                    v-if="vehicle.requires_license_type"
                                    class="text-muted-foreground"
                                >
                                    Requires
                                    {{ vehicle.requires_license_type }} license
                                </p>
                            </div>

                            <Button
                                variant="default"
                                size="lg"
                                class="w-full"
                                as-child
                            >
                                <Link
                                    :href="
                                        fleetBook({ vehicle: vehicle.slug })
                                    "
                                >
                                    Book this vehicle
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </main>
</template>
