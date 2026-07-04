<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    sales: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    paymentStatuses: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const selectedSale = ref(null);
const selectedPayment = ref(null);
const showQrModal = ref(false);
const showManualModal = ref(false);

const filterForm = useForm({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const manualForm = useForm({
    payment_method: 'cash',
    amount_received: '',
    transaction_id: '',
});

const rows = computed(() => props.sales?.data || []);

const flashSuccess = computed(() => page.props.flash?.success || null);
const flashError = computed(() => page.props.flash?.error || null);
const flashInfo = computed(() => page.props.flash?.info || null);

const money = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const formatDate = (value) => {
    if (!value) return 'Sin fecha';

    if (typeof value === 'string') {
        return value.substring(0, 10);
    }

    return value;
};

const formatDateTime = (value) => {
    if (!value) return 'No definido';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};

const clientName = (sale) => {
    const profile = sale?.users_client?.profile;

    if (profile) {
        return `${profile.name || ''} ${profile.last_name || ''}`.trim();
    }

    return sale?.users_client?.name || 'Cliente mostrador';
};

const clientEmail = (sale) => {
    return sale?.users_client?.email || 'Sin correo';
};

const clientPhone = (sale) => {
    return sale?.users_client?.profile?.telephone || 'Sin teléfono';
};

const payment = (sale) => {
    return sale?.latest_payment || null;
};

const paymentStatus = (sale) => {
    return payment(sale)?.status || null;
};

const saleStatusClass = (status) => {
    const value = String(status || '').toLowerCase();

    if (value === 'pagado') {
        return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700';
    }

    if (value === 'entregado') {
        return 'border-amber-500/30 bg-amber-500/10 text-amber-700';
    }

    if (value === 'cancelado') {
        return 'border-red-500/30 bg-red-500/10 text-red-700';
    }

    return 'border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-muted)]';
};

const paymentStatusClass = (status) => {
    const value = String(status || '').toLowerCase();

    if (value === 'paid') {
        return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700';
    }

    if (value === 'qr_generated') {
        return 'border-blue-500/30 bg-blue-500/10 text-blue-700';
    }

    if (value === 'pending') {
        return 'border-amber-500/30 bg-amber-500/10 text-amber-700';
    }

    if (['failed', 'reverted', 'cancelled', 'expired'].includes(value)) {
        return 'border-red-500/30 bg-red-500/10 text-red-700';
    }

    return 'border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-muted)]';
};

const paymentStatusLabel = (status) => {
    const map = {
        pending: 'Pendiente',
        qr_generated: 'QR generado',
        paid: 'Pagado',
        failed: 'Fallido',
        reverted: 'Revertido',
        cancelled: 'Anulado',
        expired: 'Expirado',
    };

    return map[status] || 'Sin pago';
};

const paymentMethodLabel = (method) => {
    const map = {
        cash: 'Efectivo',
        qr_pagofacil: 'QR PagoFácil',
        card: 'Tarjeta',
        transfer: 'Transferencia',
    };

    return map[method] || method || 'No definido';
};

const qrImage = computed(() => {
    if (!selectedPayment.value?.qr_base64) {
        return null;
    }

    return `data:image/png;base64,${selectedPayment.value.qr_base64}`;
});

const canGenerateQr = (sale) => {
    const saleStatus = String(sale?.status || '').toLowerCase();
    const payStatus = paymentStatus(sale);

    return saleStatus === 'entregado'
        && payStatus !== 'paid'
        && (!payment(sale) || ['pending', 'failed', 'expired'].includes(payStatus));
};

const canSeeQr = (sale) => {
    return Boolean(payment(sale)?.qr_base64);
};

const canCheckPayment = (sale) => {
    const currentPayment = payment(sale);

    return Boolean(currentPayment)
        && currentPayment.status !== 'paid'
        && currentPayment.payment_method === 'qr_pagofacil';
};

const canRegisterManual = (sale) => {
    return String(sale?.status || '').toLowerCase() === 'entregado'
        && paymentStatus(sale) !== 'paid';
};

const applyFilters = () => {
    router.get(route('payments.index'), filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    filterForm.date_from = '';
    filterForm.date_to = '';

    applyFilters();
};

const generateQr = (sale) => {
    router.post(route('payments.generate-qr', sale.id), {}, {
        preserveScroll: true,
    });
};

const checkStatus = (currentPayment) => {
    router.post(route('payments.check-status', currentPayment.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeQrModal();
        },
    });
};

const openQrModal = (sale) => {
    selectedSale.value = sale;
    selectedPayment.value = payment(sale);
    showQrModal.value = true;
};

const closeQrModal = () => {
    selectedSale.value = null;
    selectedPayment.value = null;
    showQrModal.value = false;
};

const openManualModal = (sale) => {
    selectedSale.value = sale;

    manualForm.reset();
    manualForm.payment_method = 'cash';
    manualForm.amount_received = sale?.total_price || '';
    manualForm.transaction_id = '';

    showManualModal.value = true;
};

const closeManualModal = () => {
    selectedSale.value = null;
    showManualModal.value = false;
    manualForm.clearErrors();
};

const submitManualPayment = () => {
    if (!selectedSale.value) return;

    manualForm.post(route('payments.manual', selectedSale.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeManualModal();
        },
    });
};

watch(
    () => props.sales,
    () => {
        if (selectedSale.value) {
            const freshSale = rows.value.find((sale) => sale.id === selectedSale.value.id);

            if (freshSale) {
                selectedSale.value = freshSale;
                selectedPayment.value = payment(freshSale);
            }
        }
    }
);
</script>

<template>
    <Head title="Gestión de pagos" />

    <SidebarLayout
        title="Gestión de pagos"
        subtitle="Cobros de ventas entregadas, QR PagoFácil y pagos manuales."
    >
        <div class="space-y-6">
            <div
                v-if="flashSuccess || flashError || flashInfo"
                class="grid gap-3"
            >
                <div
                    v-if="flashSuccess"
                    class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-sm font-bold text-emerald-700"
                >
                    {{ flashSuccess }}
                </div>

                <div
                    v-if="flashError"
                    class="rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-700"
                >
                    {{ flashError }}
                </div>

                <div
                    v-if="flashInfo"
                    class="rounded-2xl border border-blue-500/30 bg-blue-500/10 px-5 py-4 text-sm font-bold text-blue-700"
                >
                    {{ flashInfo }}
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Pendiente por cobrar
                            </p>

                            <p class="mt-3 text-2xl font-black text-[var(--app-text)]">
                                {{ money(summary.pending_total) }}
                            </p>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 8v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </div>
                    </div>

                    <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                        {{ summary.pending_count || 0 }} ventas entregadas
                    </p>
                </article>

                <article class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Pagado hoy
                            </p>

                            <p class="mt-3 text-2xl font-black text-[var(--app-text)]">
                                {{ money(summary.paid_today_total) }}
                            </p>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                        {{ summary.paid_today_count || 0 }} pagos confirmados
                    </p>
                </article>

                <article class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                QR activos
                            </p>

                            <p class="mt-3 text-2xl font-black text-[var(--app-text)]">
                                {{ summary.qr_active_count || 0 }}
                            </p>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-600">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z" />
                                <path d="M14 14h2v2h-2zM18 14h2v6h-4v-2h2zM14 18h2v2h-2z" />
                            </svg>
                        </div>
                    </div>

                    <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                        En espera de confirmación
                    </p>
                </article>

                <article class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Pagos totales
                            </p>

                            <p class="mt-3 text-2xl font-black text-[var(--app-text)]">
                                {{ summary.paid_count || 0 }}
                            </p>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--app-primary)]/10 text-[var(--app-primary)]">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M9 11l3 3L22 4" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                        Historial confirmado
                    </p>
                </article>
            </section>

            <section class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <div class="grid gap-3 xl:grid-cols-[1.5fr_220px_180px_180px_auto_auto]">
                    <input
                        v-model="filterForm.search"
                        type="text"
                        placeholder="Buscar por venta, cliente, CI, teléfono o transacción"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] shadow-sm outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]"
                        @keyup.enter="applyFilters"
                    />

                    <select
                        v-model="filterForm.status"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] shadow-sm outline-none transition focus:border-[var(--app-primary)]"
                    >
                        <option value="">Todos los estados</option>
                        <option value="delivered">Ventas entregadas</option>
                        <option value="without_payment">Sin pago creado</option>
                        <option value="pending">Pago pendiente</option>
                        <option value="qr_generated">QR generado</option>
                        <option value="paid">Pago confirmado</option>
                        <option value="failed">Fallido</option>
                        <option value="expired">Expirado</option>
                        <option value="paid_sale">Ventas pagadas</option>
                    </select>

                    <input
                        v-model="filterForm.date_from"
                        type="date"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] shadow-sm outline-none transition focus:border-[var(--app-primary)]"
                    />

                    <input
                        v-model="filterForm.date_to"
                        type="date"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] shadow-sm outline-none transition focus:border-[var(--app-primary)]"
                    />

                    <button
                        type="button"
                        class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90"
                        @click="applyFilters"
                    >
                        Filtrar
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                        @click="resetFilters"
                    >
                        Limpiar
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)]">
                        <thead class="bg-[var(--app-surface-soft)]">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Venta
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Cliente
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Total
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Estado venta
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Estado pago
                                </th>
                                <th class="px-5 py-4 text-right text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--app-border)]">
                            <tr
                                v-for="sale in rows"
                                :key="sale.id"
                                class="transition hover:bg-[var(--app-surface-soft)]/70"
                            >
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-black text-[var(--app-text)]">
                                        Venta #{{ sale.id }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        {{ formatDate(sale.date) }} · {{ sale.hour }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-primary)]">
                                        {{ sale.order_type || 'Sin tipo' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4">
                                    <p class="text-sm font-black text-[var(--app-text)]">
                                        {{ clientName(sale) }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        {{ clientEmail(sale) }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        Tel: {{ clientPhone(sale) }}
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-base font-black text-[var(--app-text)]">
                                        {{ money(sale.total_price) }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        {{ sale.details?.length || 0 }} ítems
                                    </p>
                                </td>

                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full border px-3 py-1 text-xs font-black',
                                            saleStatusClass(sale.status)
                                        ]"
                                    >
                                        {{ sale.status }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full border px-3 py-1 text-xs font-black',
                                                paymentStatusClass(paymentStatus(sale))
                                            ]"
                                        >
                                            {{ payment(sale)?.status_label || paymentStatusLabel(paymentStatus(sale)) }}
                                        </span>

                                        <p
                                            v-if="payment(sale)?.payment_method"
                                            class="text-xs font-semibold text-[var(--app-muted)]"
                                        >
                                            {{ payment(sale)?.method_label || paymentMethodLabel(payment(sale)?.payment_method) }}
                                        </p>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button
                                            v-if="canGenerateQr(sale)"
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-[var(--app-primary)] px-3 py-2 text-xs font-black text-white transition hover:opacity-90"
                                            @click="generateQr(sale)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z" />
                                                <path d="M14 14h2v2h-2zM18 14h2v6h-4v-2h2z" />
                                            </svg>
                                            Generar QR
                                        </button>

                                        <button
                                            v-if="canSeeQr(sale)"
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-3 py-2 text-xs font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                                            @click="openQrModal(sale)"
                                        >
                                            Ver QR
                                        </button>

                                        <button
                                            v-if="canCheckPayment(sale)"
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-blue-500/30 bg-blue-500/10 px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-500/20"
                                            @click="checkStatus(payment(sale))"
                                        >
                                            Consultar
                                        </button>

                                        <button
                                            v-if="canRegisterManual(sale)"
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-3 py-2 text-xs font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                                            @click="openManualModal(sale)"
                                        >
                                            Manual
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-[var(--app-surface-soft)] text-[var(--app-muted)]">
                                            <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="3.5" y="6" width="17" height="12" rx="2" />
                                                <path d="M3.5 10h17" />
                                                <path d="M7 15h4" />
                                            </svg>
                                        </div>

                                        <p class="mt-4 text-base font-black text-[var(--app-text)]">
                                            No hay pagos para mostrar
                                        </p>

                                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                            Cuando una venta pase a Entregado aparecerá en esta sección.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="sales.links?.length"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--app-border)] px-5 py-4"
                >
                    <p class="text-sm font-semibold text-[var(--app-muted)]">
                        Mostrando {{ sales.from || 0 }} - {{ sales.to || 0 }} de {{ sales.total || 0 }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in sales.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            preserve-scroll
                            :class="[
                                'rounded-xl border px-3 py-2 text-sm font-black transition',
                                link.active
                                    ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white'
                                    : 'border-[var(--app-border)] bg-[var(--app-card)] text-[var(--app-text)] hover:border-[var(--app-primary)]',
                                !link.url ? 'pointer-events-none opacity-40' : ''
                            ]"
                        />
                    </div>
                </div>
            </section>
        </div>

        <div
            v-if="showQrModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm"
        >
            <div class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-start justify-between border-b border-[var(--app-border)] p-6">
                    <div>
                        <p class="text-xl font-black text-[var(--app-text)]">
                            QR PagoFácil
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Venta #{{ selectedSale?.id }} · {{ money(selectedSale?.total_price) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl p-2 text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                        @click="closeQrModal"
                    >
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="rounded-[1.5rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5 text-center">
                        <img
                            v-if="qrImage"
                            :src="qrImage"
                            alt="QR PagoFácil"
                            class="mx-auto h-72 w-72 rounded-3xl bg-white p-4 shadow-sm"
                        />

                        <div
                            v-else
                            class="mx-auto flex h-72 w-72 items-center justify-center rounded-3xl bg-[var(--app-card)] text-sm font-bold text-[var(--app-muted)]"
                        >
                            QR no disponible
                        </div>

                        <p class="mt-4 text-sm font-black text-[var(--app-text)]">
                            Escanear con la aplicación bancaria
                        </p>

                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                            Expira: {{ formatDateTime(selectedPayment?.expiration_date) }}
                        </p>
                    </div>

                    <div class="mt-5 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                            Transacción
                        </p>

                        <p class="mt-2 break-all text-sm font-bold text-[var(--app-text)]">
                            {{ selectedPayment?.payment_number || 'Sin número' }}
                        </p>

                        <p class="mt-1 break-all text-xs font-semibold text-[var(--app-muted)]">
                            PagoFácil: {{ selectedPayment?.pagofacil_transaction_id || 'Pendiente' }}
                        </p>
                    </div>

                    <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                            @click="closeQrModal"
                        >
                            Cerrar
                        </button>

                        <button
                            v-if="selectedPayment?.status !== 'paid'"
                            type="button"
                            class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90"
                            @click="checkStatus(selectedPayment)"
                        >
                            Consultar pago
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showManualModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-start justify-between border-b border-[var(--app-border)] p-6">
                    <div>
                        <p class="text-xl font-black text-[var(--app-text)]">
                            Registrar pago manual
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Venta #{{ selectedSale?.id }} · Total {{ money(selectedSale?.total_price) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl p-2 text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                        @click="closeManualModal"
                    >
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <form class="space-y-4 p-6" @submit.prevent="submitManualPayment">
                    <div>
                        <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                            Método de pago
                        </label>

                        <select
                            v-model="manualForm.payment_method"
                            class="w-full rounded-2xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition focus:border-[var(--app-primary)]"
                        >
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                        </select>

                        <p v-if="manualForm.errors.payment_method" class="mt-1 text-xs font-bold text-red-600">
                            {{ manualForm.errors.payment_method }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                            Monto recibido
                        </label>

                        <input
                            v-model="manualForm.amount_received"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-2xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition focus:border-[var(--app-primary)]"
                        />

                        <p v-if="manualForm.errors.amount_received" class="mt-1 text-xs font-bold text-red-600">
                            {{ manualForm.errors.amount_received }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                            Código o referencia
                        </label>

                        <input
                            v-model="manualForm.transaction_id"
                            type="text"
                            placeholder="Opcional"
                            class="w-full rounded-2xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]"
                        />

                        <p v-if="manualForm.errors.transaction_id" class="mt-1 text-xs font-bold text-red-600">
                            {{ manualForm.errors.transaction_id }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                            @click="closeManualModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90 disabled:opacity-60"
                            :disabled="manualForm.processing"
                        >
                            Guardar pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SidebarLayout>
</template>
