<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ThemeProvider from '@/Components/ThemeProvider.vue';
import ThemeSelector from '@/Components/ThemeSelector.vue';
import SidebarAdministrationMenu from '@/Components/SidebarAdministrationMenu.vue';
import RouteLoadingIndicator from '@/Components/RouteLoadingIndicator.vue';
import QuickCommandSearch from '@/Components/QuickCommandSearch.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    subtitle: {
        type: String,
        default: 'Panel general',
    },
});

const page = usePage();

const userMenuOpen = ref(false);

const user = computed(() => page.props.auth.user);

const currentThemeName = computed(() => {
    return user.value?.theme?.name ?? 'Administrador';
});

const userInitial = computed(() => {
    return user.value?.name?.charAt(0)?.toUpperCase() ?? 'U';
});

const isActive = (path) => {
    return page.url === path || page.url.startsWith(`${path}/`);
};

const reloadView = () => {
    router.reload({
        preserveScroll: true,
    });
};

const closeUserMenu = () => {
    userMenuOpen.value = false;
};
</script>

<template>
    <ThemeProvider :theme-name="currentThemeName" v-slot="{ theme }">
        <RouteLoadingIndicator />

        <aside
            class="sidebar-scroll fixed left-0 top-0 z-40 hidden h-screen w-[290px] flex-col overflow-y-auto border-r border-[var(--app-border)] bg-[var(--app-sidebar)] px-4 py-6 lg:flex">
            <div class="px-2">
                <h1 class="font-black leading-tight tracking-tight"
                    :class="theme.mode === 'kids' ? 'text-4xl' : 'text-2xl uppercase'"
                    style="color: var(--app-primary)">
                    {{ theme.logo }}
                </h1>

                <p class="mt-2 text-sm font-bold text-[var(--app-muted)]">
                    {{ theme.subtitle }}
                </p>
            </div>

            <nav class="mt-8 flex flex-col gap-2">
                <Link :href="route('dashboard')"
                    class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-black transition"
                    :class="isActive('/dashboard')
                        ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                        : 'border-transparent text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M4 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM13 5a1 1 0 011-1h5a1 1 0 011 1v3a1 1 0 01-1 1h-5a1 1 0 01-1-1V5zM13 14a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1h-5a1 1 0 01-1-1v-5zM4 16a1 1 0 011-1h5a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3z" />
                    </svg>

                    Dashboard
                </Link>

                <SidebarAdministrationMenu />
            </nav>
        </aside>

        <div class="flex min-h-screen flex-col lg:pl-[290px]">
            <header
                class="sticky top-0 z-30 border-b border-[var(--app-border)] bg-[var(--app-sidebar)]/95 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between gap-4">

                    <QuickCommandSearch />
                    
                    <div class="lg:hidden">
                        <p class="text-xl font-black text-[var(--app-primary)]">
                            {{ theme.logo }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button"
                            class="group hidden items-center gap-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-4 py-3 text-sm font-black text-[var(--app-text)] shadow-sm transition hover:border-[var(--app-primary)] hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)] md:inline-flex"
                            @click="reloadView">
                            <svg class="h-5 w-5 text-[var(--app-primary)] transition group-hover:rotate-180" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M4 4v6h6M20 20v-6h-6M5 19A8 8 0 0119 5l1 1M19 5A8 8 0 015 19l-1-1" />
                            </svg>

                            Recargar vista
                        </button>

                        <ThemeSelector variant="compact" />

                        <div class="relative">
                            <button type="button"
                                class="group flex items-center gap-3 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-3 py-2 shadow-sm transition hover:border-[var(--app-primary)] hover:bg-[var(--app-primary-soft)]"
                                @click="userMenuOpen = !userMenuOpen">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-sm font-black text-white shadow-sm transition group-hover:scale-105">
                                    {{ userInitial }}
                                </div>

                                <div class="hidden min-w-0 text-left sm:block">
                                    <p class="max-w-[150px] truncate text-sm font-black text-[var(--app-text)]">
                                        {{ user.name }}
                                    </p>

                                    <p class="max-w-[150px] truncate text-xs font-semibold text-[var(--app-muted)]">
                                        {{ user.email }}
                                    </p>
                                </div>

                                <svg class="h-4 w-4 text-[var(--app-muted)] transition"
                                    :class="userMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-if="userMenuOpen" class="fixed inset-0 z-40" @click="closeUserMenu"></div>

                            <div v-if="userMenuOpen"
                                class="absolute right-0 top-16 z-50 w-80 overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                                <div
                                    class="relative overflow-hidden border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5">
                                    <div
                                        class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[var(--app-primary)]/20 blur-2xl">
                                    </div>

                                    <div class="relative flex items-center gap-4">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-lg font-black text-white shadow-sm">
                                            {{ userInitial }}
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-base font-black text-[var(--app-text)]">
                                                {{ user.name }}
                                            </p>

                                            <p class="truncate text-sm font-semibold text-[var(--app-muted)]">
                                                {{ user.email }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs font-black uppercase tracking-[0.14em] text-[var(--app-primary)]">
                                                {{ theme.displayName }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3">
                                    <Link :href="route('profile.edit')"
                                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-black text-[var(--app-text)] transition hover:bg-[var(--app-surface-soft)]"
                                        @click="closeUserMenu">
                                        <svg class="h-5 w-5 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0" />
                                        </svg>

                                        Mi perfil
                                    </Link>

                                    <Link :href="route('logout')" method="post" as="button"
                                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-black text-red-500 transition hover:bg-red-500/10"
                                        @click="closeUserMenu">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M15 12H3m12 0l-4-4m4 4l-4 4M21 4v16" />
                                        </svg>

                                        Cerrar sesión
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h2 class="text-3xl font-black tracking-tight text-[var(--app-text)]">
                        {{ title }}
                    </h2>

                    <p class="mt-1 text-base text-[var(--app-muted)]">
                        {{ subtitle }}
                    </p>
                </div>

                <slot :theme="theme" />
            </main>

            <footer class="border-t border-[var(--app-border)] bg-[var(--app-sidebar)] px-4 py-5 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-black text-[var(--app-text)]">
                            Churrasquería Roberto
                        </p>

                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                            © 2026 Todos los derechos reservados.
                        </p>
                    </div>

                    <div
                        class="flex items-center gap-2 rounded-2xl bg-[var(--app-surface-soft)] px-4 py-2 font-black text-[var(--app-text)]">
                        <svg class="h-4 w-4 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                        </svg>

                        {{ page.props.routeVisits?.current ?? 0 }} visitas de esta vista
                    </div>
                </div>
            </footer>
        </div>
    </ThemeProvider>
</template>
