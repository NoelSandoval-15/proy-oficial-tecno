<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import OrderProductPicker from '@/Pages/Orders/Partials/OrderProductPicker.vue';

const props = defineProps({
    orders: {
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
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    tables: {
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
            today_total: 0,
        }),
    },
});

const page = usePage();

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const orderType = ref(props.filters?.order_type ?? '');
const date = ref(props.filters?.date ?? '');
const perPage = ref(props.filters?.per_page ?? '10');

const listLoading = ref(false);
const modalOpen = ref(false);
const modalMode = ref('create');
const editingOrder = ref(null);
const detailOpen = ref(false);
const selectedOrder = ref(null);
const cancelOpen = ref(false);
const orderToCancel = ref(null);

const clientSearch = ref('');
const clientResults = ref([]);
const selectedClient = ref(null);
const searchingClients = ref(false);

let searchTimeout = null;
let clientTimeout = null;

const rows = computed(() => props.orders?.data ?? []);
const meta = computed(() => props.orders?.meta ?? {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
    status: 'Pendiente',
    order_type: 'Mesa',
    users_client_id: '',
    tables_id: '',
    reservations_id: '',
    items: [],
});

const statusForm = useForm({
    status: '',
});

const cancelForm = useForm({});

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
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

    router.get(route('admin.orders.index'), buildFilters(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['orders', 'filters', 'stats', 'products', 'categories', 'tables'],
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
    perPage.value = '10';
    reloadList();
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.status = 'Pendiente';
    form.order_type = 'Mesa';
    form.users_client_id = '';
    form.tables_id = '';
    form.reservations_id = '';
    form.items = [];
    clientSearch.value = '';
    clientResults.value = [];
    selectedClient.value = null;
    editingOrder.value = null;
};

const openCreate = () => {
    modalMode.value = 'create';
    resetForm();
    modalOpen.value = true;
};

const openEdit = (order) => {
    modalMode.value = 'edit';
    editingOrder.value = order;

    form.status = order.status;
    form.order_type = order.order_type ?? 'Mesa';
    form.users_client_id = order.users_client_id ?? '';
    form.tables_id = order.tables_id ?? '';
    form.reservations_id = order.reservations_id ?? '';
    form.items = (order.details ?? []).map((detail) => ({
        products_id: detail.products_id,
        amount: detail.amount,
    }));

    selectedClient.value = order.client ?? null;
    clientSearch.value = order.client?.label ?? order.client?.name ?? '';

    modalOpen.value = true;
};

const closeModal = () => {
    if (form.processing) {
        return;
    }

    modalOpen.value = false;
    resetForm();
};

const searchClients = async () => {
    const value = clientSearch.value.trim();

    if (value.length < 2) {
        clientResults.value = [];
        return;
    }

    searchingClients.value = true;

    try {
        const url = new URL(route('admin.orders.search-clients'));
        url.searchParams.append('search', value);

        const response = await fetch(url);
        const data = await response.json();

        clientResults.value = response.ok ? data : [];
    } catch {
        clientResults.value = [];
    } finally {
        searchingClients.value = false;
    }
};

watch(clientSearch, () => {
    clearTimeout(clientTimeout);

    if (selectedClient.value && clientSearch.value === selectedClient.value.label) {
        return;
    }

    selectedClient.value = null;
    form.users_client_id = '';

    clientTimeout = setTimeout(() => {
        searchClients();
    }, 350);
});

const selectClient = (client) => {
    selectedClient.value = client;
    form.users_client_id = client.id;
    clientSearch.value = client.label || client.name;
    clientResults.value = [];
};

const clearSelectedClient = () => {
    selectedClient.value = null;
    form.users_client_id = '';
    clientSearch.value = '';
    clientResults.value = [];
};

const submit = () => {
    if (form.order_type !== 'Mesa') {
        form.tables_id = '';
    }

    if (modalMode.value === 'create') {
        form.post(route('admin.orders.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                reloadList();
            },
        });

        return;
    }

    form.put(route('admin.orders.update', editingOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            reloadList();
        },
    });
};

const changeStatus = (order, newStatus) => {
    statusForm.status = newStatus;

    statusForm.patch(route('admin.orders.change-status', order.id), {
        preserveScroll: true,
        onSuccess: () => reloadList(),
    });
};

const openDetail = (order) => {
    selectedOrder.value = order;
    detailOpen.value = true;
};

const closeDetail = () => {
    selectedOrder.value = null;
    detailOpen.value = false;
};

const askCancel = (order) => {
    orderToCancel.value = order;
    cancelOpen.value = true;
};

const closeCancel = () => {
    if (cancelForm.processing) {
        return;
    }

    orderToCancel.value = null;
    cancelOpen.value = false;
};

