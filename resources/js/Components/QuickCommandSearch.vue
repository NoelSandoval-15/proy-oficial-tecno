<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { systemActions } from '@/Config/systemActions';

const page = usePage();

const search = ref('');
const open = ref(false);

const currentRoles = computed(() => {
    return page.props.auth.user?.roles ?? [];
});

const canSee = (action) => {
    return action.roles.some((role) => currentRoles.value.includes(role));
};

const buildHref = (action) => {
    const base = route(action.routeName);

    if (!action.query) {
        return base;
    }

    const params = new URLSearchParams();

    Object.entries(action.query).forEach(([key, value]) => {
        params.append(key, value);
    });

    return `${base}?${params.toString()}`;
};

const filteredActions = computed(() => {
    const text = search.value.toLowerCase().trim();

    return systemActions
        .filter(canSee)
        .filter((action) => {
            if (!text) {
                return true;
            }

            return [
                action.title,
                action.description,
                action.category,
                ...(action.keywords ?? []),
            ].join(' ').toLowerCase().includes(text);
        })
        .slice(0, 8);
});

const groupedActions = computed(() => {
    return filteredActions.value.reduce((groups, action) => {
        if (!groups[action.category]) {
            groups[action.category] = [];
        }

        groups[action.category].push(action);

        return groups;
    }, {});
});

const close = () => {
    open.value = false;
};

const openSearch = () => {
    open.value = true;
};
</script>

<template>
    <div class="relative hidden w-full max-w-md md:block">
        <div
            class="group flex items-center gap-3 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-4 py-3 shadow-sm transition hover:border-[var(--app-primary)]"
            @click="openSearch"
        >
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--app-card)] text-[var(--app-primary)]">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                    />
                </svg>
            </div>

            <input
                v-model="search"
                type="text"
                placeholder="Buscar acciones, módulos, usuarios..."
                class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-[var(--app-text)] placeholder:text-[var(--app-muted)] focus:ring-0"
                @focus="openSearch"
            />
        </div>

        <div
            v-if="open"
            class="fixed inset-0 z-40"
            @click="close"
        ></div>

        <div
            v-if="open"
            class="absolute left-0 top-16 z-50 w-[520px] overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl"
        >
            <div class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] px-5 py-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                    Búsqueda rápida
                </p>

                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                    Accesos disponibles según tu rol.
                </p>
            </div>

            <div v-if="filteredActions.length > 0" class="max-h-[520px] overflow-y-auto p-3">
                <div
                    v-for="(actions, category) in groupedActions"
                    :key="category"
                    class="mb-4 last:mb-0"
                >
                    <p class="mb-2 px-2 text-[10px] font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                        {{ category }}
                    </p>

                    <Link
                        v-for="action in actions"
                        :key="action.key"
                        :href="buildHref(action)"
                        class="group flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-[var(--app-primary-soft)]"
                        @click="close"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[var(--app-surface-soft)] text-[var(--app-primary)] transition group-hover:scale-105">
                            <svg
                                v-if="action.icon === 'dashboard'"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h7v7H4V5zm9 0h7v5h-7V5zM4 14h7v5H4v-5zm9-2h7v7h-7v-7z" />
                            </svg>

                            <svg
                                v-else-if="action.icon === 'users'"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 118 0 4 4 0 01-8 0z" />
                            </svg>

                            <svg
                                v-else-if="action.icon === 'clients'"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11a4 4 0 100-8 4 4 0 000 8zM8 13a4 4 0 100-8 4 4 0 000 8zM2 21a6 6 0 0112 0M12 21a6 6 0 0110 0" />
                            </svg>

                            <svg
                                v-else-if="action.icon === 'shield'"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z" />
                            </svg>

                            <svg
                                v-else-if="action.icon === 'plus'"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                            </svg>

                            <svg
                                v-else-if="action.icon === 'document'"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 4h8l4 4v12H4V4h4zM15 4v5h5M8 13h8M8 17h5" />
                            </svg>

                            <svg
                                v-else
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="font-black text-[var(--app-text)]">
                                {{ action.title }}
                            </p>

                            <p class="truncate text-sm font-semibold text-[var(--app-muted)]">
                                {{ action.description }}
                            </p>
                        </div>

                        <svg class="h-5 w-5 text-[var(--app-muted)] transition group-hover:translate-x-1 group-hover:text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>

            <div v-else class="p-8 text-center">
                <p class="font-black text-[var(--app-text)]">
                    Sin resultados
                </p>

                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                    No hay acciones disponibles para esa búsqueda.
                </p>
            </div>
        </div>
    </div>
</template>
