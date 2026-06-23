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
        :class="
            cn(
                'font-heading text-2xl tracking-tight text-primary lowercase',
                props.class,
            )
        "
    >
        virent
    </Link>
</template>
