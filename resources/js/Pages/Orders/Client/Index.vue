<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import OrderProductPicker from '@/Pages/Orders/Partials/OrderProductPicker.vue';

const props = defineProps({
    orders: {
        type: Object,
        default: () => ({}),
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
    orderTypes: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const modalOpen = ref(false);
const detailOpen = ref(false);
const selectedOrder = ref(null);
const cancelOpen = ref(false);
const orderToCancel = ref(null);
const listLoading = ref(false);

const rows = computed(() => props.orders?.data ?? []);
const meta = computed(() => props.orders?.meta ?? {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
    order_type: 'Mesa',
    tables_id: '',
    items: [],
});

const cancelForm = useForm({});

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.order_type = 'Mesa';
    form.tables_id = '';
    form.items = [];
};

const openCreate = () => {
    resetForm();
    modalOpen.value = true;
};

const closeModal = () => {
    if (form.processing) {
        return;
    }

    modalOpen.value = false;
    resetForm();
};

const submit = () => {
    if (form.order_type === 'Para llevar') {
        form.tables_id = '';
    }

    form.post(route('client.orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            reloadList();
        },
    });
};

const reloadList = (pageNumber = null) => {
    listLoading.value = true;

    const payload = {};

    if (pageNumber) {
        payload.page = pageNumber;
    }

    router.get(route('client.orders.index'), payload, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['orders', 'products', 'categories', 'tables'],
        onFinish: () => {
            listLoading.value = false;
        },
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

    cancelForm.patch(route('client.orders.cancel', orderToCancel.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCancel();
            reloadList();
        },
    });
};

const canCancel = (order) => {
    return order.status === 'Pendiente';
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
    <Head title="Mis pedidos" />

    <SidebarLayout title="Mis pedidos" subtitle="Realiza pedidos y consulta tus compras">
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

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Pedidos
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Historial de pedidos
                        </h1>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Puedes pedir desde una mesa o solicitar para llevar.
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

            <section class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div
                    v-if="listLoading"
                    class="absolute inset-0 z-30 flex items-center justify-center bg-[var(--app-card)]/70 backdrop-blur-sm"
                >
                    <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] px-7 py-6 shadow-xl">
                        <p class="text-sm font-black text-[var(--app-text)]">Actualizando pedidos...</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-b border-[var(--app-border)] px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-[var(--app-text)]">Mis compras realizadas</h2>
                        <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                            Mostrando {{ meta.from ?? 0 }} - {{ meta.to ?? 0 }} de {{ meta.total ?? 0 }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Tipo</th>
                                <th class="px-6 py-4">Ubicación</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4">Estado</th>
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
                                    <p class="font-black text-[var(--app-text)]">{{ order.date_formatted }}</p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">{{ order.hour }}</p>
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ order.order_type }}
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ order.table?.name ?? 'Sin mesa' }}
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ formatMoney(order.total_price) }}
                                </td>

                                <td class="px-6 py-5">
                                    <span
                                        class="rounded-xl px-3 py-1 text-xs font-black"
                                        :class="stateClass(order.status)"
                                    >
                                        {{ order.status }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-2 text-sm font-black text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)]"
                                            @click="openDetail(order)"
                                        >
                                            Ver detalle
                                        </button>

                                        <button
                                            v-if="canCancel(order)"
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
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <p class="text-lg font-black text-[var(--app-text)]">Todavía no tienes pedidos</p>
                                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                        Crea tu primer pedido seleccionando productos.
                                    </p>
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
                    <h2 class="text-2xl font-black text-[var(--app-text)]">Nuevo pedido</h2>
                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Selecciona tipo de pedido, mesa si corresponde y productos del catálogo.
                    </p>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Tipo de pedido</label>
                            <select
                                v-model="form.order_type"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            >
                                <option v-for="type in orderTypes" :key="type" :value="type">
                                    {{ type }}
                                </option>
                            </select>
                        </div>

                        <div v-if="form.order_type === 'Mesa'">
                            <label class="text-sm font-black text-[var(--app-text)]">
                                Mesa donde te encuentras
                            </label>
                            <select
                                v-model="form.tables_id"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            >
                                <option value="">Selecciona una mesa</option>
                                <option v-for="table in tables" :key="table.id" :value="table.id">
                                    {{ table.name }} · {{ table.amount }} personas
                                </option>
                            </select>
                            <p v-if="form.errors.tables_id" class="mt-1 text-sm font-bold text-red-500">
                                {{ form.errors.tables_id }}
                            </p>
                        </div>
                    </div>

                    <OrderProductPicker
                        v-model:items="form.items"
                        :products="products"
                        :categories="categories"
                        :errors="form.errors"
                        title="Arma tu pedido"
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
                            {{ form.processing ? 'Enviando...' : 'Enviar pedido' }}
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

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">Tipo</p>
                        <p class="mt-1 font-black text-[var(--app-text)]">{{ selectedOrder?.order_type }}</p>
                    </div>

                    <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">Ubicación</p>
                        <p class="mt-1 font-black text-[var(--app-text)]">{{ selectedOrder?.table?.name ?? 'Sin mesa' }}</p>
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

                        <p class="font-black text-[var(--app-text)]">
                            {{ formatMoney(detail.price_sale) }}
                        </p>
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
