<script setup lang="ts">
import { Head, Link, useForm, useHttp } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { AlertTriangle, ArrowLeft } from '@lucide/vue';
import type { DateValue } from '@internationalized/date';
import type { DateRange, RangeCalendarRootProps } from 'reka-ui';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { getLocalTimeZone, today as dateToday } from '@internationalized/date';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useBookingDisplay } from '@/composables/useBookingDisplay';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { store as bookStore } from '@/routes/fleet/book';
import { destroy as holdDestroy, store as holdStore } from '@/routes/fleet/hold';
import { pricePreview, show as fleetShow, unavailableDates } from '@/routes/fleet';

type VehicleSummary = {
    id: number;
    slug: string;
    name: string;
    daily_rate: string;
    available_with_operator: boolean;
    operator_daily_rate: string | null;
    category: {
        name: string;
        slug: string;
    };
};

type ExtraOption = {
    id: number;
    name: string;
    price: string;
    price_type: 'flat' | 'per_day';
};

type BreakdownLine = {
    label: string;
    amount: string;
};

type PricePreviewResult = {
    base_price: string;
    operator_price: string;
    extras_price: string;
    total_price: string;
    breakdown: BreakdownLine[];
    available: boolean;
};

type UnavailableRange = {
    start_date: string;
    end_date: string;
};

const props = defineProps<{
    vehicle: VehicleSummary;
    extras: ExtraOption[];
}>();

const DEBOUNCE_MS = 350;
const localTimeZone = getLocalTimeZone();
const minBookingDate = dateToday(localTimeZone);

const form = useForm({
    start_date: '',
    end_date: '',
    with_operator: false,
    extras: [] as number[],
});

const previewHttp = useHttp({
    start_date: '',
    end_date: '',
    with_operator: false,
    extras: [] as number[],
});

const holdHttp = useHttp({
    start_date: '',
    end_date: '',
});

const preview = ref<PricePreviewResult | null>(null);
const previewLoading = ref(false);
const previewError = ref<string | null>(null);
const confirmModalOpen = ref(false);
const liveConflictWarning = ref(false);
const unavailableDatesLoading = ref(false);
const unavailableDatesError = ref<string | null>(null);
const unavailableDateSet = ref<Set<string>>(new Set());
type SelectedRange = RangeCalendarRootProps['modelValue'];
const selectedRange = ref<SelectedRange>(undefined);
const selectedRangeForCalendar = computed(
    () => selectedRange.value as DateRange | null | undefined,
);
const datesError = computed(
    () => (form.errors as Record<string, string | undefined>).dates,
);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let previewRequestId = 0;

const { formatDate } = useBookingDisplay();

const eurFormatter = new Intl.NumberFormat('en-EU', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function formatEur(amount: string | number): string {
    return eurFormatter.format(Number(amount));
}

function formatExtraPrice(extra: ExtraOption): string {
    if (extra.price_type === 'per_day') {
        return `${formatEur(extra.price)} / day`;
    }

    return formatEur(extra.price);
}

const datesComplete = computed(
    () =>
        form.start_date !== ''
        && form.end_date !== ''
        && form.end_date > form.start_date,
);

const isPreviewUnavailable = computed(
    () => preview.value !== null && preview.value.available === false,
);

const canOpenConfirmModal = computed(
    () =>
        datesComplete.value
        && !previewLoading.value
        && preview.value !== null
        && preview.value.available
        && !liveConflictWarning.value,
);

const selectedExtras = computed(() =>
    props.extras.filter((extra) => form.extras.includes(extra.id)),
);

function isExtraSelected(extraId: number): boolean {
    return form.extras.includes(extraId);
}

function toggleExtra(extraId: number, checked: boolean | 'indeterminate'): void {
    if (checked === true) {
        if (!form.extras.includes(extraId)) {
            form.extras = [...form.extras, extraId];
        }

        return;
    }

    form.extras = form.extras.filter((id) => id !== extraId);
}

function dateValueToIso(value: DateValue): string {
    return value.toString();
}

function expandUnavailableDates(ranges: UnavailableRange[]): Set<string> {
    const expanded = new Set<string>();

    ranges.forEach((range) => {
        const start = new Date(`${range.start_date}T00:00:00Z`);
        const end = new Date(`${range.end_date}T00:00:00Z`);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || start > end) {
            return;
        }

        const cursor = new Date(start);

        while (cursor <= end) {
            expanded.add(cursor.toISOString().slice(0, 10));
            cursor.setUTCDate(cursor.getUTCDate() + 1);
        }
    });

    return expanded;
}

