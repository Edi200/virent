<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    IdCard,
    LogOut,
    Palette,
    ShieldCheck,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import ViRentWordmark from '@/components/ViRentWordmark.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { logout } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editRentalProfile } from '@/routes/rental-profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem, UserRole } from '@/types';

const page = usePage();
const { isCurrentOrParentUrl } = useCurrentUrl();

const navItems = computed((): NavItem[] => {
    const items: NavItem[] = [
        {
            title: 'Account',
            href: editProfile(),
            icon: User,
        },
    ];

    if ((page.props.auth.user?.role as UserRole | undefined) === 'customer') {
        items.push({
            title: 'Rental profile',
            href: editRentalProfile(),
            icon: IdCard,
        });
    }

    items.push(
        {
            title: 'Security',
            href: editSecurity(),
            icon: ShieldCheck,
        },
        {
            title: 'Appearance',
            href: editAppearance(),
            icon: Palette,
        },
    );

    return items;
});

function handleLogout(): void {
    router.flushAll();
}
</script>

<template>
    <div class="min-h-svh bg-background">
        <header class="border-b border-border/60">
            <div
                class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-6"
            >
                <ViRentWordmark class="text-2xl sm:text-3xl" />

                <nav class="flex flex-wrap items-center justify-end gap-1">
                    <Button
                        v-for="item in navItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        size="sm"
                        :class="[
                            'h-9',
                            {
                                'bg-primary/10 text-primary dark:bg-primary/15 dark:text-[#C97FAE]':
                                    isCurrentOrParentUrl(item.href),
                            },
                        ]"
                        as-child
                    >
                        <Link
                            :href="item.href"
                            class="inline-flex items-center gap-2"
                        >
                            <component
                                :is="item.icon"
                                class="size-4 shrink-0"
                            />
                            {{ item.title }}
                        </Link>
                    </Button>

                    <Button variant="ghost" size="sm" class="h-9" as-child>
                        <Link
                            :href="logout()"
                            as="button"
                            class="inline-flex items-center gap-2"
                            data-test="logout-button"
                            @click="handleLogout"
                        >
                            <LogOut class="size-4 shrink-0" />
                            Log out
                        </Link>
                    </Button>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <slot />
        </main>

        <Toaster />
    </div>
</template>
