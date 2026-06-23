<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        link?: boolean;
    }>(),
    {
        link: true,
    },
);

const wordmarkClass = cn(
    'font-wordmark text-3xl font-bold tracking-[0.02em] uppercase sm:text-4xl',
    props.class,
);
</script>

<template>
    <Link
        v-if="link"
        :href="home()"
        aria-label="ViRent"
        :class="wordmarkClass"
    >
        <span class="text-[#5E244E] dark:text-[#F3EEF5]">Vi</span
        ><span class="text-[#E68457] dark:text-[#F3EEF5]">Rent</span>
    </Link>
    <span v-else :class="wordmarkClass" aria-hidden="true">
        <span class="text-[#5E244E] dark:text-[#F3EEF5]">Vi</span
        ><span class="text-[#E68457] dark:text-[#F3EEF5]">Rent</span>
    </span>
</template>