function isDateUnavailable(date: DateValue): boolean {
    return unavailableDateSet.value.has(dateValueToIso(date));
}

function isSelectedRangeBlocked(start: string, end: string): boolean {
    const cursor = new Date(`${start}T00:00:00Z`);
    const endDate = new Date(`${end}T00:00:00Z`);

    if (Number.isNaN(cursor.getTime()) || Number.isNaN(endDate.getTime())) {
        return false;
    }

    while (cursor < endDate) {
        if (unavailableDateSet.value.has(cursor.toISOString().slice(0, 10))) {
            return true;
        }

        cursor.setUTCDate(cursor.getUTCDate() + 1);
    }

    return false;
}

function clearDateSelection(): void {
    selectedRange.value = undefined;
    form.start_date = '';
    form.end_date = '';
    preview.value = null;
    previewError.value = null;
    previewRequestId++;
}

function syncFormFromRange(range: SelectedRange): void {
    if (range?.start === undefined || range.end === undefined) {
        form.start_date = '';
        form.end_date = '';

        return;
    }

    form.start_date = dateValueToIso(range.start);
    form.end_date = dateValueToIso(range.end);
}

function handleRangeUpdate(range: SelectedRange): void {
    liveConflictWarning.value = false;
    selectedRange.value = range;
    syncFormFromRange(range);
}

async function fetchUnavailableDates(options?: {
    checkSelectionConflict?: boolean;
}): Promise<void> {
    const hadCompleteSelection = datesComplete.value;

    unavailableDatesLoading.value = true;
    unavailableDatesError.value = null;

    try {
        const response = await fetch(unavailableDates.url({ vehicle: props.vehicle.slug }), {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Unavailable dates request failed');
        }

        const ranges = (await response.json()) as UnavailableRange[];
        unavailableDateSet.value = expandUnavailableDates(ranges);

        if (
            options?.checkSelectionConflict
            && hadCompleteSelection
            && form.start_date !== ''
            && form.end_date !== ''
            && isSelectedRangeBlocked(form.start_date, form.end_date)
        ) {
            liveConflictWarning.value = true;
            clearDateSelection();
        }
    } catch {
        unavailableDateSet.value = new Set();
        unavailableDatesError.value = 'Unable to load unavailable dates. You can still pick dates manually.';
    } finally {
        unavailableDatesLoading.value = false;
    }
}

async function syncHold(): Promise<void> {
    if (!datesComplete.value) {
        return;
    }

    holdHttp.start_date = form.start_date;
    holdHttp.end_date = form.end_date;

    try {
        await holdHttp.submit(holdStore.post({ vehicle: props.vehicle.slug }));
    } catch {
        // Hold sync is best-effort; preview and live refetch reconcile availability.
    }
}

function readXsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function releaseHold(): void {
    if (typeof window === 'undefined') {
        return;
    }

    void fetch(holdDestroy.url({ vehicle: props.vehicle.slug }), {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': readXsrfToken(),
        },
        credentials: 'same-origin',
        keepalive: true,
    }).catch(() => {});
}

async function fetchPreview(): Promise<void> {
    if (!datesComplete.value) {
        preview.value = null;
        previewError.value = null;

        return;
    }

    const requestId = ++previewRequestId;
    previewLoading.value = true;
    previewError.value = null;

    previewHttp.start_date = form.start_date;
    previewHttp.end_date = form.end_date;
    previewHttp.with_operator = form.with_operator;
    previewHttp.extras = [...form.extras];

    try {
        const result = (await previewHttp.submit(
            pricePreview.post({ vehicle: props.vehicle.slug }),
        )) as PricePreviewResult;

        if (requestId !== previewRequestId) {
            return;
        }

        preview.value = result;
    } catch {
        if (requestId !== previewRequestId) {
            return;
        }

        preview.value = null;
        previewError.value = 'Unable to load price estimate. Please try again.';
    } finally {
        if (requestId === previewRequestId) {
            previewLoading.value = false;
        }
    }
}

