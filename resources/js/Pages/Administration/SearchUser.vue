<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    selectedUser: {
        type: Object,
        default: null,
    },
    stats: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
});

const search = ref(props.filters.search ?? '');

const displayName = (user) => {
    const profileName = user?.profile?.name;
    const profileLastName = user?.profile?.last_name;

    if (profileName || profileLastName) {
        return `${profileName ?? ''} ${profileLastName ?? ''}`.trim();
    }

    return user?.name ?? 'Sin nombre';
};

const initials = (user) => {
    return displayName(user).charAt(0).toUpperCase();
};

const selectedDisplayName = computed(() => {
    return props.selectedUser ? displayName(props.selectedUser) : '';
});

const selectedInitial = computed(() => {
    return props.selectedUser ? initials(props.selectedUser) : 'U';
});

const runSearch = () => {
    router.get(
        route('administracion.usuarios.buscar'),
        {
            search: search.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const clearSearch = () => {
    search.value = '';

    router.get(
        route('administracion.usuarios.buscar'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const selectUser = (userId) => {
    router.get(
        route('administracion.usuarios.buscar'),
        {
            search: search.value,
            user: userId,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};
</script>

<template>
    <Head title="Buscar usuario" />

    <SidebarLayout
        title="Buscar usuario"
        subtitle="Consulta empleados, clientes y administradores con estadísticas importantes."
    >
        <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
            <form class="grid gap-4 lg:grid-cols-[1fr_auto_auto]" @submit.prevent="runSearch">
                <div class="relative">
                    <svg
                        class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                        />
                    </svg>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre, apellido, correo, CI, teléfono o una acción del sistema..."
                        class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] py-3 pl-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                    />
                </div>

                <button
                    type="submit"
                    class="rounded-2xl bg-[var(--app-primary)] px-6 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-6 py-3 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                    @click="clearSearch"
                >
                    Limpiar
                </button>
            </form>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[420px_1fr]">
            <div class="overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div class="border-b border-[var(--app-border)] px-6 py-5">
                    <h2 class="text-xl font-black text-[var(--app-text)]">
                        Resultados
                    </h2>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Selecciona un usuario para ver su análisis.
                    </p>
                </div>

                <div class="max-h-[720px] overflow-y-auto divide-y divide-[var(--app-border)]">
                    <button
                        v-for="user in users"
                        :key="user.id"
                        type="button"
                        class="group flex w-full items-center gap-4 px-6 py-4 text-left transition hover:bg-[var(--app-surface-soft)]"
                        :class="selectedUser?.id === user.id ? 'bg-[var(--app-primary-soft)]' : ''"
                        @click="selectUser(user.id)"
                    >
                        <img
                            v-if="user.profile?.url_photo"
                            :src="user.profile.url_photo"
                            :alt="displayName(user)"
                            class="h-12 w-12 shrink-0 rounded-2xl object-cover shadow-sm"
                        />

                        <div
                            v-else
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-base font-black text-white shadow-sm"
                        >
                            {{ initials(user) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-black text-[var(--app-text)]">
                                {{ displayName(user) }}
                            </p>

                            <p class="truncate text-sm font-semibold text-[var(--app-muted)]">
                                {{ user.email }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="rounded-full bg-[var(--app-primary-soft)] px-3 py-1 text-[11px] font-black text-[var(--app-primary-text)]">
                                    {{ user.roles?.[0] ?? 'Sin rol' }}
                                </span>

                                <span class="rounded-full bg-[var(--app-surface-soft)] px-3 py-1 text-[11px] font-black text-[var(--app-text)]">
                                    CI: {{ user.profile?.ci ?? 'Sin CI' }}
                                </span>
                            </div>
                        </div>

                        <svg
                            class="h-5 w-5 text-[var(--app-muted)] transition group-hover:translate-x-1 group-hover:text-[var(--app-primary)]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>

                    <div v-if="users.length === 0" class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--app-primary-soft)] text-[var(--app-primary)]">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                                />
                            </svg>
                        </div>

                        <p class="mt-4 font-black text-[var(--app-text)]">
                            No hay resultados
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Intenta con otro nombre, correo, CI o teléfono.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <section
                    v-if="selectedUser"
                    class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
                >
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[var(--app-primary)]/20 blur-3xl"></div>

                    <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-4">
                            <img
                                v-if="selectedUser.profile?.url_photo"
                                :src="selectedUser.profile.url_photo"
                                :alt="selectedDisplayName"
                                class="h-20 w-20 rounded-[1.5rem] object-cover shadow-sm"
                            />

                            <div
                                v-else
                                class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-[var(--app-primary)] text-3xl font-black text-white shadow-sm"
                            >
                                {{ selectedInitial }}
                            </div>

                            <div>
                                <h2 class="text-2xl font-black text-[var(--app-text)]">
                                    {{ selectedDisplayName }}
                                </h2>

                                <p class="text-sm font-semibold text-[var(--app-muted)]">
                                    {{ selectedUser.email }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="rounded-xl bg-[var(--app-primary-soft)] px-3 py-1 text-xs font-black text-[var(--app-primary-text)]">
                                        {{ selectedUser.roles?.[0] ?? 'Sin rol' }}
                                    </span>

                                    <span class="rounded-xl bg-[var(--app-surface-soft)] px-3 py-1 text-xs font-black text-[var(--app-text)]">
                                        {{ selectedUser.theme ?? 'Sin tema' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-[var(--app-surface-soft)] px-5 py-4">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                                Perfil
                            </p>

                            <p class="mt-1 text-sm font-bold text-[var(--app-text)]">
                                CI: {{ selectedUser.profile?.ci ?? 'Sin CI' }}
                            </p>

                            <p class="text-sm font-bold text-[var(--app-text)]">
                                Tel: {{ selectedUser.profile?.telephone ?? 'Sin teléfono' }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="stats"
                    class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
                >
                    <h3 class="text-xl font-black text-[var(--app-text)]">
                        {{ stats.title }}
                    </h3>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div
                            v-for="card in stats.cards"
                            :key="card.label"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5"
                        >
                            <p class="text-sm font-black text-[var(--app-muted)]">
                                {{ card.label }}
                            </p>

                            <p class="mt-3 text-2xl font-black text-[var(--app-text)]">
                                {{ card.value }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="stats"
                    class="overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm"
                >
                    <div class="border-b border-[var(--app-border)] px-6 py-5">
                        <h3 class="text-xl font-black text-[var(--app-text)]">
                            Últimos movimientos
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left">
                            <thead>
                                <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Fecha</th>
                                    <th class="px-6 py-4">Hora</th>
                                    <th class="px-6 py-4">Descripción</th>
                                    <th class="px-6 py-4">Total</th>
                                    <th class="px-6 py-4">Estado</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-[var(--app-border)]">
                                <tr
                                    v-for="item in stats.recent"
                                    :key="item.id"
                                    class="transition hover:bg-[var(--app-surface-soft)]"
                                >
                                    <td class="px-6 py-5 font-black text-[var(--app-text)]">
                                        #{{ item.id }}
                                    </td>

                                    <td class="px-6 py-5 text-sm font-semibold text-[var(--app-muted)]">
                                        {{ item.date }}
                                    </td>

                                    <td class="px-6 py-5 text-sm font-semibold text-[var(--app-muted)]">
                                        {{ item.hour }}
                                    </td>

                                    <td class="px-6 py-5 text-sm font-semibold text-[var(--app-muted)]">
                                        {{ item.description }}
                                    </td>

                                    <td class="px-6 py-5 font-black text-[var(--app-text)]">
                                        {{ item.total }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="rounded-xl bg-[var(--app-primary-soft)] px-3 py-1 text-xs font-black text-[var(--app-primary-text)]">
                                            {{ item.status ?? 'Registrado' }}
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="stats.recent.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-6 py-10 text-center text-sm font-bold text-[var(--app-muted)]"
                                    >
                                        No hay movimientos registrados todavía.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section
                    v-if="!selectedUser"
                    class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-12 text-center shadow-sm"
                >
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[2rem] bg-[var(--app-primary-soft)] text-[var(--app-primary)]">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0"
                            />
                        </svg>
                    </div>

                    <p class="mt-5 text-2xl font-black text-[var(--app-text)]">
                        Selecciona un usuario
                    </p>

                    <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                        Aquí aparecerán sus estadísticas, ventas, reservas o resumen del negocio.
                    </p>
                </section>
            </div>
        </section>
    </SidebarLayout>
</template>
