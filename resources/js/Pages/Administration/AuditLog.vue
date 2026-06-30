<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    logs: {
        type: Object,
        default: () => ({
            data: [],
            meta: {},
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    actions: {
        type: Array,
        default: () => [],
    },
    modules: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const logsState = ref(props.logs);
const statsState = ref(props.stats);

const search = ref(props.filters.search ?? '');
const action = ref(props.filters.action ?? '');
const module = ref(props.filters.module ?? '');
const userId = ref(props.filters.user_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const period = ref(props.filters.period ?? 'all');
const perPage = ref(String(props.filters.per_page ?? 15));

const tableLoading = ref(false);
const lastUpdated = ref(null);
const currentPage = ref(Number(props.logs?.meta?.current_page ?? 1));

let timeout = null;
let interval = null;
let controller = null;

const rows = computed(() => logsState.value?.data ?? []);
const meta = computed(() => logsState.value?.meta ?? {});

const periodOptions = [
    { value: 'all', label: 'Todo' },
    { value: 'today', label: 'Hoy' },
    { value: 'yesterday', label: 'Ayer' },
    { value: 'month', label: 'Este mes' },
];

const hasFilters = computed(() => {
    return Boolean(
        search.value ||
        action.value ||
        module.value ||
        userId.value ||
        dateFrom.value ||
        dateTo.value ||
        period.value !== 'all'
    );
});

const buildParams = (page = currentPage.value) => {
    const params = new URLSearchParams();

    params.set('search', search.value ?? '');
    params.set('action', action.value ?? '');
    params.set('module', module.value ?? '');
    params.set('user_id', userId.value ?? '');
    params.set('date_from', dateFrom.value ?? '');
    params.set('date_to', dateTo.value ?? '');
    params.set('period', period.value ?? 'all');
    params.set('per_page', perPage.value ?? '15');
    params.set('page', String(page));

    return params;
};

const syncBrowserUrl = (page = currentPage.value) => {
    const url = `${route('administracion.bitacora.index')}?${buildParams(page).toString()}`;
    window.history.replaceState({}, '', url);
};

const loadData = async (page = 1, silent = false) => {
    const targetPage = Number(page || 1);

    currentPage.value = targetPage;

    if (controller) {
        controller.abort();
    }

    controller = new AbortController();

    if (!silent) {
        tableLoading.value = true;
    }

    try {
        const response = await fetch(
            `${route('administracion.bitacora.data')}?${buildParams(targetPage).toString()}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            }
        );

        if (!response.ok) {
            throw new Error('No se pudo cargar la bitácora.');
        }

        const data = await response.json();

        logsState.value = data.logs;
        statsState.value = data.stats;

        currentPage.value = Number(data.logs?.meta?.current_page ?? targetPage);

        lastUpdated.value = new Date().toLocaleTimeString('es-BO', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });

        syncBrowserUrl(currentPage.value);
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error(error);
        }
    } finally {
        if (!silent) {
            tableLoading.value = false;
        }
    }
};

watch([search, action, module, userId, dateFrom, dateTo, period, perPage], () => {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        loadData(1);
    }, 350);
});

onMounted(() => {
    interval = setInterval(() => {
        loadData(currentPage.value, true);
    }, 5000);
});

onBeforeUnmount(() => {
    clearTimeout(timeout);
    clearInterval(interval);

    if (controller) {
        controller.abort();
    }
});

const setPeriod = (value) => {
    period.value = value;

    if (value !== 'custom') {
        dateFrom.value = '';
        dateTo.value = '';
    }
};

const useCustomDates = () => {
    period.value = 'custom';
};

const clearFilters = () => {
    search.value = '';
    action.value = '';
    module.value = '';
    userId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    period.value = 'all';
    perPage.value = '15';

    loadData(1);
};

const refreshNow = () => {
    loadData(currentPage.value);
};

const exportUrl = (routeName) => {
    const params = new URLSearchParams({
        search: search.value ?? '',
        action: action.value ?? '',
        module: module.value ?? '',
        user_id: userId.value ?? '',
        date_from: dateFrom.value ?? '',
        date_to: dateTo.value ?? '',
        period: period.value ?? 'all',
    });

    return `${route(routeName)}?${params.toString()}`;
};

const goToPage = (page) => {
    const targetPage = Number(page);
    const lastPage = Number(meta.value?.last_page ?? 1);

    if (targetPage < 1 || targetPage > lastPage) {
        return;
    }

    loadData(targetPage);
};

const actionClass = (value) => {
    if (value === 'ELIMINAR') return 'bg-red-500/10 text-red-600 ring-red-500/20';
    if (value === 'CREAR') return 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20';
    if (value === 'EDITAR') return 'bg-blue-500/10 text-blue-600 ring-blue-500/20';
    if (value === 'EXPORTAR') return 'bg-purple-500/10 text-purple-600 ring-purple-500/20';
    if (value === 'BUSCAR') return 'bg-yellow-500/10 text-yellow-600 ring-yellow-500/20';
    if (value === 'CAMBIAR_TEMA') return 'bg-pink-500/10 text-pink-600 ring-pink-500/20';
    if (value === 'VISITAR') return 'bg-slate-500/10 text-slate-600 ring-slate-500/20';

    return 'bg-[var(--app-primary-soft)] text-[var(--app-primary-text)] ring-[var(--app-primary)]/20';
};

const roleClass = (value) => {
    if (value === 'Master') return 'bg-red-500/10 text-red-600 ring-red-500/20';
    if (value === 'Administrador') return 'bg-blue-500/10 text-blue-600 ring-blue-500/20';
    if (value === 'Mesero') return 'bg-amber-500/10 text-amber-600 ring-amber-500/20';
    if (value === 'Cliente') return 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20';

    return 'bg-slate-500/10 text-slate-600 ring-slate-500/20';
};

const methodLabel = (value) => {
    return String(value || '-').toUpperCase();
};

const statusLabel = (value) => {
    return value ?? '-';
};

const methodTextClass = (value) => {
    const method = String(value || '').toUpperCase();

    if (method === 'GET') return 'text-sky-600';
    if (method === 'POST') return 'text-emerald-600';
    if (method === 'PUT') return 'text-blue-600';
    if (method === 'PATCH') return 'text-indigo-600';
    if (method === 'DELETE') return 'text-red-600';
    if (method === 'OPTIONS') return 'text-violet-600';

    return 'text-slate-600';
};

const statusDotClass = (value) => {
    const status = Number(value);

    if (status >= 200 && status < 300) return 'bg-emerald-500';
    if (status >= 300 && status < 400) return 'bg-yellow-500';
    if (status >= 400 && status < 500) return 'bg-orange-500';
    if (status >= 500) return 'bg-red-500';

    return 'bg-slate-400';
};

const statusSoftClass = (value) => {
    const status = Number(value);

    if (status >= 200 && status < 300) {
        return 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20';
    }

    if (status >= 300 && status < 400) {
        return 'bg-yellow-500/10 text-yellow-700 ring-yellow-500/20';
    }

    if (status >= 400 && status < 500) {
        return 'bg-orange-500/10 text-orange-700 ring-orange-500/20';
    }

    if (status >= 500) {
        return 'bg-red-500/10 text-red-700 ring-red-500/20';
    }

    return 'bg-slate-500/10 text-slate-600 ring-slate-500/20';
};

const statusText = (value) => {
    const status = Number(value);

    if (status >= 200 && status < 300) return 'Correcto';
    if (status >= 300 && status < 400) return 'Redirección';
    if (status >= 400 && status < 500) return 'Error cliente';
    if (status >= 500) return 'Error servidor';

    return 'Sin estado';
};
</script>

<template>
    <Head title="Bitácora" />

    <SidebarLayout
        title="Bitácora"
        subtitle="Registro profesional de acciones realizadas dentro del sistema."
    >
        <section class="grid gap-4 xl:grid-cols-5">
            <article class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[var(--app-primary)]/10 blur-2xl"></div>

                <div class="relative">
                    <p class="text-sm font-black text-[var(--app-muted)]">Total eventos</p>
                    <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ statsState.total ?? 0 }}</p>
                </div>
            </article>

            <article class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-emerald-500/10 blur-2xl"></div>

                <div class="relative">
                    <p class="text-sm font-black text-[var(--app-muted)]">Hoy Bolivia</p>
                    <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ statsState.today ?? 0 }}</p>
                </div>
            </article>

            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <p class="text-sm font-black text-[var(--app-muted)]">Exportaciones</p>
                <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ statsState.exports ?? 0 }}</p>
            </article>

            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <p class="text-sm font-black text-[var(--app-muted)]">Ediciones</p>
                <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ statsState.edits ?? 0 }}</p>
            </article>

            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <p class="text-sm font-black text-[var(--app-muted)]">Eliminaciones</p>
                <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ statsState.deletes ?? 0 }}</p>
            </article>
        </section>

        <section class="mt-6 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                        Filtros de auditoría
                    </p>

                    <h3 class="mt-1 text-2xl font-black text-[var(--app-text)]">
                        Consulta de eventos
                    </h3>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Ordenado del evento más reciente al más antiguo.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-2 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                        @click="refreshNow"
                    >
                        Actualizar ahora
                    </button>

                    <a
                        :href="exportUrl('administracion.bitacora.export.csv')"
                        class="rounded-2xl bg-[var(--app-primary)] px-4 py-2 text-sm font-black text-white transition hover:opacity-90"
                    >
                        Excel
                    </a>

                    <a
                        :href="exportUrl('administracion.bitacora.export.pdf')"
                        class="rounded-2xl bg-[var(--app-primary)] px-4 py-2 text-sm font-black text-white transition hover:opacity-90"
                    >
                        PDF
                    </a>

                    <a
                        :href="exportUrl('administracion.bitacora.export.txt')"
                        class="rounded-2xl bg-[var(--app-primary)] px-4 py-2 text-sm font-black text-white transition hover:opacity-90"
                    >
                        TXT
                    </a>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <button
                    v-for="item in periodOptions"
                    :key="item.value"
                    type="button"
                    class="rounded-2xl px-4 py-2 text-sm font-black ring-1 transition"
                    :class="period === item.value
                        ? 'bg-[var(--app-primary)] text-white ring-[var(--app-primary)]'
                        : 'bg-[var(--app-surface-soft)] text-[var(--app-muted)] ring-[var(--app-border)] hover:text-[var(--app-text)]'"
                    @click="setPeriod(item.value)"
                >
                    {{ item.label }}
                </button>
            </div>

            <div class="mt-6 grid gap-4 xl:grid-cols-[1fr_180px_180px_220px]">
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
                        placeholder="Buscar usuario, acción, módulo, url, ip..."
                        class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] py-3 pl-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                    />
                </div>

                <select
                    v-model="action"
                    class="rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                >
                    <option value="">Todas las acciones</option>
                    <option v-for="item in actions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select
                    v-model="module"
                    class="rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                >
                    <option value="">Todos los módulos</option>
                    <option v-for="item in modules" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select
                    v-model="userId"
                    class="rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                >
                    <option value="">Todos los usuarios</option>
                    <option v-for="item in users" :key="item.id" :value="item.id">
                        {{ item.name }} - {{ item.email }}
                    </option>
                </select>
            </div>

            <div class="mt-4 grid gap-4 xl:grid-cols-[180px_180px_160px_1fr]">
                <input
                    v-model="dateFrom"
                    type="date"
                    class="rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                    @change="useCustomDates"
                />

                <input
                    v-model="dateTo"
                    type="date"
                    class="rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                    @change="useCustomDates"
                />

                <select
                    v-model="perPage"
                    class="rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                >
                    <option value="15">15 registros</option>
                    <option value="30">30 registros</option>
                    <option value="50">50 registros</option>
                    <option value="100">100 registros</option>
                </select>

                <div class="flex justify-end">
                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] px-5 py-2 text-sm font-black transition"
                        :class="hasFilters
                            ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary-text)] hover:opacity-90'
                            : 'text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)]'"
                        @click="clearFilters"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </section>

        <section class="relative mt-6 overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
            <div
                v-if="tableLoading"
                class="absolute inset-0 z-30 flex items-center justify-center bg-[var(--app-card)]/70 backdrop-blur-sm"
            >
                <div class="flex flex-col items-center gap-3 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] px-7 py-6 shadow-xl">
                    <div class="h-12 w-12 animate-spin rounded-full border-4 border-[var(--app-primary-soft)] border-t-[var(--app-primary)]"></div>

                    <p class="text-sm font-black text-[var(--app-text)]">
                        Actualizando bitácora...
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-2 border-b border-[var(--app-border)] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-[var(--app-text)]">
                        Registro de eventos
                    </h2>

                    <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                        Mostrando {{ meta.from ?? 0 }} - {{ meta.to ?? 0 }} de {{ meta.total ?? 0 }}
                    </p>
                </div>

                <p class="text-sm font-black text-[var(--app-primary)]">
                    Autoactualización cada 5s
                    <span v-if="lastUpdated"> · última: {{ lastUpdated }}</span>
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1180px] text-left">
                    <thead>
                        <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4">Acción</th>
                            <th class="px-6 py-4">Módulo</th>
                            <th class="px-6 py-4 text-center">Método / Estado</th>
                            <th class="px-6 py-4">Ruta / URL</th>
                            <th class="px-6 py-4 text-center">Fecha / Hora</th>
                            <th class="px-6 py-4">IP</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[var(--app-border)]">
                        <tr
                            v-for="log in rows"
                            :key="log.id"
                            class="group transition hover:bg-[var(--app-surface-soft)]"
                        >
                            <td class="px-6 py-5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-[var(--app-text)]">
                                        {{ log.user_name ?? 'Sin usuario' }}
                                    </p>

                                    <p class="mt-0.5 break-all text-xs font-semibold text-[var(--app-muted)]">
                                        {{ log.user_email ?? 'Sin correo' }}
                                    </p>

                                    <span
                                        class="mt-2 inline-flex rounded-full px-3 py-1 text-[11px] font-black ring-1"
                                        :class="roleClass(log.user_role)"
                                    >
                                        {{ log.user_role ?? 'Sin rol' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1"
                                    :class="actionClass(log.action)"
                                >
                                    {{ log.action ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="max-w-[180px] break-words text-sm font-bold text-[var(--app-text)]">
                                    {{ log.module ?? 'Sin módulo' }}
                                </p>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="mx-auto inline-flex min-w-[135px] items-center justify-center rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 shadow-sm">
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-black uppercase"
                                                :class="methodTextClass(log.method)"
                                            >
                                                {{ methodLabel(log.method) }}
                                            </span>

                                            <span class="text-xs font-black text-[var(--app-muted)]">
                                                /
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-black ring-1"
                                                :class="statusSoftClass(log.status_code)"
                                            >
                                                <span
                                                    class="h-2 w-2 rounded-full"
                                                    :class="statusDotClass(log.status_code)"
                                                ></span>

                                                {{ statusLabel(log.status_code) }}
                                            </span>
                                        </div>

                                        <span class="text-[10px] font-bold text-[var(--app-muted)]">
                                            {{ statusText(log.status_code) }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="max-w-[460px]">
                                    <p class="break-words text-sm font-black text-[var(--app-text)]">
                                        {{ log.route_name ?? 'Sin ruta' }}
                                    </p>

                                    <p
                                        class="mt-1 break-all text-xs font-semibold leading-5 text-[var(--app-muted)]"
                                        :title="log.url"
                                    >
                                        {{ log.url ?? 'Sin URL' }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="mx-auto inline-flex min-w-[140px] flex-col items-center rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 shadow-sm">
                                    <span class="text-sm font-black text-[var(--app-text)]">
                                        {{ log.date ?? log.fecha_bolivia ?? '-' }}
                                    </span>

                                    <span class="mt-1 rounded-full bg-[var(--app-primary-soft)] px-3 py-1 text-xs font-black text-[var(--app-primary-text)]">
                                        {{ log.time ?? log.hora_bolivia ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <p class="text-sm font-semibold text-[var(--app-muted)]">
                                    {{ log.ip_address ?? 'Sin IP' }}
                                </p>
                            </td>
                        </tr>

                        <tr v-if="rows.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--app-primary-soft)] text-[var(--app-primary)]">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M8 4h8l4 4v12H4V4h4zM15 4v5h5M8 13h8M8 17h5"
                                        />
                                    </svg>
                                </div>

                                <p class="mt-4 text-lg font-black text-[var(--app-text)]">
                                    No hay registros de bitácora
                                </p>

                                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                    Las acciones aparecerán aquí cuando los usuarios usen el sistema.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="Number(meta.last_page ?? 1) > 1"
                class="flex items-center justify-between border-t border-[var(--app-border)] px-6 py-4"
            >
                <button
                    type="button"
                    class="rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] disabled:opacity-40"
                    :disabled="Number(meta.current_page ?? 1) <= 1"
                    @click="goToPage(Number(meta.current_page ?? 1) - 1)"
                >
                    Anterior
                </button>

                <p class="text-sm font-black text-[var(--app-muted)]">
                    Página {{ meta.current_page }} de {{ meta.last_page }}
                </p>

                <button
                    type="button"
                    class="rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] disabled:opacity-40"
                    :disabled="Number(meta.current_page ?? 1) >= Number(meta.last_page ?? 1)"
                    @click="goToPage(Number(meta.current_page ?? 1) + 1)"
                >
                    Siguiente
                </button>
            </div>
        </section>
    </SidebarLayout>
</template>
