<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { CalendarCheck, LogIn, LogOut, Menu, User, UserPlus } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import RentalProfileModal from '@/components/RentalProfileModal.vue';
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
import { login, logout, register } from '@/routes';
import { index as bookingsIndex } from '@/routes/bookings';
import { edit as profileEdit } from '@/routes/profile';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const showRentalProfileModal = ref(false);
const isMobileNavOpen = ref(false);
const { isCurrentOrParentUrl } = useCurrentUrl();

const bookingsNavItem: NavItem = {
    title: 'Bookings',
    href: bookingsIndex(),
    icon: CalendarCheck,
};

const authenticatedNavItems: NavItem[] = [
    {
        title: 'Account',
        href: profileEdit(),
        icon: User,
    },
];

const guestNavItems: NavItem[] = [
    {
        title: 'Log in',
        href: login(),
        icon: LogIn,
    },
    {
        title: 'Sign up',
        href: register(),
        icon: UserPlus,
    },
];

function handleFlash(event: Event): void {
    const flash = (event as CustomEvent).detail?.flash;

    if (flash?.showRentalProfileModal) {
        showRentalProfileModal.value = true;
    }
}

function handleLogout(): void {
    router.flushAll();
}

function closeMobileNav(): void {
    isMobileNavOpen.value = false;
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
    <div class="min-h-svh bg-background">
        <header class="border-b border-border/60">
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6"
            >
                <ViRentWordmark class="text-2xl sm:text-3xl" />

                <nav class="hidden items-center gap-1 md:flex">
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="[
                            'h-9',
                            {
                                'bg-primary/10 text-primary dark:bg-primary/15':
                                    isCurrentOrParentUrl(bookingsNavItem.href),
                            },
                        ]"
                        as-child
                    >
                        <Link
                            :href="bookingsNavItem.href"
                            class="inline-flex items-center gap-2"
                        >
                            <component
                                :is="bookingsNavItem.icon"
                                class="size-4 shrink-0"
                            />
                            {{ bookingsNavItem.title }}
                        </Link>
                    </Button>
                    <template v-if="user">
                        <Button
                            v-for="item in authenticatedNavItems"
                            :key="toUrl(item.href)"
                            variant="ghost"
                            size="sm"
                            :class="[
                                'h-9',
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
                                @click="handleLogout"
                            >
                                <LogOut class="size-4 shrink-0" />
                                Log out
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            v-for="item in guestNavItems"
                            :key="toUrl(item.href)"
                            variant="ghost"
                            size="sm"
                            :class="[
                                'h-9',
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
                            >
                                <component
                                    :is="item.icon"
                                    class="size-4 shrink-0"
                                />
                                {{ item.title }}
                            </Link>
                        </Button>
                    </template>
                </nav>

                <Sheet v-model:open="isMobileNavOpen">
                    <SheetTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="md:hidden"
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
                                :class="[
                                    'h-10 justify-start',
                                    {
                                        'bg-primary/10 text-primary dark:bg-primary/15':
                                            isCurrentOrParentUrl(
                                                bookingsNavItem.href,
                                            ),
                                    },
                                ]"
                                as-child
                            >
                                <Link
                                    :href="bookingsNavItem.href"
                                    class="inline-flex items-center gap-2"
                                    @click="closeMobileNav"
                                >
                                    <component
                                        :is="bookingsNavItem.icon"
                                        class="size-4 shrink-0"
                                    />
                                    {{ bookingsNavItem.title }}
                                </Link>
                            </Button>
                            <template v-if="user">
                                <Button
                                    v-for="item in authenticatedNavItems"
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
                            </template>
                            <template v-else>
                                <Button
                                    v-for="item in guestNavItems"
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
                            </template>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>
        </header>

        <slot />

        <Toaster />

        <RentalProfileModal v-if="user" v-model:open="showRentalProfileModal" />
    </div>
</template>
