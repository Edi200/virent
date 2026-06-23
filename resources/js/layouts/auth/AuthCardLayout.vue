<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ViRentWordmark from '@/components/ViRentWordmark.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="absolute top-4 right-4 md:top-6 md:right-6">
            <AppearanceTabs />
        </div>

        <div class="flex w-full max-w-md flex-col gap-6">
            <div class="flex justify-center">
                <ViRentWordmark />
            </div>

            <Card class="rounded-xl border-t-2 border-t-accent/45">
                <CardHeader class="px-10 pt-8 pb-0 text-center">
                    <CardTitle class="font-heading text-xl">{{ title }}</CardTitle>
                    <CardDescription>
                        {{ description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="px-10 py-8">
                    <slot />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
