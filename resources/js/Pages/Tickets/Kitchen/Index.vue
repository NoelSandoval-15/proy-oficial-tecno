<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    tickets: {
        type: Object,
        default: () => ({}),
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    orderTypes: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            pending: 0,
            preparing: 0,
            ready: 0,
            delivered_today: 0,
        }),
    },
});

const page = usePage();

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const orderType = ref(props.filters?.order_type ?? '');
const date = ref(props.filters?.date ?? '');
const perPage = ref(props.filters?.per_page ?? '12');

const listLoading = ref(false);
const detailOpen = ref(false);
const selectedTicket = ref(null);

const statusForm = useForm({
    status: '',
});

let searchTimeout = null;

const rows = computed(() => props.tickets?.data ?? []);
const meta = computed(() => props.tickets?.meta ?? {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};

const stateClass = (value) => {
    if (value === 'Pendiente') return 'bg-yellow-500/10 text-yellow-600 border-yellow-500/20';
    if (value === 'En preparación') return 'bg-purple-500/10 text-purple-600 border-purple-500/20';
    if (value === 'Listo') return 'bg-blue-500/10 text-blue-600 border-blue-500/20';
    if (value === 'Entregado') return 'bg-green-500/10 text-green-600 border-green-500/20';
    if (value === 'Cancelado') return 'bg-red-500/10 text-red-500 border-red-500/20';

    return 'bg-gray-500/10 text-gray-600 border-gray-500/20';
};

const nextButtonLabel = (nextStatus) => {
    if (nextStatus === 'En preparación') return 'Preparar';
    if (nextStatus === 'Listo') return 'Marcar listo';
    if (nextStatus === 'Entregado') return 'Entregar';
    if (nextStatus === 'Cancelado') return 'Cancelar';

    return nextStatus;
};

const nextButtonClass = (nextStatus) => {
    if (nextStatus === 'Cancelado') {
        return 'bg-red-500/10 text-red-500 hover:bg-red-500/20';
    }

    return 'bg-[var(--app-primary)] text-white hover:opacity-90';
};

const buildFilters = (pageNumber = null) => {
    const payload = {
        search: search.value,
        status: status.value,
        order_type: orderType.value,
        date: date.value,
        per_page: perPage.value,
    };

    if (pageNumber) {
        payload.page = pageNumber;
    }

    return payload;
};

const reloadList = (pageNumber = null) => {
    listLoading.value = true;

    router.get(route('tickets.index'), buildFilters(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['tickets', 'filters', 'stats'],
        onFinish: () => {
            listLoading.value = false;
        },
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => reloadList(), 400);
});

watch(status, () => reloadList());
watch(orderType, () => reloadList());
watch(date, () => reloadList());
watch(perPage, () => reloadList());

const clearFilters = () => {
    search.value = '';
    status.value = '';
    orderType.value = '';
    date.value = '';
    perPage.value = '12';
    reloadList();
};

const changeStatus = (ticket, newStatus) => {
    statusForm.status = newStatus;

    statusForm.patch(route('tickets.change-status', ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            reloadList();
        },
    });
};

const openDetail = (ticket) => {
    selectedTicket.value = ticket;
    detailOpen.value = true;
};

const closeDetail = () => {
    selectedTicket.value = null;
    detailOpen.value = false;
};

const goToPage = (pageNumber) => {
    reloadList(pageNumber);
};
</script>

