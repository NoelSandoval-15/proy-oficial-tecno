<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    purchaseNotes: {
        type: Object,
        required: true,
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            supplier_id: '',
            date_from: '',
            date_to: '',
            period: 'all',
            sort: 'recent',
            per_page: '10',
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            purchases: 0,
            total_spent: 0,
            total_amount: 0,
            max_purchase: 0,
        }),
    },
    allowedPerPage: {
        type: Array,
        default: () => ['10', '20', '30', '50', '100', 'all'],
    },
});

const page = usePage();

const search = ref(props.filters.search ?? '');
const supplierId = ref(props.filters.supplier_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const period = ref(props.filters.period ?? 'all');
const sort = ref(props.filters.sort ?? 'recent');
const perPage = ref(props.filters.per_page ?? '10');

const tableLoading = ref(false);
const purchaseToDelete = ref(null);

let filterTimeout = null;

const rows = computed(() => props.purchaseNotes?.data ?? []);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const currentPage = computed(() => props.purchaseNotes?.current_page ?? 1);
const lastPage = computed(() => props.purchaseNotes?.last_page ?? 1);
const fromRow = computed(() => props.purchaseNotes?.from ?? 0);
const toRow = computed(() => props.purchaseNotes?.to ?? 0);
const totalRows = computed(() => props.purchaseNotes?.total ?? 0);

const buildFilters = (pageNumber = null) => {
    const payload = {
        search: search.value,
        supplier_id: supplierId.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        period: period.value,
        sort: sort.value,
        per_page: perPage.value,
    };

    if (pageNumber) {
        payload.page = pageNumber;
    }

    return payload;
};

const reloadList = (pageNumber = null, preserveState = true) => {
    tableLoading.value = true;

    router.get(route('insumos.purchases.index'), buildFilters(pageNumber), {
        preserveState,
        preserveScroll: true,
        replace: true,
        only: ['purchaseNotes', 'suppliers', 'filters', 'stats', 'allowedPerPage', 'flash'],
        onFinish: () => {
            tableLoading.value = false;
        },
    });
};

watch([search, supplierId, dateFrom, dateTo, period, sort, perPage], () => {
    clearTimeout(filterTimeout);

    filterTimeout = setTimeout(() => {
        reloadList(1, true);
    }, 350);
});

const clearFilters = () => {
    search.value = '';
    supplierId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    period.value = 'all';
    sort.value = 'recent';
    perPage.value = '10';

    reloadList(1, true);
};

const expensiveThisMonth = () => {
    search.value = '';
    supplierId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    period.value = 'this_month';
    sort.value = 'highest_total';

    reloadList(1, true);
};

const goToPage = (pageNumber) => {
    reloadList(pageNumber, false);
};

const exportPurchases = (type) => {
    const params = new URLSearchParams(buildFilters()).toString();

    const routeName = {
        excel: 'insumos.purchases.export.excel',
        pdf: 'insumos.purchases.export.pdf',
        txt: 'insumos.purchases.export.txt',
    }[type];

    window.open(`${route(routeName)}?${params}`, '_blank');
};

const confirmDelete = (purchase) => {
    purchaseToDelete.value = purchase;
};

const closeDeleteModal = () => {
    purchaseToDelete.value = null;
};

const destroyPurchase = () => {
    if (!purchaseToDelete.value) {
        return;
    }

    tableLoading.value = true;

    router.delete(route('insumos.purchases.destroy', purchaseToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            purchaseToDelete.value = null;
            reloadList(1, false);
        },
        onFinish: () => {
            tableLoading.value = false;
        },
    });
};

const detailsCount = (purchase) => {
    return purchase.details_purchases?.length ?? 0;
};

const amountTotal = (purchase) => {
    return (purchase.details_purchases ?? []).reduce((total, detail) => {
        return total + Number(detail.amount ?? 0);
    }, 0);
};

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};

const perPageLabel = (value) => {
    return value === 'all' ? 'Todos' : value;
};
</script>

