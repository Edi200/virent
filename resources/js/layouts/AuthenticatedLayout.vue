<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    House,
    IdCard,
    Menu,
    LogOut,
    Palette,
    ShieldCheck,
    User,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import ViRentWordmark from '@/components/ViRentWordmark.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { home, logout } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editRentalProfile } from '@/routes/rental-profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem, UserRole } from '@/types';

const page = usePage();
const { isCurrentOrParentUrl } = useCurrentUrl();
const isMobileNavOpen = ref(false);

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

function closeMobileNav(): void {
    isMobileNavOpen.value = false;
}
</script>

<template>
    <div class="min-h-svh bg-background md:flex">
        <header class="border-b border-border/60 md:hidden">
            <div
                class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-6"
            >
                <ViRentWordmark class="text-2xl sm:text-3xl" />

                <Sheet v-model:open="isMobileNavOpen">
                    <SheetTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Open navigation menu"
                        >
                            <Menu class="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        class="w-[280px] gap-2 px-4 pt-4 pb-4 sm:w-[320px]"
                    >
                        <SheetHeader class="gap-0 p-0 pb-2">
                            <SheetTitle class="font-normal leading-none">
                                <span class="sr-only">ViRent</span>
                                <ViRentWordmark
                                    :link="false"
                                    class="text-2xl sm:text-3xl"
                                />
                            </SheetTitle>
                        </SheetHeader>
                        <nav class="flex flex-col gap-1">
                            <Button
                                variant="ghost"
                                class="h-10 justify-start"
                                as-child
                            >
                                <Link
                                    :href="home()"
                                    class="inline-flex items-center gap-2"
                                    @click="closeMobileNav"
                                >
                                    <House class="size-4 shrink-0" />
                                    Home
                                </Link>
                            </Button>

                            <Button
                                v-for="item in navItems"
                                :key="`mobile-${toUrl(item.href)}`"
                                variant="ghost"
                                :class="[
                                    'h-10 justify-start',
                                    {
                                        'bg-primary/10 text-primary dark:bg-primary/15':
                                            isCurrentOrParentUrl(item.href),
                                    },
                                ]"
                                as-child
                            >
                                <Link
                                    :href="item.href"
                                    class="inline-flex items-center gap-2"
                                    @click="closeMobileNav"
                                >
                                    <component
                                        :is="item.icon"
                                        class="size-4 shrink-0"
                                    />
                                    {{ item.title }}
                                </Link>
                            </Button>

                            <Button
                                variant="ghost"
                                class="h-10 justify-start"
                                as-child
                            >
                                <Link
                                    :href="logout()"
                                    as="button"
                                    class="inline-flex items-center gap-2"
                                    data-test="logout-button-mobile"
                                    @click="
                                        () => {
                                            handleLogout();
                                            closeMobileNav();
                                        }
                                    "
                                >
                                    <LogOut class="size-4 shrink-0" />
                                    Log out
                                </Link>
                            </Button>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>
        </header>

        <aside
            class="hidden w-64 shrink-0 flex-col border-r border-border/60 bg-background md:sticky md:top-0 md:flex md:h-svh"
        >
            <div class="flex flex-col gap-6 p-6">
                <ViRentWordmark class="text-2xl sm:text-3xl" />

                <nav class="flex flex-col gap-1">
                    <Button
                        variant="ghost"
                        class="h-10 justify-start"
                        as-child
                    >
                        <Link
                            :href="home()"
                            class="inline-flex w-full items-center gap-2"
                        >
                            <House class="size-4 shrink-0" />
                            Home
                        </Link>
                    </Button>

                    <Button
                        v-for="item in navItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'h-10 justify-start',
                            {
                                'bg-primary/10 text-primary dark:bg-primary/15':
                                    isCurrentOrParentUrl(item.href),
                            },
                        ]"
                        as-child
                    >
                        <Link
                            :href="item.href"
                            class="inline-flex w-full items-center gap-2"
                        >
                            <component
                                :is="item.icon"
                                class="size-4 shrink-0"
                            />
                            {{ item.title }}
                        </Link>
                    </Button>

                    <Button
                        variant="ghost"
                        class="h-10 justify-start"
                        as-child
                    >
                        <Link
                            :href="logout()"
                            as="button"
                            class="inline-flex w-full items-center gap-2"
                            data-test="logout-button"
                            @click="handleLogout"
                        >
                            <LogOut class="size-4 shrink-0" />
                            Log out
                        </Link>
                    </Button>
                </nav>
            </div>
        </aside>

        <main class="mx-auto w-full max-w-5xl flex-1 px-6 py-8">
            <slot />
        </main>

        <Toaster />
    </div>
</template>
