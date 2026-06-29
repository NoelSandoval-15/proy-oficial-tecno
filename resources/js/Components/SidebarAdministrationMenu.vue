<script setup>
import { onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const open = ref(true);

const isActive = (path) => {
    return page.url === path || page.url.startsWith(`${path}/`);
};

onMounted(() => {
    const saved = localStorage.getItem('sidebar.administracion.open');

    if (saved !== null) {
        open.value = saved === 'true';
    }

    if (page.url.startsWith('/administracion')) {
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
            class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
            @click="open = !open"
        >
            <span class="flex items-center gap-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M4 6h16M4 12h16M4 18h10"
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div v-if="open" class="mt-2 space-y-1 pl-3">
            <Link
                :href="route('administracion.empleados.index')"
                class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-bold transition"
                :class="isActive('/administracion/empleados')
                    ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                    : 'border-transparent text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            >
                Empleados
            </Link>

            <Link
                :href="route('administracion.clientes.index')"
                class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-bold transition"
                :class="isActive('/administracion/clientes')
                    ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                    : 'border-transparent text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            >
                Clientes
            </Link>

            <Link
                :href="route('administracion.administradores.index')"
                class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-bold transition"
                :class="isActive('/administracion/administradores')
                    ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                    : 'border-transparent text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            >
                Administradores
            </Link>

            <Link
                :href="route('administracion.usuarios.buscar')"
                class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-bold transition"
                :class="isActive('/administracion/usuarios/buscar')
                    ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                    : 'border-transparent text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            >
                Buscar usuario
            </Link>
        </div>
    </div>
</template>
