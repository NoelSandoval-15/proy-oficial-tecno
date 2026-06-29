<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ThemeProvider from '@/Components/ThemeProvider.vue';
import ThemeSelector from '@/Components/ThemeSelector.vue';
import SidebarAdministrationMenu from '@/Components/SidebarAdministrationMenu.vue';
import RouteLoadingIndicator from '@/Components/RouteLoadingIndicator.vue';

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

const user = computed(() => page.props.auth.user);

const currentThemeName = computed(() => {
    return user.value?.theme?.name ?? 'Administrador';
});

const isActive = (path) => {
    return page.url === path || page.url.startsWith(`${path}/`);
};

const reloadView = () => {
    router.reload({
        preserveScroll: true,
    });
};
</script>

<template>
    <ThemeProvider :theme-name="currentThemeName" v-slot="{ theme }">
        <RouteLoadingIndicator />

        <aside
            class="sidebar-scroll fixed left-0 top-0 z-40 hidden h-screen w-[290px] flex-col overflow-y-auto border-r border-[var(--app-border)] bg-[var(--app-sidebar)] px-4 py-6 lg:flex"
        >
            <div class="px-2">
                <h1
                    class="font-black leading-tight tracking-tight"
                    :class="theme.mode === 'kids' ? 'text-4xl' : 'text-2xl uppercase'"
                    style="color: var(--app-primary)"
                >
                    {{ theme.logo }}
                </h1>

                <p class="mt-2 text-sm font-bold text-[var(--app-muted)]">
                    {{ theme.subtitle }}
                </p>
            </div>

            <nav class="mt-8 flex flex-col gap-2">
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-black transition"
                    :class="isActive('/dashboard')
                        ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                        : 'border-transparent text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM13 5a1 1 0 011-1h5a1 1 0 011 1v3a1 1 0 01-1 1h-5a1 1 0 01-1-1V5zM13 14a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1h-5a1 1 0 01-1-1v-5zM4 16a1 1 0 011-1h5a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3z"
                        />
                    </svg>

                    Dashboard
                </Link>

                <SidebarAdministrationMenu />

                <a
                    href="#"
                    class="flex items-center gap-3 rounded-2xl border border-transparent px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                    Inventario
                </a>

                <a
                    href="#"
                    class="flex items-center gap-3 rounded-2xl border border-transparent px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 7h16M6 11h12M7 15h6M6 19h12M6 5h12a2 2 0 012 2v12H4V7a2 2 0 012-2z"
                        />
                    </svg>
                    Ventas
                </a>

                <a
                    href="#"
                    class="flex items-center gap-3 rounded-2xl border border-transparent px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M7 3v8M5 3v8M9 3v8M5 11h4M7 11v10M16 3c1.8 1.6 3 4.1 3 7 0 2.8-1.1 5.3-3 7V3zM16 17v4"
                        />
                    </svg>
                    Mesas
                </a>

                <a
                    href="#"
                    class="flex items-center gap-3 rounded-2xl border border-transparent px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M8 7h8M8 11h8M8 15h5M5 4h14v16H5z"
                        />
                    </svg>
                    Reservas
                </a>
            </nav>

            <div class="mt-8">
                <ThemeSelector />
            </div>

            <div class="mt-6 border-t border-[var(--app-border)] pt-4">
                <a
                    href="#"
                    class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0"
                        />
                    </svg>
                    Mi perfil
                </a>

                <a
                    href="#"
                    class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M10.5 6h3m-6 4.5h9m-10 4.5h11M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z"
                        />
                    </svg>
                    Configuración
                </a>

                <a
                    href="#"
                    class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M8 10a4 4 0 118 0c0 3-4 3-4 6M12 20h.01"
                        />
                    </svg>
                    Soporte
                </a>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M15 12H3m12 0l-4-4m4 4l-4 4M21 4v16"
                        />
                    </svg>
                    Cerrar sesión
                </Link>

                <div class="mt-4 flex items-center gap-3 rounded-2xl bg-[var(--app-surface-soft)] px-3 py-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--app-primary)] text-sm font-black text-white">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-[var(--app-text)]">
                            {{ user.name }}
                        </p>
                        <p class="truncate text-xs text-[var(--app-muted)]">
                            {{ user.email }}
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="lg:pl-[290px]">
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-[var(--app-border)] bg-[var(--app-sidebar)] px-4 sm:px-6 lg:px-8">
                <div class="hidden w-full max-w-md items-center gap-3 rounded-full border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-4 py-2 text-[var(--app-muted)] md:flex">
                    <svg class="h-5 w-5 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                        />
                    </svg>
                    <span class="text-sm">Buscar órdenes, mesas, clientes...</span>
                </div>

                <div class="lg:hidden">
                    <p class="text-xl font-black text-[var(--app-primary)]">
                        {{ theme.logo }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="hidden rounded-full bg-[var(--app-surface-soft)] px-4 py-2 text-sm font-black text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)] md:inline-flex"
                        @click="reloadView"
                    >
                        Recargar vista
                    </button>

                    <div class="hidden items-center gap-2 rounded-full bg-[var(--app-surface-soft)] px-4 py-2 text-sm font-black text-[var(--app-text)] sm:flex">
                        <svg class="h-4 w-4 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 15a3 3 0 100-6 3 3 0 000 6z"
                            />
                        </svg>
                        {{ page.props.routeVisits?.current ?? 0 }} visitas
                    </div>

                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-black text-[var(--app-text)]">
                            {{ user.name }}
                        </p>
                        <p class="text-xs text-[var(--app-muted)]">
                            {{ theme.displayName }}
                        </p>
                    </div>
                </div>
            </header>

            <main class="px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-3xl font-black tracking-tight text-[var(--app-text)]">
                            {{ title }}
                        </h2>
                        <p class="mt-1 text-base text-[var(--app-muted)]">
                            {{ subtitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-2.5 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                    >
                        Exportar reporte
                    </button>
                </div>

                <slot :theme="theme" />
            </main>
        </div>
    </ThemeProvider>
</template>