function scheduleDebouncedSync(): void {
    if (debounceTimer !== null) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        void fetchPreview();
        void syncHold();
    }, DEBOUNCE_MS);
}

watch(
    () => [form.start_date, form.end_date, form.with_operator, form.extras] as const,
    () => {
        if (!datesComplete.value) {
            preview.value = null;
            previewError.value = null;
            previewRequestId++;
            releaseHold();

            return;
        }

        scheduleDebouncedSync();
    },
    { deep: true },
);

const availabilityChannel = `vehicle.${props.vehicle.id}.availability`;

function handleAvailabilityChanged(): void {
    void fetchUnavailableDates({ checkSelectionConflict: true });
}

onMounted(() => {
    void fetchUnavailableDates();

    echo()
        .channel(availabilityChannel)
        .listen('.VehicleAvailabilityChanged', handleAvailabilityChanged);
});

onUnmounted(() => {
    if (debounceTimer !== null) {
        clearTimeout(debounceTimer);
    }

    previewRequestId++;
    echo().leave(availabilityChannel);
    releaseHold();
});

function handleConfirmModalOpenChange(open: boolean): void {
    if (form.processing) {
        return;
    }

    confirmModalOpen.value = open;
}

function submit(): void {
    form.post(bookStore.url({ vehicle: props.vehicle.slug }), {
        onSuccess: () => {
            releaseHold();
        },
    });
}
</script>

