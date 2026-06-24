<script setup lang="ts">
import { Slider } from '@/components/ui/slider';
import type { RangeBounds } from '@/composables/useFleetFilters';

withDefaults(
    defineProps<{
        bounds: RangeBounds;
        modelValue: [number, number];
        formatValue?: (value: number) => string;
    }>(),
    {
        formatValue: (value: number) => String(value),
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: [number, number]];
}>();

function onUpdate(value: number[] | undefined): void {
    if (!value || value.length < 2) {
        return;
    }

    emit('update:modelValue', [value[0]!, value[1]!]);
}
</script>

<template>
    <div v-if="bounds.max > bounds.min" class="space-y-3">
        <div
            class="flex items-center justify-between text-xs tabular-nums text-muted-foreground"
        >
            <span>{{ formatValue(modelValue[0]) }}</span>
            <span>{{ formatValue(modelValue[1]) }}</span>
        </div>
        <Slider
            :min="bounds.min"
            :max="bounds.max"
            :step="bounds.step"
            :model-value="modelValue"
            @update:model-value="onUpdate"
        />
    </div>
    <p v-else class="text-xs tabular-nums text-muted-foreground">
        {{ formatValue(bounds.min) }}
    </p>
</template>