<template>
    <Head title="Compras de insumos" />

    <SidebarLayout
        title="Compras de insumos"
        subtitle="Control de compras, gastos, proveedores y actualización automática de stock"
    >
        <div class="space-y-6">
            <div
                v-if="flashSuccess"
                class="rounded-3xl border border-green-500/20 bg-green-500/10 px-5 py-4 text-sm font-black text-green-600"
            >
                {{ flashSuccess }}
            </div>

            <div
                v-if="flashError"
                class="rounded-3xl border border-red-500/20 bg-red-500/10 px-5 py-4 text-sm font-black text-red-600"
            >
                {{ flashError }}
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-bold text-[var(--app-muted)]">Compras</p>
                        <div class="rounded-2xl bg-[var(--app-primary-soft)] p-3 text-[var(--app-primary)]">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h10a2 2 0 012 2v16l-3-2-3 2-3-2-3 2V5a2 2 0 012-2zm2 5h6M9 12h6M9 16h4" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-4xl font-black text-[var(--app-text)]">{{ stats.purchases }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-bold text-[var(--app-muted)]">Total gastado</p>
                        <div class="rounded-2xl bg-red-500/10 p-3 text-red-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-2.5 0-4 .9-4 2.2 0 1.5 1.5 2 4 2.3 2.5.3 4 .8 4 2.3 0 1.3-1.5 2.2-4 2.2m0-9V6m0 12v-2M4 6h16v12H4V6z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-3xl font-black text-[var(--app-primary)]">{{ formatMoney(stats.total_spent) }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-bold text-[var(--app-muted)]">Cantidad comprada</p>
                        <div class="rounded-2xl bg-green-500/10 p-3 text-green-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-4xl font-black text-[var(--app-text)]">{{ stats.total_amount }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-bold text-[var(--app-muted)]">Compra más alta</p>
                        <div class="rounded-2xl bg-yellow-500/10 p-3 text-yellow-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17l6-6 4 4 6-8M14 7h6v6" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-3xl font-black text-red-500">{{ formatMoney(stats.max_purchase) }}</p>
                </article>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Gestión de compras
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Historial de compras
                        </h1>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Las compras se muestran desde la más reciente.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportPurchases('excel')"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h10l6 6v10a2 2 0 01-2 2H4V4zm10 0v6h6M8 13l2 3m0 0l2-3m-2 3l-2 3m2-3l2 3" />
                            </svg>
                            Excel
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportPurchases('pdf')"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7V3zm7 0v5h5M5 9H3v9h2m4-5h1.5a1.5 1.5 0 010 3H9v2m5-5v5m0-5h2a2 2 0 010 4h-2" />
                            </svg>
                            PDF
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportPurchases('txt')"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3h8l4 4v14H6V3zm8 0v4h4M9 12h6M9 16h6M9 8h2" />
                            </svg>
                            TXT
                        </button>

                        <Link
                            :href="route('insumos.purchases.create')"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" />
                            </svg>
                            Nueva compra
                        </Link>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
                    <div class="sm:col-span-2 xl:col-span-2 2xl:col-span-2">
                        <label class="text-sm font-black text-[var(--app-text)]">
                            Buscar en tiempo real
                        </label>

                        <div class="relative mt-2">
                            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>

                            <input
                                v-model="search"
                                type="text"
                                placeholder="Proveedor, insumo, usuario..."
                                class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] py-3 pl-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Proveedor</label>
                        <select
                            v-model="supplierId"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="">Todos</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Desde</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Hasta</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Periodo</label>
                        <select
                            v-model="period"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="all">Todos</option>
                            <option value="this_month">Este mes</option>
                            <option value="last_2_months">Últimos 2 meses</option>
                            <option value="last_6_months">Últimos 6 meses</option>
                            <option value="last_year">Último año</option>
                            <option value="last_2_years">Últimos 2 años</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Orden</label>
                        <select
                            v-model="sort"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="recent">Más recientes</option>
                            <option value="highest_total">Gastos más caros</option>
                            <option value="lowest_total">Gastos más bajos</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Mostrar</label>
                        <select
                            v-model="perPage"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option
                                v-for="option in allowedPerPage"
                                :key="option"
                                :value="option"
                            >
                                {{ perPageLabel(option) }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)]"
                        @click="clearFilters"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar filtros
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-red-500/10 px-5 py-3 text-sm font-black text-red-500 transition hover:bg-red-500/20"
                        @click="expensiveThisMonth"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17l6-6 4 4 6-8M14 7h6v6" />
                        </svg>
                        Gastos más caros de este mes
                    </button>
                </div>
            </section>

            <section class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div
                    v-if="tableLoading"
                    class="absolute inset-0 z-30 flex items-center justify-center bg-[var(--app-card)]/80 backdrop-blur-sm"
                >
                    <div class="flex flex-col items-center gap-3 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] px-7 py-6 shadow-xl">
                        <div class="h-12 w-12 animate-spin rounded-full border-4 border-[var(--app-primary-soft)] border-t-[var(--app-primary)]"></div>
                        <p class="text-sm font-black text-[var(--app-text)]">Actualizando compras...</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-b border-[var(--app-border)] px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-[var(--app-text)]">Lista de compras</h2>
                        <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                            Mostrando {{ fromRow ?? 0 }} - {{ toRow ?? 0 }} de {{ totalRows ?? 0 }}
                        </p>
                    </div>

                    <p class="text-sm font-black text-[var(--app-primary)]">
                        Registros visibles: {{ rows.length }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-left">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                <th class="px-6 py-4">Fecha / hora</th>
                                <th class="px-6 py-4">Proveedor</th>
                                <th class="px-6 py-4">Administrador</th>
                                <th class="px-6 py-4">Detalle</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--app-border)]">
                            <tr
                                v-for="purchase in rows"
                                :key="purchase.id"
                                class="transition hover:bg-[var(--app-surface-soft)]"
                            >
                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">{{ purchase.date }}</p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">{{ purchase.hour }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">
                                        {{ purchase.suppliers?.name ?? 'Sin proveedor' }}
                                    </p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        {{ purchase.suppliers?.telephone ?? 'Sin teléfono' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">
                                        {{ purchase.users?.name ?? 'Sin usuario' }}
                                    </p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        {{ purchase.users?.email ?? 'Sin correo' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">
                                        {{ detailsCount(purchase) }} insumos
                                    </p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        Cantidad total: {{ amountTotal(purchase) }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-xl bg-[var(--app-primary-soft)] px-3 py-1 text-sm font-black text-[var(--app-primary-text)]">
                                        {{ formatMoney(purchase.total_price) }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="route('insumos.purchases.show', purchase.id)"
                                            title="Ver detalle"
                                            class="rounded-xl bg-[var(--app-surface-soft)] p-2 text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)]"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                            </svg>
                                        </Link>

                                        <button
                                            type="button"
                                            title="Eliminar compra"
                                            class="rounded-xl bg-red-500/10 p-2 text-red-500 transition hover:bg-red-500/20"
                                            @click="confirmDelete(purchase)"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6M14 11v6" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <p class="text-lg font-black text-[var(--app-text)]">No hay compras registradas</p>
                                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                        Intenta cambiar filtros o registra una nueva compra.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="lastPage > 1"
                    class="flex items-center justify-between border-t border-[var(--app-border)] px-6 py-4"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] disabled:opacity-40"
                        :disabled="currentPage <= 1"
                        @click="goToPage(currentPage - 1)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Anterior
                    </button>

                    <p class="text-sm font-black text-[var(--app-muted)]">
                        Página {{ currentPage }} de {{ lastPage }}
                    </p>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] disabled:opacity-40"
                        :disabled="currentPage >= lastPage"
                        @click="goToPage(currentPage + 1)"
                    >
                        Siguiente
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </section>
        </div>

        <div
            v-if="purchaseToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeDeleteModal"
        >
            <div class="w-full max-w-md rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <h3 class="text-xl font-black text-[var(--app-text)]">
                    Eliminar compra
                </h3>

                <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                    Al eliminar esta compra se intentará revertir el stock de los insumos.
                </p>

                <p class="mt-2 text-xs font-bold text-red-500">
                    Si el stock actual ya fue utilizado, no se permitirá eliminarla.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] px-5 py-3 text-sm font-black text-[var(--app-muted)]"
                        @click="closeDeleteModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl bg-red-600 px-5 py-3 text-sm font-black text-white"
                        @click="destroyPurchase"
                    >
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
