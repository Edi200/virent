<script setup lang="ts">
import ViRentWordmark from '@/components/ViRentWordmark.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-primary p-10 text-primary-foreground lg:flex dark:border-r"
        >
            <div class="relative z-20">
                <ViRentWordmark />
            </div>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center gap-6 sm:w-[350px]"
            >
                <div class="flex flex-col gap-2 text-center">
                    <h1
                        v-if="title"
                        class="font-heading text-xl font-medium tracking-tight"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
