<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

type VehicleImage = {
    src: string;
    width: number;
    height: number;
    alt: string;
};

defineProps<{
    vehicle: {
        name: string;
        images: VehicleImage[];
    };
}>();
</script>

<template>
    <Head :title="vehicle.name" />

    <main class="mx-auto max-w-7xl px-6 py-8">
        <div class="space-y-6">
            <h1 class="font-heading text-2xl font-semibold text-foreground">
                {{ vehicle.name }}
            </h1>

            <div
                v-if="vehicle.images.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="(image, index) in vehicle.images"
                    :key="`${image.src}-${index}`"
                    class="overflow-hidden rounded-xl border border-border/60 bg-muted"
                >
                    <img
                        :src="image.src"
                        :alt="image.alt"
                        :width="image.width"
                        :height="image.height"
                        loading="lazy"
                        class="aspect-video size-full object-cover"
                    />
                </div>
            </div>
        </div>
    </main>
</template>
