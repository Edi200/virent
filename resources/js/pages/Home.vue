<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import RentalProfileModal from '@/components/RentalProfileModal.vue';
import TextLink from '@/components/TextLink.vue';
import ViRentWordmark from '@/components/ViRentWordmark.vue';
import { Button } from '@/components/ui/button';
import { login, logout, register } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const showRentalProfileModal = ref(false);

function handleFlash(event: Event): void {
    const flash = (event as CustomEvent).detail?.flash;

    if (flash?.showRentalProfileModal) {
        showRentalProfileModal.value = true;
    }
}

function handleLogout(): void {
    router.flushAll();
}

let unsubscribeFlash: (() => void) | undefined;

onMounted(() => {
    unsubscribeFlash = router.on('flash', handleFlash);
});

onUnmounted(() => {
    unsubscribeFlash?.();
});
</script>

<template>
    <Head title="ViRent" />

    <div class="min-h-svh bg-background">
        <header class="border-b border-border/60">
            <div
                class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6"
            >
                <ViRentWordmark class="text-2xl sm:text-3xl" />

                <nav class="flex items-center gap-3 text-sm">
                    <template v-if="user">
                        <TextLink :href="profileEdit()">Account</TextLink>
                        <Link
                            :href="logout()"
                            as="button"
                            class="text-primary underline decoration-primary/30 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-primary"
                            @click="handleLogout"
                        >
                            Log out
                        </Link>
                    </template>
                    <template v-else>
                        <TextLink :href="login()">Log in</TextLink>
                        <Button
                            as-child
                            size="sm"
                            class="bg-accent text-accent-foreground hover:bg-accent/90"
                        >
                            <Link :href="register()">Sign up</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main
            class="mx-auto flex max-w-5xl flex-col items-center px-6 py-16 text-center md:py-24"
        >
            <div
                class="w-full max-w-2xl space-y-6 rounded-xl border border-border/60 border-t-2 border-t-accent/45 bg-card px-8 py-12 shadow-sm"
            >
                <div class="flex justify-center">
                    <ViRentWordmark class="text-4xl sm:text-5xl" />
                </div>

                <p class="font-heading text-xl font-medium tracking-tight text-foreground md:text-2xl">
                    Cars, vans, and work machinery — one rental platform for
                    every job.
                </p>

                <p class="text-sm text-muted-foreground md:text-base">
                    ViRent brings passenger cars, commercial vans, and operator
                    machinery into a single booking experience.
                </p>
            </div>
        </main>

        <RentalProfileModal v-if="user" v-model:open="showRentalProfileModal" />
    </div>
</template>
