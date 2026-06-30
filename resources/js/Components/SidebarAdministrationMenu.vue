<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const open = ref(true);

const menuItems = [
    {
        label: 'Buscar usuario',
        routeName: 'administracion.usuarios.buscar',
        path: '/administracion/usuarios/buscar',
        icon: 'M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z',
    },
    {
        label: 'Bitácora',
        routeName: 'administracion.bitacora.index',
        path: '/administracion/bitacora',
        icon: 'M12 8v5l3 2m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        label: 'Administradores',
        routeName: 'administracion.administradores.index',
        path: '/administracion/administradores',
        icon: 'M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4z',
    },
    {
        label: 'Empleados',
        routeName: 'administracion.empleados.index',
        path: '/administracion/empleados',
        icon: 'M16 11a4 4 0 10-8 0m8 0a4 4 0 01-8 0m8 0c2.21 0 4 1.79 4 4v2H4v-2c0-2.21 1.79-4 4-4',
    },
    {
        label: 'Clientes',
        routeName: 'administracion.clientes.index',
        path: '/administracion/clientes',
        icon: 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z',
    },
];

const sectionActive = computed(() => {
    return page.url.startsWith('/administracion');
});

const isActive = (path) => {
    return page.url === path || page.url.startsWith(`${path}/`);
};

onMounted(() => {
    const saved = localStorage.getItem('sidebar.administracion.open');

    if (saved !== null) {
        open.value = saved === 'true';
    }

    if (sectionActive.value) {
        open.value = true;
    }
});

watch(open, (value) => {
    localStorage.setItem('sidebar.administracion.open', value ? 'true' : 'false');
});
</script>

<template>
    <div class="mt-3">
        <button
            type="button"
            class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-black transition"
            :class="sectionActive
                ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                : 'text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            @click="open = !open"
        >
            <span class="flex items-center gap-3">
                <svg
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M4 6a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM13 6a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V6zM4 15a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3zM13 15a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z"
                    />
                </svg>

                Administración
            </span>

            <svg
                class="h-4 w-4 transition"
                :class="open ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <div v-if="open" class="mt-2 space-y-1 pl-2">
            <Link
                v-for="item in menuItems"
                :key="item.path"
                :href="route(item.routeName)"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-bold transition"
                :class="isActive(item.path)
                    ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                    : 'text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            >
                <svg
                    class="h-5 w-5 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        :d="item.icon"
                    />
                </svg>

                <span class="truncate">
                    {{ item.label }}
                </span>
            </Link>
        </div>
    </div>
</template>
