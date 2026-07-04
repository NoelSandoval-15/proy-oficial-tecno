<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    sales: {
        type: Object,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const selectedSale = ref(null);
const selectedPayment = ref(null);
const showQrModal = ref(false);

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

const payment = (sale) => {
    return sale?.latest_payment || sale?.active_payment || sale?.paid_payment || null;
};

const paymentStatus = (sale) => {
    return payment(sale)?.status || null;
};

const paymentStatusLabel = (status) => {
    const map = {
        pending: 'Pendiente',
        qr_generated: 'Esperando pago',
        paid: 'Pagado',
        failed: 'Fallido',
        reverted: 'Revertido',
        cancelled: 'Anulado',
        expired: 'Expirado',
    };

    return map[status] || 'Pendiente';
};

const paymentStatusClass = (status, saleStatus) => {
    const value = String(status || '').toLowerCase();
    const order = String(saleStatus || '').toLowerCase();

    if (value === 'paid' || order === 'pagado') {
        return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700';
    }

    if (value === 'qr_generated') {
        return 'border-blue-500/30 bg-blue-500/10 text-blue-700';
    }

    if (['failed', 'reverted', 'cancelled', 'expired'].includes(value)) {
        return 'border-red-500/30 bg-red-500/10 text-red-700';
    }

    return 'border-amber-500/30 bg-amber-500/10 text-amber-700';
};

const qrImage = computed(() => {
    if (!selectedPayment.value?.qr_base64) {
        return null;
    }

    return `data:image/png;base64,${selectedPayment.value.qr_base64}`;
});

const canGenerateQr = (sale) => {
    const status = String(sale?.status || '').toLowerCase();
    const payStatus = paymentStatus(sale);

    return status === 'entregado'
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

const generateQr = (sale) => {
    router.post(route('client.payments.generate-qr', sale.id), {}, {
        preserveScroll: true,
    });
};

const checkStatus = (currentPayment) => {
    router.post(route('client.payments.check-status', currentPayment.id), {}, {
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

const saleItems = (sale) => {
    return sale?.details || [];
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
    <Head title="Mis pagos" />

    <SidebarLayout
        title="Mis pagos"
        subtitle="Consulta tus pedidos entregados y paga con QR."
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

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Pendientes
                            </p>

                            <p class="mt-3 text-3xl font-black text-[var(--app-text)]">
                                {{ summary.pending_count || 0 }}
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
                        Pedidos entregados por pagar
                    </p>
                </article>

                <article class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Total pendiente
                            </p>

                            <p class="mt-3 text-3xl font-black text-[var(--app-text)]">
                                {{ money(summary.pending_total) }}
                            </p>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-600">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3.5" y="6" width="17" height="12" rx="2" />
                                <path d="M3.5 10h17" />
                                <path d="M7 15h4" />
                            </svg>
                        </div>
                    </div>

                    <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                        Disponible para pago QR
                    </p>
                </article>

                <article class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Pagados
                            </p>

                            <p class="mt-3 text-3xl font-black text-[var(--app-text)]">
                                {{ summary.paid_count || 0 }}
                            </p>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                        Historial confirmado
                    </p>
                </article>
            </section>

            <section class="grid gap-4">
                <article
                    v-for="sale in rows"
                    :key="sale.id"
                    class="overflow-hidden rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm"
                >
                    <div class="p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-lg font-black text-[var(--app-text)]">
                                        Venta #{{ sale.id }}
                                    </p>

                                    <span
                                        :class="[
                                            'inline-flex rounded-full border px-3 py-1 text-xs font-black',
                                            paymentStatusClass(paymentStatus(sale), sale.status)
                                        ]"
                                    >
                                        {{ payment(sale)?.status_label || paymentStatusLabel(paymentStatus(sale)) }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-semibold text-[var(--app-muted)]">
                                    <span class="rounded-full bg-[var(--app-surface-soft)] px-3 py-1">
                                        {{ formatDate(sale.date) }}
                                    </span>

                                    <span class="rounded-full bg-[var(--app-surface-soft)] px-3 py-1">
                                        {{ sale.hour }}
                                    </span>

                                    <span class="rounded-full bg-[var(--app-surface-soft)] px-3 py-1">
                                        {{ sale.order_type || 'Pedido' }}
                                    </span>
                                </div>

                                <p class="text-3xl font-black text-[var(--app-text)]">
                                    {{ money(sale.total_price) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                <button
                                    v-if="canGenerateQr(sale)"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90"
                                    @click="generateQr(sale)"
                                >
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z" />
                                        <path d="M14 14h2v2h-2zM18 14h2v6h-4v-2h2z" />
                                    </svg>
                                    Pagar con QR
                                </button>

                                <button
                                    v-if="canSeeQr(sale)"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                                    @click="openQrModal(sale)"
                                >
                                    Ver QR
                                </button>

                                <button
                                    v-if="canCheckPayment(sale)"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-blue-500/30 bg-blue-500/10 px-5 py-3 text-sm font-black text-blue-700 transition hover:bg-blue-500/20"
                                    @click="checkStatus(payment(sale))"
                                >
                                    Actualizar estado
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[var(--app-border)] bg-[var(--app-surface-soft)]/70 p-5">
                        <p class="mb-3 text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                            Detalle del pedido
                        </p>

                        <div class="grid gap-2">
                            <div
                                v-for="item in saleItems(sale)"
                                :key="item.id"
                                class="flex items-center justify-between gap-4 rounded-2xl bg-[var(--app-card)] px-4 py-3"
                            >
                                <div>
                                    <p class="text-sm font-black text-[var(--app-text)]">
                                        {{ item.product?.name || 'Producto' }}
                                    </p>

                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        {{ item.amount }} x {{ money(item.price_sale) }}
                                    </p>
                                </div>

                                <p class="text-sm font-black text-[var(--app-text)]">
                                    {{ money((item.amount || 0) * (item.price_sale || 0)) }}
                                </p>
                            </div>

                            <p
                                v-if="saleItems(sale).length === 0"
                                class="rounded-2xl bg-[var(--app-card)] px-4 py-3 text-sm font-semibold text-[var(--app-muted)]"
                            >
                                No hay detalle registrado para esta venta.
                            </p>
                        </div>
                    </div>
                </article>

                <article
                    v-if="rows.length === 0"
                    class="rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] p-10 text-center shadow-sm"
                >
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[var(--app-surface-soft)] text-[var(--app-muted)]">
                        <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3.5" y="6" width="17" height="12" rx="2" />
                            <path d="M3.5 10h17" />
                            <path d="M7 15h4" />
                        </svg>
                    </div>

                    <p class="mt-4 text-base font-black text-[var(--app-text)]">
                        No tienes pagos pendientes
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Cuando un pedido sea entregado aparecerá aquí para pagarlo.
                    </p>
                </article>
            </section>

            <div
                v-if="sales.links?.length"
                class="flex flex-wrap items-center justify-between gap-3 rounded-[1.7rem] border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-4"
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
        </div>

        <div
            v-if="showQrModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm"
        >
            <div class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-start justify-between border-b border-[var(--app-border)] p-6">
                    <div>
                        <p class="text-xl font-black text-[var(--app-text)]">
                            Pago con QR
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
                            Escanea este QR con tu app bancaria
                        </p>

                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                            Después de pagar, presiona actualizar estado.
                        </p>

                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                            Expira: {{ formatDateTime(selectedPayment?.expiration_date) }}
                        </p>
                    </div>

                    <div class="mt-5 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                            Número de pago
                        </p>

                        <p class="mt-2 break-all text-sm font-bold text-[var(--app-text)]">
                            {{ selectedPayment?.payment_number || 'Sin número' }}
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
                            Actualizar estado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
