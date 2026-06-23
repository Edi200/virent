<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import RentalProfileController from '@/actions/App/Http/Controllers/Settings/RentalProfileController';
import Heading from '@/components/Heading.vue';
import RentalProfileFields from '@/components/RentalProfileFields.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { RentalProfile, RentalProfileForm } from '@/types';

const props = defineProps<{
    customer: RentalProfile;
}>();

function toFormValue(value: string | null): string {
    return value ?? '';
}

const form = useForm<RentalProfileForm>({
    driver_license_number: toFormValue(props.customer.driver_license_number),
    license_expiry: toFormValue(props.customer.license_expiry),
    company_name: toFormValue(props.customer.company_name),
    tax_number: toFormValue(props.customer.tax_number),
    address: toFormValue(props.customer.address),
});

function submit(): void {
    form.patch(RentalProfileController.update.url(), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Rental profile" />

    <h1 class="sr-only">Rental profile</h1>

    <div class="max-w-xl">
        <Card class="rounded-xl border-t-2 border-t-accent/45">
            <CardContent class="space-y-6 px-6 py-6">
                <Heading
                    variant="small"
                    title="Rental profile"
                    description="License and billing details for your rentals. All fields are optional."
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <RentalProfileFields :form="form" />

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            Save rental profile
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
