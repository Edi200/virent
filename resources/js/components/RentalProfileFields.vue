<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { RentalProfileForm } from '@/types';

type RentalProfileFormState = RentalProfileForm & {
    errors: Partial<Record<keyof RentalProfileForm, string>>;
};

const { form, idPrefix = '' } = defineProps<{
    form: RentalProfileFormState;
    idPrefix?: string;
}>();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="`${idPrefix}driver_license_number`">
            Driver license number
        </Label>
        <Input
            :id="`${idPrefix}driver_license_number`"
            v-model="form.driver_license_number"
            type="text"
            autocomplete="off"
            class="font-mono"
            placeholder="e.g. PL12345678"
        />
        <InputError :message="form.errors.driver_license_number" />
    </div>

    <div class="grid gap-2">
        <Label :for="`${idPrefix}license_expiry`">License expiry</Label>
        <Input
            :id="`${idPrefix}license_expiry`"
            v-model="form.license_expiry"
            type="date"
        />
        <InputError :message="form.errors.license_expiry" />
    </div>

    <div class="grid gap-2">
        <Label :for="`${idPrefix}company_name`">Company name</Label>
        <Input
            :id="`${idPrefix}company_name`"
            v-model="form.company_name"
            type="text"
            autocomplete="organization"
            placeholder="Optional"
        />
        <InputError :message="form.errors.company_name" />
    </div>

    <div class="grid gap-2">
        <Label :for="`${idPrefix}tax_number`">Tax number</Label>
        <Input
            :id="`${idPrefix}tax_number`"
            v-model="form.tax_number"
            type="text"
            autocomplete="off"
            placeholder="Optional"
        />
        <InputError :message="form.errors.tax_number" />
    </div>

    <div class="grid gap-2">
        <Label :for="`${idPrefix}address`">Address</Label>
        <textarea
            :id="`${idPrefix}address`"
            v-model="form.address"
            rows="3"
            autocomplete="street-address"
            placeholder="Optional"
            :class="
                cn(
                    'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                )
            "
        />
        <InputError :message="form.errors.address" />
    </div>
</template>
