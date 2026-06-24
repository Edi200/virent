<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Laravel LengthAwarePaginator JSON shape (flat, no meta wrapper). */
type LaravelPaginator = {
    last_page: number;
    links: PaginationLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    paginator: LaravelPaginator;
}>();

const pageLinks = computed(() =>
    props.paginator.links.filter((link) => /^\d+$/.test(link.label.trim())),
);

const showPagination = computed(() => props.paginator.last_page > 1);
</script>

<template>
    <nav
        v-if="showPagination"
        class="flex items-center justify-center gap-1"
        aria-label="Fleet pagination"
    >
        <Button
            variant="outline"
            size="sm"
            class="gap-1"
            :disabled="!paginator.prev_page_url"
            as-child
        >
            <Link
                v-if="paginator.prev_page_url"
                :href="paginator.prev_page_url"
                preserve-state
                preserve-scroll
                class="inline-flex items-center gap-1"
            >
                <ChevronLeft class="size-4 shrink-0" />
                Previous
            </Link>
            <span v-else class="inline-flex items-center gap-1">
                <ChevronLeft class="size-4 shrink-0" />
                Previous
            </span>
        </Button>

        <Button
            v-for="(link, index) in pageLinks"
            :key="`page-${index}`"
            variant="outline"
            size="sm"
            class="min-w-9"
            :class="[
                link.active &&
                    'border-primary/20 bg-primary/10 text-primary dark:bg-primary/15',
            ]"
            as-child
        >
            <Link
                :href="link.url!"
                preserve-state
                preserve-scroll
            >
                {{ link.label }}
            </Link>
        </Button>

        <Button
            variant="outline"
            size="sm"
            class="gap-1"
            :disabled="!paginator.next_page_url"
            as-child
        >
            <Link
                v-if="paginator.next_page_url"
                :href="paginator.next_page_url"
                preserve-state
                preserve-scroll
                class="inline-flex items-center gap-1"
            >
                Next
                <ChevronRight class="size-4 shrink-0" />
            </Link>
            <span v-else class="inline-flex items-center gap-1">
                Next
                <ChevronRight class="size-4 shrink-0" />
            </span>
        </Button>
    </nav>
</template>
