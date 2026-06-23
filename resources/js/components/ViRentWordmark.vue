<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <Link
        href="/"
        aria-label="ViRent"
        :class="
            cn(
                'font-heading text-3xl font-bold tracking-[0.02em] uppercase sm:text-4xl',
                props.class,
            )
        "
    >
        <span class="text-[#5E244E] dark:text-[#C97FAE]">Vi</span
        ><span class="text-[#E68457]">Rent</span>
    </Link>
</template>