<template>
    <Head title="Tickets" />

    <SidebarLayout title="Tickets" subtitle="Panel de preparación y seguimiento de pedidos">
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

            <section class="grid gap-4 md:grid-cols-5">
                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Tickets</p>
                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">{{ stats.total }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Pendientes</p>
                    <p class="mt-2 text-4xl font-black text-yellow-600">{{ stats.pending }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">En preparación</p>
                    <p class="mt-2 text-4xl font-black text-purple-600">{{ stats.preparing }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Listos</p>
                    <p class="mt-2 text-4xl font-black text-blue-600">{{ stats.ready }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Entregados hoy</p>
                    <p class="mt-2 text-4xl font-black text-green-600">{{ stats.delivered_today }}</p>
                </article>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                        Cocina / Atención
                    </p>

                    <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                        Tickets de pedidos
                    </h1>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Recibe pedidos, márcalos en preparación, listos o entregados.
                    </p>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[1fr_220px_220px_180px_160px_160px] xl:items-end">
                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Buscar</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Ticket, cliente, CI, mesa o tipo"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Estado</label>
                        <select
                            v-model="status"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="">Todos</option>
                            <option v-for="item in statuses" :key="item" :value="item">
                                {{ item }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Tipo</label>
                        <select
                            v-model="orderType"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="">Todos</option>
                            <option v-for="item in orderTypes" :key="item" :value="item">
                                {{ item }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Fecha</label>
                        <input
                            v-model="date"
                            type="date"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Mostrar</label>
                        <select
                            v-model="perPage"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="12">12</option>
                            <option value="24">24</option>
                            <option value="48">48</option>
                            <option value="all">Todos</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] px-5 py-3 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)]"
                        @click="clearFilters"
                    >
                        Limpiar
                    </button>
                </div>
            </section>

            <section class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div
                    v-if="listLoading"
                    class="absolute inset-0 z-30 flex items-center justify-center bg-[var(--app-card)]/70 backdrop-blur-sm"
                >
                    <p class="rounded-2xl bg-[var(--app-card)] px-6 py-4 font-black text-[var(--app-text)]">
                        Actualizando tickets...
                    </p>
                </div>

                <div class="grid gap-4 p-5 xl:grid-cols-2 2xl:grid-cols-3">
                    <article
                        v-for="ticket in rows"
                        :key="ticket.id"
                        class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5 transition hover:-translate-y-0.5 hover:border-[var(--app-primary)] hover:shadow-lg"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-[var(--app-primary)]">
                                    {{ ticket.ticket_code }}
                                </p>

                                <h2 class="mt-2 text-xl font-black text-[var(--app-text)]">
                                    {{ ticket.client?.label ?? ticket.client?.name ?? 'Venta sin cliente' }}
                                </h2>

                                <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                                    {{ ticket.order_type }} · {{ ticket.table?.name ?? 'Sin mesa' }}
                                </p>
                            </div>

                            <span
                                class="rounded-xl border px-3 py-1 text-xs font-black"
                                :class="stateClass(ticket.status)"
                            >
                                {{ ticket.status }}
                            </span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-[var(--app-card)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Hora
                                </p>

                                <p class="mt-1 font-black text-[var(--app-text)]">
                                    {{ ticket.hour }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[var(--app-card)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Productos
                                </p>

                                <p class="mt-1 font-black text-[var(--app-text)]">
                                    {{ ticket.details?.length ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[var(--app-card)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Total
                                </p>

                                <p class="mt-1 font-black text-[var(--app-text)]">
                                    {{ formatMoney(ticket.total_price) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-2">
                            <div
                                v-for="detail in ticket.details"
                                :key="detail.id"
                                class="flex items-center justify-between rounded-2xl bg-[var(--app-card)] px-4 py-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-[var(--app-text)]">
                                        {{ detail.product?.name }}
                                    </p>

                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        Cantidad: {{ detail.amount }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-[var(--app-card)] px-4 py-2 text-sm font-black text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)]"
                                @click="openDetail(ticket)"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"
                                    />
                                </svg>
                                <span>Ver detalle</span>
                            </button>

                            <Link
                                :href="route('tickets.show', ticket.id)"
                                class="inline-flex items-center gap-2 rounded-xl bg-[var(--app-card)] px-4 py-2 text-sm font-black text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                                <span>Abrir</span>
                            </Link>

                            <button
                                v-for="nextStatus in ticket.allowed_next_statuses"
                                :key="nextStatus"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-black transition disabled:opacity-60"
                                :class="nextButtonClass(nextStatus)"
                                :disabled="statusForm.processing"
                                @click="changeStatus(ticket, nextStatus)"
                            >
                                <svg
                                    v-if="nextStatus !== 'Cancelado'"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.4"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>

                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.4"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>

                                <span>{{ nextButtonLabel(nextStatus) }}</span>
                            </button>
                        </div>
                    </article>

                    <div
                        v-if="rows.length === 0"
                        class="col-span-full rounded-[2rem] border border-dashed border-[var(--app-border)] bg-[var(--app-surface-soft)] px-6 py-12 text-center"
                    >
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 14h6m-6-4h6m-7 10h8a2 2 0 002-2V6.5L14.5 3H8a2 2 0 00-2 2v13a2 2 0 002 2z"
                                />
                            </svg>
                        </div>

                        <p class="mt-4 text-xl font-black text-[var(--app-text)]">
                            No hay tickets registrados
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Cuando lleguen pedidos pendientes, aparecerán en este panel.
                        </p>
                    </div>
                </div>

                <div
                    v-if="meta.last_page > 1"
                    class="flex items-center justify-between border-t border-[var(--app-border)] px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] disabled:opacity-40"
                        :disabled="meta.current_page <= 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        Anterior
                    </button>

                    <p class="text-sm font-black text-[var(--app-muted)]">
                        Página {{ meta.current_page }} de {{ meta.last_page }}
                    </p>

                    <button
                        type="button"
                        class="rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] disabled:opacity-40"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        Siguiente
                    </button>
                </div>
            </section>
        </div>

        <div
            v-if="detailOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeDetail"
        >
            <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            {{ selectedTicket?.ticket_code }}
                        </p>

                        <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                            Detalle del ticket
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-[var(--app-surface-soft)] px-4 py-2 text-sm font-black text-[var(--app-text)]"
                        @click="closeDetail"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.4"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Cerrar</span>
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                            Cliente
                        </p>

                        <p class="mt-1 font-black text-[var(--app-text)]">
                            {{ selectedTicket?.client?.label ?? selectedTicket?.client?.name ?? 'Sin cliente' }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                            Ubicación
                        </p>

                        <p class="mt-1 font-black text-[var(--app-text)]">
                            {{ selectedTicket?.table?.name ?? 'Sin mesa' }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                            Estado
                        </p>

                        <p class="mt-1 font-black text-[var(--app-text)]">
                            {{ selectedTicket?.status }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="detail in selectedTicket?.details ?? []"
                        :key="detail.id"
                        class="flex items-center justify-between rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3"
                    >
                        <div>
                            <p class="font-black text-[var(--app-text)]">
                                {{ detail.product?.name }}
                            </p>

                            <p class="text-sm font-semibold text-[var(--app-muted)]">
                                Cantidad: {{ detail.amount }}
                            </p>
                        </div>

                        <p class="font-black text-[var(--app-text)]">
                            {{ formatMoney(detail.price_sale) }}
                        </p>
                    </article>
                </div>

                <div class="mt-5 rounded-2xl bg-[var(--app-surface-soft)] px-5 py-4 text-right">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Total</p>

                    <p class="text-3xl font-black text-[var(--app-text)]">
                        {{ formatMoney(selectedTicket?.total_price) }}
                    </p>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