<template>
    <Head :title="`Book ${vehicle.name}`" />

    <main class="mx-auto max-w-7xl px-6 py-8">
        <div class="space-y-8">
            <Button variant="outline" size="sm" class="w-fit gap-2" as-child>
                <Link :href="fleetShow({ vehicle: vehicle.slug })">
                    <ArrowLeft class="size-4 shrink-0" aria-hidden="true" />
                    Back to vehicle
                </Link>
            </Button>

            <div class="lg:grid lg:grid-cols-3 lg:items-start lg:gap-8">
                <div class="min-w-0 space-y-6 lg:col-span-2">
                    <Card
                        class="gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0"
                    >
                        <CardContent class="space-y-4 p-5">
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div class="min-w-0 space-y-1">
                                    <h1
                                        class="font-heading text-2xl font-semibold text-foreground"
                                    >
                                        {{ vehicle.name }}
                                    </h1>
                                    <p class="text-sm text-muted-foreground">
                                        {{ vehicle.category.name }}
                                    </p>
                                </div>
                                <Badge variant="secondary" class="shrink-0">
                                    {{ formatEur(vehicle.daily_rate) }} / day
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0"
                    >
                        <CardContent class="space-y-6 p-5">
                            <div class="space-y-1">
                                <h2
                                    class="font-heading text-lg font-semibold text-foreground"
                                >
                                    Rental dates
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Return date is the day you bring the
                                    vehicle back (not charged).
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Blocked dates are shown in red and cannot be selected.
                                </p>
                            </div>

                            <div class="space-y-3">
                                <Label>Pick-up and return dates</Label>
                                <RangeCalendar
                                    :model-value="selectedRangeForCalendar"
                                    @update:model-value="handleRangeUpdate"
                                    :number-of-months="2"
                                    :min-value="minBookingDate"
                                    :is-date-unavailable="isDateUnavailable"
                                    class="w-full rounded-md border"
                                />
                                <p
                                    v-if="unavailableDatesLoading"
                                    class="text-sm text-muted-foreground"
                                >
                                    Loading unavailable dates...
                                </p>
                                <p
                                    v-else-if="unavailableDatesError"
                                    class="text-sm text-destructive"
                                >
                                    {{ unavailableDatesError }}
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-foreground">
                                        Pick-up date
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ form.start_date || 'Not selected' }}
                                    </p>
                                    <p
                                        v-if="form.errors.start_date"
                                        class="text-sm text-destructive"
                                    >
                                        {{ form.errors.start_date }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-foreground">
                                        Return date
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ form.end_date || 'Not selected' }}
                                    </p>
                                    <p
                                        v-if="form.errors.end_date"
                                        class="text-sm text-destructive"
                                    >
                                        {{ form.errors.end_date }}
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="datesError"
                                class="text-sm text-destructive"
                            >
                                {{ datesError }}
                            </p>

                            <div
                                v-if="vehicle.available_with_operator"
                                class="space-y-3 border-t border-border pt-6"
                            >
                                <div class="flex items-start gap-3">
                                    <Checkbox
                                        id="with_operator"
                                        :model-value="form.with_operator"
                                        @update:model-value="
                                            (checked) =>
                                                (form.with_operator =
                                                    checked === true)
                                        "
                                    />
                                    <div class="space-y-1">
                                        <Label
                                            for="with_operator"
                                            class="cursor-pointer font-normal"
                                        >
                                            Include operator
                                        </Label>
                                        <p
                                            v-if="
                                                vehicle.operator_daily_rate
                                                    !== null
                                            "
                                            class="text-sm text-muted-foreground"
                                        >
                                            Operator rate:
                                            {{
                                                formatEur(
                                                    vehicle.operator_daily_rate,
                                                )
                                            }}
                                            / day
                                        </p>
                                    </div>
                                </div>
                                <p
                                    v-if="form.errors.with_operator"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.with_operator }}
                                </p>
                            </div>

                            <div
                                v-if="extras.length > 0"
                                class="space-y-4 border-t border-border pt-6"
                            >
                                <h2
                                    class="font-heading text-lg font-semibold text-foreground"
                                >
                                    Extras
                                </h2>

                                <div class="space-y-3">
                                    <div
                                        v-for="extra in extras"
                                        :key="extra.id"
                                        class="flex items-start gap-3"
                                    >
                                        <Checkbox
                                            :id="`extra-${extra.id}`"
                                            :model-value="
                                                isExtraSelected(extra.id)
                                            "
                                            @update:model-value="
                                                (checked) =>
                                                    toggleExtra(
                                                        extra.id,
                                                        checked,
                                                    )
                                            "
                                        />
                                        <Label
                                            :for="`extra-${extra.id}`"
                                            class="flex flex-1 cursor-pointer items-baseline justify-between gap-3 font-normal"
                                        >
                                            <span>{{ extra.name }}</span>
                                            <span
                                                class="shrink-0 text-sm text-muted-foreground"
                                            >
                                                {{ formatExtraPrice(extra) }}
                                            </span>
                                        </Label>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <aside class="mt-8 lg:col-span-1 lg:mt-0">
                    <Card
                        class="gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0 lg:sticky lg:top-24"
                    >
                        <CardContent class="space-y-5 p-5">
                            <h2
                                class="font-heading text-lg font-semibold text-foreground"
                            >
                                Price estimate
                            </h2>

                            <div
                                v-if="liveConflictWarning"
                                class="flex gap-3 rounded-lg border border-amber-300/80 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-200"
                                role="alert"
                            >
                                <AlertTriangle
                                    class="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <p>
                                    This vehicle is not available for the
                                    selected dates. Please choose different
                                    dates.
                                </p>
                            </div>

                            <div
                                v-else-if="!datesComplete"
                                class="text-sm text-muted-foreground"
                            >
                                Select pick-up and return dates to see your
                                estimate.
                            </div>

                            <div
                                v-else-if="previewLoading && !preview"
                                class="text-sm text-muted-foreground"
                            >
                                Calculating…
                            </div>

                            <div
                                v-else-if="previewError"
                                class="text-sm text-destructive"
                            >
                                {{ previewError }}
                            </div>

                            <template v-else-if="preview">
                                <div
                                    v-if="isPreviewUnavailable"
                                    class="flex gap-3 rounded-lg border border-amber-300/80 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-200"
                                    role="alert"
                                >
                                    <AlertTriangle
                                        class="mt-0.5 size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <p>
                                        This vehicle is not available for the
                                        selected dates. Please choose different
                                        dates.
                                    </p>
                                </div>

                                <ul class="space-y-2 text-sm">
                                    <li
                                        v-for="(line, index) in preview.breakdown"
                                        :key="`${line.label}-${index}`"
                                        class="flex items-baseline justify-between gap-3"
                                    >
                                        <span class="text-muted-foreground">
                                            {{ line.label }}
                                        </span>
                                        <span class="font-medium text-foreground">
                                            {{ formatEur(line.amount) }}
                                        </span>
                                    </li>
                                </ul>

                                <div
                                    class="flex items-baseline justify-between gap-3 border-t border-border pt-4"
                                >
                                    <span
                                        class="font-heading text-base font-semibold text-foreground"
                                    >
                                        Total
                                    </span>
                                    <span
                                        class="font-heading text-xl font-semibold text-foreground"
                                    >
                                        {{ formatEur(preview.total_price) }}
                                    </span>
                                </div>
                            </template>

                            <Button
                                type="button"
                                variant="default"
                                size="lg"
                                class="w-full"
                                :disabled="!canOpenConfirmModal"
                                @click="confirmModalOpen = true"
                            >
                                Request booking
                            </Button>

                            <p class="text-xs text-muted-foreground">
                                No payment is taken now. We will contact you to
                                confirm your booking.
                            </p>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </main>

    <Dialog
        :open="confirmModalOpen"
        @update:open="handleConfirmModalOpenChange"
    >
        <DialogContent
            class="max-h-[90vh] overflow-y-auto sm:max-w-lg"
            :show-close-button="!form.processing"
        >
            <DialogHeader class="space-y-3">
                <DialogTitle class="font-heading text-xl">
                    Confirm booking request
                </DialogTitle>
                <DialogDescription>
                    Review your rental details before submitting. No payment is
                    taken now — we will contact you to confirm availability.
                </DialogDescription>
            </DialogHeader>

            <div v-if="preview" class="space-y-5 text-sm">
                <div class="space-y-1">
                    <p class="font-heading text-base font-semibold text-foreground">
                        {{ vehicle.name }}
                    </p>
                    <p class="text-muted-foreground">
                        {{ vehicle.category.name }}
                    </p>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <dt class="text-muted-foreground">Pick-up date</dt>
                        <dd class="font-medium text-foreground">
                            {{ formatDate(form.start_date) }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-muted-foreground">
                            Return date
                        </dt>
                        <dd class="font-medium text-foreground">
                            {{ formatDate(form.end_date) }}
                        </dd>
                        <dd class="text-xs text-muted-foreground">
                            Vehicle must be returned by end of this date
                            (exclusive-end — not charged).
                        </dd>
                    </div>
                </dl>

                <div
                    v-if="form.with_operator"
                    class="space-y-1 border-t border-border pt-4"
                >
                    <p class="font-medium text-foreground">Operator</p>
                    <p class="text-muted-foreground">Included</p>
                </div>

                <div
                    v-if="selectedExtras.length > 0"
                    class="space-y-2 border-t border-border pt-4"
                >
                    <p class="font-medium text-foreground">Extras</p>
                    <ul class="space-y-2">
                        <li
                            v-for="extra in selectedExtras"
                            :key="extra.id"
                            class="flex items-baseline justify-between gap-3"
                        >
                            <span class="text-muted-foreground">
                                {{ extra.name }}
                            </span>
                            <span class="font-medium text-foreground">
                                {{ formatExtraPrice(extra) }}
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-3 border-t border-border pt-4">
                    <p class="font-medium text-foreground">Price breakdown</p>
                    <ul class="space-y-2">
                        <li
                            v-for="(line, index) in preview.breakdown"
                            :key="`${line.label}-${index}`"
                            class="flex items-baseline justify-between gap-3"
                        >
                            <span class="text-muted-foreground">
                                {{ line.label }}
                            </span>
                            <span class="font-medium text-foreground">
                                {{ formatEur(line.amount) }}
                            </span>
                        </li>
                    </ul>
                    <div
                        class="flex items-baseline justify-between gap-3 border-t border-border pt-3"
                    >
                        <span
                            class="font-heading text-base font-semibold text-foreground"
                        >
                            Total
                        </span>
                        <span
                            class="font-heading text-lg font-semibold text-foreground"
                        >
                            {{ formatEur(preview.total_price) }}
                        </span>
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button
                    type="button"
                    variant="secondary"
                    :disabled="form.processing"
                    @click="confirmModalOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    class="gap-2"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Spinner v-if="form.processing" />
                    {{
                        form.processing
                            ? 'Submitting…'
                            : 'Confirm request'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
