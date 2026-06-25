<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Clock, XCircle } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useBookingDisplay } from '@/composables/useBookingDisplay';
import { index as bookingsIndex } from '@/routes/bookings';
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

const { formatEur, formatDate, statusLabel, statusBadgeClass } =
    useBookingDisplay();

const heroHeading = computed((): string => {
    switch (props.booking.status) {
        case 'pending':
            return 'Booking request received';
        case 'confirmed':
            return 'Booking confirmed';
        case 'active':
            return 'Rental in progress';
        case 'completed':
            return 'Rental completed';
        case 'cancelled':
            return 'Booking cancelled';
        default:
            return 'Booking';
    }
});

const nextStepsCopy = computed((): string => {
    switch (props.booking.status) {
        case 'pending':
            return 'Your booking is pending review. We will be in touch to confirm availability and next steps. No payment has been taken — the deposit will only be requested once your booking is confirmed.';
        case 'confirmed':
            return 'Your booking is confirmed. We will contact you shortly regarding the deposit and pick-up arrangements. Please have your driving licence and any required documents ready.';
        case 'active':
            return 'Your rental is currently active. Enjoy your vehicle, and remember to return it by the agreed date. Contact us if you need any assistance during your rental.';
        case 'completed':
            return 'Thank you for renting with us. This booking is complete. We hope you had a great experience.';
        case 'cancelled':
            return 'This booking has been cancelled. If you have any questions or would like to make a new reservation, please get in touch or browse our fleet.';
        default:
            return '';
    }
});

const heroIcon = computed(() => {
    switch (props.booking.status) {
        case 'cancelled':
            return XCircle;
        case 'pending':
            return Clock;
        default:
            return CheckCircle2;
    }
});

const heroIconClass = computed((): string => {
    switch (props.booking.status) {
        case 'cancelled':
            return 'bg-destructive/10 text-destructive';
        case 'pending':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
        default:
            return 'bg-primary/10 text-primary';
    }
});

const showDepositNote = computed(
    () =>
        props.booking.status === 'pending'
        || props.booking.status === 'confirmed',
);

const showNextSteps = computed(
    () => props.booking.status !== 'completed',
);
</script>

<template>
    <Head :title="`Booking ${booking.reference}`" />

    <h1 class="sr-only">{{ heroHeading }}</h1>

    <div class="mx-auto max-w-3xl space-y-8">
        <div class="space-y-3 text-center">
            <div
                class="mx-auto flex size-12 items-center justify-center rounded-full"
                :class="heroIconClass"
            >
                <component :is="heroIcon" class="size-6" aria-hidden="true" />
            </div>
            <h2
                class="font-heading text-2xl font-semibold text-foreground md:text-3xl"
            >
                {{ heroHeading }}
            </h2>
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
                        <h3
                            class="font-heading text-lg font-semibold text-foreground"
                        >
                            {{ vehicle.name }}
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ vehicle.category.name }}
                        </p>
                    </div>
                    <Badge
                        variant="outline"
                        :class="statusBadgeClass(booking.status)"
                    >
                        {{ statusLabel(booking.status) }}
                    </Badge>
                </div>

                <dl
                    class="grid gap-4 border-t border-border pt-6 sm:grid-cols-2"
                >
                    <div class="space-y-1">
                        <dt class="text-sm text-muted-foreground">Pick-up</dt>
                        <dd class="text-sm font-medium text-foreground">
                            {{ formatDate(booking.start_date) }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-sm text-muted-foreground">Return</dt>
                        <dd class="text-sm font-medium text-foreground">
                            {{ formatDate(booking.end_date) }}
                        </dd>
                    </div>
                    <div v-if="booking.with_operator" class="space-y-1">
                        <dt class="text-sm text-muted-foreground">Operator</dt>
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

                    <p
                        v-if="showDepositNote"
                        class="text-sm text-muted-foreground"
                    >
                        Deposit (due on confirmation):
                        <span class="font-medium text-foreground">
                            {{ formatEur(booking.deposit_amount) }}
                        </span>
                        — not charged today.
                    </p>
                </div>

                <div
                    v-if="showNextSteps"
                    class="space-y-2 rounded-lg bg-muted/60 p-4 text-sm text-muted-foreground"
                >
                    <p class="font-medium text-foreground">What happens next</p>
                    <p>{{ nextStepsCopy }}</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button class="w-full sm:w-auto" as-child>
                        <Link :href="bookingsIndex()">
                            View all bookings
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        class="w-full sm:w-auto"
                        as-child
                    >
                        <Link :href="fleetShow({ vehicle: vehicle.slug })">
                            Back to vehicle
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