const cancelOrder = () => {
    if (!orderToCancel.value) {
        return;
    }

    cancelForm.patch(route('admin.orders.cancel', orderToCancel.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCancel();
            reloadList();
        },
    });
};

const goToPage = (pageNumber) => {
    reloadList(pageNumber);
};

const stateClass = (value) => {
    if (value === 'Pendiente') return 'bg-yellow-500/10 text-yellow-600';
    if (value === 'En preparación') return 'bg-purple-500/10 text-purple-600';
    if (value === 'Listo') return 'bg-blue-500/10 text-blue-600';
    if (value === 'Entregado') return 'bg-green-500/10 text-green-600';
    if (value === 'Cancelado') return 'bg-red-500/10 text-red-500';
    if (value === 'Pagado') return 'bg-green-500/10 text-green-700';
    return 'bg-gray-500/10 text-gray-600';
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('action') === 'create') {
        openCreate();
    }
});
</script>

<template>
    <Head title="Pedidos" />

    <SidebarLayout title="Pedidos" subtitle="Gestión interna de pedidos, ventas y atención en mesa">
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
                    <p class="text-sm font-bold text-[var(--app-muted)]">Pedidos</p>
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
                    <p class="text-sm font-bold text-[var(--app-muted)]">Ventas de hoy</p>
                    <p class="mt-2 text-3xl font-black text-green-600">{{ formatMoney(stats.today_total) }}</p>
                </article>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Pedidos
                        </p>
                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Pedidos y ventas del local
                        </h1>
                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Gestiona pedidos en mesa, mostrador y para llevar.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90"
                        @click="openCreate"
                    >
                        Nuevo pedido
                    </button>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[1fr_220px_220px_180px_160px_160px] xl:items-end">
                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Buscar</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cliente, CI, mesa o tipo"
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
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
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
                        Actualizando pedidos...
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-left">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Ubicación</th>
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4">Atendido por</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--app-border)]">
                            <tr
                                v-for="order in rows"
                                :key="order.id"
                                class="transition hover:bg-[var(--app-surface-soft)]"
                            >
                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">
                                        {{ order.client?.label ?? order.client?.name ?? 'Venta sin cliente' }}
                                    </p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        {{ order.client?.email ?? 'Mostrador' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">{{ order.order_type }}</p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        {{ order.table?.name ?? 'Sin mesa' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">{{ order.date_formatted }}</p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">{{ order.hour }}</p>
                                </td>

                                <td class="px-6 py-5 font-black text-[var(--app-text)]">
                                    {{ formatMoney(order.total_price) }}
                                </td>

                                <td class="px-6 py-5">
                                    <select
                                        :value="order.status"
                                        class="rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-sm font-black text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                        @change="changeStatus(order, $event.target.value)"
                                    >
                                        <option v-for="item in statuses" :key="item" :value="item">
                                            {{ item }}
                                        </option>
                                    </select>
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ order.admin?.name ?? 'Sin asignar' }}
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-2 text-sm font-black transition hover:bg-[var(--app-primary-soft)]"
                                            @click="openDetail(order)"
                                        >
                                            Detalle
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-2 text-sm font-black transition hover:bg-[var(--app-primary-soft)]"
                                            @click="openEdit(order)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            v-if="order.status !== 'Cancelado' && order.status !== 'Pagado'"
                                            type="button"
                                            class="rounded-xl bg-red-500/10 px-4 py-2 text-sm font-black text-red-500 transition hover:bg-red-500/20"
                                            @click="askCancel(order)"
                                        >
                                            Cancelar
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <p class="text-lg font-black text-[var(--app-text)]">No hay pedidos registrados</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeModal"
        >
            <div class="max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-6">
                    <h2 class="text-2xl font-black text-[var(--app-text)]">
                        {{ modalMode === 'create' ? 'Nuevo pedido' : 'Editar pedido' }}
                    </h2>
                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Puede ser en mesa, mostrador o para llevar.
                    </p>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Estado</label>
                            <select
                                v-model="form.status"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            >
                                <option v-for="item in statuses" :key="item" :value="item">
                                    {{ item }}
                                </option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm font-bold text-red-500">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Tipo</label>
                            <select
                                v-model="form.order_type"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            >
                                <option v-for="item in orderTypes" :key="item" :value="item">
                                    {{ item }}
                                </option>
                            </select>
                            <p v-if="form.errors.order_type" class="mt-1 text-sm font-bold text-red-500">
                                {{ form.errors.order_type }}
                            </p>
                        </div>

                        <div v-if="form.order_type === 'Mesa'">
                            <label class="text-sm font-black text-[var(--app-text)]">Mesa</label>
                            <select
                                v-model="form.tables_id"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            >
                                <option value="">Selecciona mesa</option>
                                <option v-for="table in tables" :key="table.id" :value="table.id">
                                    {{ table.name }} · {{ table.amount }} personas
                                </option>
                            </select>
                            <p v-if="form.errors.tables_id" class="mt-1 text-sm font-bold text-red-500">
                                {{ form.errors.tables_id }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Cliente opcional</label>
                        <input
                            v-model="clientSearch"
                            type="text"
                            placeholder="Buscar por CI, nombre, apellido o correo"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />

                        <div
                            v-if="clientResults.length"
                            class="mt-2 max-h-72 overflow-y-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)]"
                        >
                            <button
                                v-for="client in clientResults"
                                :key="client.id"
                                type="button"
                                class="block w-full border-b border-[var(--app-border)] px-4 py-3 text-left transition hover:bg-[var(--app-surface-soft)]"
                                @click="selectClient(client)"
                            >
                                <p class="text-sm font-black text-[var(--app-text)]">{{ client.label }}</p>
                                <p class="text-xs font-semibold text-[var(--app-muted)]">
                                    CI: {{ client.ci ?? 'Sin CI' }} · {{ client.email }}
                                </p>
                            </button>
                        </div>

                        <p v-if="searchingClients" class="mt-1 text-xs font-bold text-[var(--app-primary)]">
                            Buscando clientes...
                        </p>

                        <div
                            v-if="selectedClient"
                            class="mt-3 flex items-center justify-between gap-3 rounded-2xl border border-green-500/20 bg-green-500/10 px-4 py-3"
                        >
                            <p class="text-sm font-black text-green-700">
                                Cliente seleccionado: {{ selectedClient.label }}
                            </p>
                            <button
                                type="button"
                                class="rounded-xl bg-white/70 px-3 py-2 text-xs font-black text-red-500"
                                @click="clearSelectedClient"
                            >
                                Quitar
                            </button>
                        </div>
                    </div>

                    <OrderProductPicker
                        v-model:items="form.items"
                        :products="products"
                        :categories="categories"
                        :errors="form.errors"
                        :title="modalMode === 'create' ? 'Productos del pedido' : 'Editar productos del pedido'"
                    />

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            class="flex-1 rounded-2xl border border-[var(--app-border)] px-5 py-3 text-sm font-black text-[var(--app-muted)]"
                            @click="closeModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="flex-1 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Guardando...' : modalMode === 'create' ? 'Guardar' : 'Actualizar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="detailOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeDetail"
        >
            <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <h2 class="text-2xl font-black text-[var(--app-text)]">Detalle del pedido</h2>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">Tipo</p>
                        <p class="mt-1 font-black text-[var(--app-text)]">{{ selectedOrder?.order_type }}</p>
                    </div>

                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">Ubicación</p>
                        <p class="mt-1 font-black text-[var(--app-text)]">{{ selectedOrder?.table?.name ?? 'Sin mesa' }}</p>
                    </div>

                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">Cliente</p>
                        <p class="mt-1 font-black text-[var(--app-text)]">
                            {{ selectedOrder?.client?.label ?? selectedOrder?.client?.name ?? 'Sin cliente' }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div
                        v-for="detail in selectedOrder?.details ?? []"
                        :key="detail.id"
                        class="flex items-center justify-between rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3"
                    >
                        <div>
                            <p class="font-black text-[var(--app-text)]">{{ detail.product?.name }}</p>
                            <p class="text-sm font-semibold text-[var(--app-muted)]">
                                Cantidad: {{ detail.amount }}
                            </p>
                        </div>

                        <p class="font-black text-[var(--app-text)]">{{ formatMoney(detail.price_sale) }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl bg-[var(--app-surface-soft)] px-5 py-4 text-right">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Total</p>
                    <p class="text-3xl font-black text-[var(--app-text)]">
                        {{ formatMoney(selectedOrder?.total_price) }}
                    </p>
                </div>

                <button
                    type="button"
                    class="mt-5 w-full rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white"
                    @click="closeDetail"
                >
                    Cerrar
                </button>
            </div>
        </div>

        <div
            v-if="cancelOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeCancel"
        >
            <div class="w-full max-w-md rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <h2 class="text-xl font-black text-[var(--app-text)]">Cancelar pedido</h2>
                <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                    ¿Seguro que deseas cancelar este pedido?
                </p>

                <div class="mt-6 flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-2xl border border-[var(--app-border)] px-5 py-3 text-sm font-black text-[var(--app-muted)]"
                        @click="closeCancel"
                    >
                        Volver
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-2xl bg-red-500 px-5 py-3 text-sm font-black text-white disabled:opacity-60"
                        :disabled="cancelForm.processing"
                        @click="cancelOrder"
                    >
                        {{ cancelForm.processing ? 'Cancelando...' : 'Cancelar' }}
                    </button>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
