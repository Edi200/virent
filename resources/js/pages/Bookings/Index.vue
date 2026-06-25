<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FleetPagination from '@/components/FleetPagination.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useBookingDisplay } from '@/composables/useBookingDisplay';
import { home } from '@/routes';
import { show } from '@/routes/bookings';

type BookingRow = {
    id: number;
    reference: string;
    vehicle: {
        name: string;
    };
    start_date: string;
    end_date: string;
    status: string;
    total_price: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type LaravelPaginator = {
    data: BookingRow[];
    last_page: number;
    links: PaginationLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
};

defineProps<{
    bookings: LaravelPaginator;
}>();

const { formatEur, formatDate, statusLabel, statusBadgeClass } =
    useBookingDisplay();
</script>

<template>
    <Head title="Bookings" />

    <h1 class="sr-only">Bookings</h1>

    <div class="space-y-8">
        <Heading
            title="Bookings"
            description="View and track your rental reservations"
        />

        <div v-if="bookings.data.length > 0" class="space-y-4">
            <Link
                v-for="booking in bookings.data"
                :key="booking.id"
                :href="show({ booking: booking.id })"
                class="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <Card
                    class="gap-0 overflow-hidden rounded-xl border-t-2 border-t-accent/45 py-0 transition-shadow hover:shadow-md"
                >
                    <CardContent
                        class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0 space-y-2">
                            <p class="font-mono text-sm text-muted-foreground">
                                {{ booking.reference }}
                            </p>
                            <p
                                class="font-heading text-lg font-semibold text-foreground"
                            >
                                {{ booking.vehicle.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDate(booking.start_date) }}
                                —
                                {{ formatDate(booking.end_date) }}
                            </p>
                        </div>

                        <div
                            class="flex shrink-0 flex-wrap items-center gap-3 sm:flex-col sm:items-end"
                        >
                            <Badge
                                variant="outline"
                                :class="statusBadgeClass(booking.status)"
                            >
                                {{ statusLabel(booking.status) }}
                            </Badge>
                            <p
                                class="font-heading text-lg font-semibold text-foreground"
                            >
                                {{ formatEur(booking.total_price) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </Link>

            <FleetPagination :paginator="bookings" />
        </div>

        <div
            v-else
            class="rounded-xl border border-border/60 border-t-2 border-t-accent/45 bg-card px-8 py-12 text-center shadow-sm"
        >
            <p class="font-heading text-lg font-medium text-foreground">
                You have no bookings yet
            </p>
            <p class="mt-2 text-sm text-muted-foreground">
                Browse our fleet to find a vehicle and make your first
                reservation.
            </p>
            <Button variant="outline" size="sm" class="mt-6" as-child>
                <Link :href="home()">Browse fleet</Link>
            </Button>
        </div>
    </div>
</template>
