<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import RentalProfileController from '@/actions/App/Http/Controllers/Settings/RentalProfileController';
import RentalProfileFields from '@/components/RentalProfileFields.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { RentalProfileForm } from '@/types';

const open = defineModel<boolean>('open', { required: true });

const form = useForm<RentalProfileForm>({
    driver_license_number: '',
    license_expiry: '',
    company_name: '',
    tax_number: '',
    address: '',
});

function close(): void {
    open.value = false;
}

function submit(): void {
    form.patch(RentalProfileController.update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            close();
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader class="space-y-3">
                    <DialogTitle class="font-heading text-xl">
                        Complete your rental profile
                    </DialogTitle>
                    <DialogDescription>
                        Add your license and billing details so you're ready to
                        book. You can skip this for now and finish later in
                        Settings.
                    </DialogDescription>
                </DialogHeader>

                <RentalProfileFields :form="form" id-prefix="modal-" />

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="close"
                    >
                        Skip for now
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Save rental profile
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
