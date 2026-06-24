<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2 } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show as fleetShow } from '@/routes/fleet';

type BreakdownLine = {
    label: string;
    amount: string;
};

type BookingConfirmation = {
    reference: string;
    status: string;
    start_date: string;
    end_date: string;
    total_price: string;
    deposit_amount: string;
    with_operator: boolean;
    pricing_breakdown: BreakdownLine[];
};

type VehicleSummary = {
    slug: string;
    name: string;
    category: {
        name: string;
        slug: string;
    };
};

const props = defineProps<{
    booking: BookingConfirmation;
    vehicle: VehicleSummary;
}>();

const eurFormatter = new Intl.NumberFormat('en-EU', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

function formatEur(amount: string | number): string {
    return eurFormatter.format(Number(amount));
}

function formatDate(date: string): string {
    return dateFormatter.format(new Date(`${date}T00:00:00`));
}

const statusLabel = computed(() => {
    return props.booking.status.charAt(0).toUpperCase()
        + props.booking.status.slice(1);
});

const statusBadgeClass = computed(() => {
    switch (props.booking.status) {
        case 'pending':
            return 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-300';
        case 'confirmed':
            return 'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-700 dark:bg-blue-950/50 dark:text-blue-300';
        case 'active':
            return 'border-transparent bg-primary text-primary-foreground';
        case 'completed':
            return 'border-green-300 bg-green-50 text-green-800 dark:border-green-700 dark:bg-green-950/50 dark:text-green-300';
        case 'cancelled':
            return 'border-transparent bg-destructive text-white';
        default:
            return '';
    }
});
</script>

<template>
    <Head :title="`Booking ${booking.reference}`" />

    <main class="mx-auto max-w-3xl px-6 py-8">
        <div class="space-y-8">
            <div class="space-y-3 text-center">
                <div
                    class="mx-auto flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary"
                >
                    <CheckCircle2 class="size-6" aria-hidden="true" />
                </div>
                <h1
                    class="font-heading text-2xl font-semibold text-foreground md:text-3xl"
                >
                    Booking request received
                </h1>
                <p class="font-mono text-sm text-muted-foreground">
                    {{ booking.reference }}
                </p>
            </div>

            <Card
                class="gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0"
            >
                <CardContent class="space-y-6 p-5 md:p-6">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="space-y-1">
                            <h2
                                class="font-heading text-lg font-semibold text-foreground"
                            >
                                {{ vehicle.name }}
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                {{ vehicle.category.name }}
                            </p>
                        </div>
                        <Badge variant="outline" :class="statusBadgeClass">
                            {{ statusLabel }}
                        </Badge>
                    </div>

                    <dl class="grid gap-4 border-t border-border pt-6 sm:grid-cols-2">
                        <div class="space-y-1">
                            <dt class="text-sm text-muted-foreground">
                                Pick-up
                            </dt>
                            <dd class="text-sm font-medium text-foreground">
                                {{ formatDate(booking.start_date) }}
                            </dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-sm text-muted-foreground">
                                Return
                            </dt>
                            <dd class="text-sm font-medium text-foreground">
                                {{ formatDate(booking.end_date) }}
                            </dd>
                        </div>
                        <div v-if="booking.with_operator" class="space-y-1">
                            <dt class="text-sm text-muted-foreground">
                                Operator
                            </dt>
                            <dd class="text-sm font-medium text-foreground">
                                Included
                            </dd>
                        </div>
                    </dl>

                    <div class="space-y-3 border-t border-border pt-6">
                        <h3
                            class="font-heading text-base font-semibold text-foreground"
                        >
                            Price breakdown
                        </h3>

                        <ul class="space-y-2 text-sm">
                            <li
                                v-for="(line, index) in booking.pricing_breakdown"
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
                                {{ formatEur(booking.total_price) }}
                            </span>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Deposit (due on confirmation):
                            <span class="font-medium text-foreground">
                                {{ formatEur(booking.deposit_amount) }}
                            </span>
                            — not charged today.
                        </p>
                    </div>

                    <div
                        class="space-y-2 rounded-lg bg-muted/60 p-4 text-sm text-muted-foreground"
                    >
                        <p class="font-medium text-foreground">
                            What happens next
                        </p>
                        <p>
                            Your booking is pending review. We will be in touch
                            to confirm availability and next steps. No payment
                            has been taken — the deposit will only be
                            requested once your booking is confirmed.
                        </p>
                    </div>

                    <Button variant="outline" class="w-full sm:w-auto" as-child>
                        <Link :href="fleetShow({ vehicle: vehicle.slug })">
                            Back to vehicle
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </main>
</template>
