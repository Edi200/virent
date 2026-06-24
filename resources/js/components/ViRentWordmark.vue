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
    'font-wordmark text-3xl font-bold tracking-[0.02em] uppercase text-primary sm:text-4xl',
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
        ViRent
    </Link>
    <span v-else :class="wordmarkClass" aria-hidden="true">ViRent</span>
</template>
