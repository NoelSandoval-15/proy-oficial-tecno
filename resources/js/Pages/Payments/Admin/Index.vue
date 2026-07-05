<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
const receiptSale = ref(null);

const showQrModal = ref(false);
const showManualModal = ref(false);
const showReceiptModal = ref(false);
const showQrImageModal = ref(false);

const expandedSaleIds = ref(new Set());
const pollingTimer = ref(null);
const dashboardPollingTimer = ref(null);
const checkingPaymentId = ref(null);
const generatingSaleId = ref(null);

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

const getValue = (object, path, fallback = null) => {
    return path.split('.').reduce((current, key) => {
        if (current === null || current === undefined) {
            return undefined;
        }

        return current[key];
    }, object) ?? fallback;
};

const shortText = (value, length = 30) => {
    if (!value) return 'No registrado';

    const text = String(value);

    if (text.length <= length) {
        return text;
    }

    return `${text.substring(0, length)}...`;
};

const clientName = (sale) => {
    const profile = sale?.users_client?.profile;

    if (profile) {
        const fullName = `${profile.name || ''} ${profile.last_name || ''}`.trim();

        if (fullName) {
            return fullName;
        }
    }

    return sale?.users_client?.name || 'Cliente mostrador';
};

const clientEmail = (sale) => {
    return sale?.users_client?.email || 'Sin correo';
};

const clientPhone = (sale) => {
    return sale?.users_client?.profile?.telephone
        || sale?.users_client?.profile?.phone
        || 'Sin teléfono';
};

const clientDocument = (sale) => {
    return sale?.users_client?.profile?.ci
        || sale?.users_client?.profile?.document
        || sale?.users_client?.profile?.document_number
        || 'No registrado';
};

const saleIsPaidByStatus = (sale) => {
    return String(sale?.status || '').toLowerCase() === 'pagado';
};

const payment = (sale) => {
    if (!sale) return null;

    if (saleIsPaidByStatus(sale)) {
        return sale?.paid_payment || sale?.latest_payment || sale?.active_payment || null;
    }

    return sale?.active_payment || sale?.latest_payment || sale?.paid_payment || null;
};

const paymentStatus = (sale) => {
    return payment(sale)?.status || null;
};

const isPaidSale = (sale) => {
    return saleIsPaidByStatus(sale) || paymentStatus(sale) === 'paid';
};

const isExpiredPayment = (currentPayment) => {
    if (!currentPayment?.expiration_date || currentPayment?.status === 'paid') {
        return false;
    }

    const expirationDate = new Date(currentPayment.expiration_date);

    if (Number.isNaN(expirationDate.getTime())) {
        return false;
    }

    return expirationDate.getTime() < Date.now();
};

const isFinalUnpaidPayment = (currentPayment) => {
    return ['failed', 'reverted', 'cancelled', 'expired'].includes(String(currentPayment?.status || '').toLowerCase());
};

const paymentStatusLabel = (status, sale = null) => {
    if (isPaidSale(sale)) return 'Pagado';

    const map = {
        pending: 'Pendiente',
        qr_generated: 'QR activo',
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
    };

    return map[method] || 'No definido';
};

const saleState = (sale) => {
    const currentPayment = payment(sale);
    const status = String(currentPayment?.status || '').toLowerCase();

    if (isPaidSale(sale)) {
        return {
            label: 'Pagado',
            title: 'Pago confirmado',
            description: 'Venta cerrada. La nota de venta está disponible.',
            badge: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700',
            card: 'border-emerald-500/25',
            dot: 'bg-emerald-500',
            panel: 'bg-emerald-500/10 border-emerald-500/20',
        };
    }

    if (status === 'qr_generated' && !isExpiredPayment(currentPayment)) {
        return {
            label: 'QR activo',
            title: 'Esperando pago QR',
            description: 'El sistema verificará este pago automáticamente.',
            badge: 'border-blue-500/30 bg-blue-500/10 text-blue-700',
            card: 'border-blue-500/25',
            dot: 'bg-blue-500',
            panel: 'bg-blue-500/10 border-blue-500/20',
        };
    }

    if (['failed', 'reverted', 'cancelled', 'expired'].includes(status) || isExpiredPayment(currentPayment)) {
        return {
            label: 'Por revisar',
            title: 'Pago no confirmado',
            description: 'Puedes generar otro QR o cobrar en efectivo.',
            badge: 'border-red-500/30 bg-red-500/10 text-red-700',
            card: 'border-red-500/25',
            dot: 'bg-red-500',
            panel: 'bg-red-500/10 border-red-500/20',
        };
    }

    if (String(sale?.status || '').toLowerCase() === 'entregado') {
        return {
            label: 'Por cobrar',
            title: 'Venta entregada',
            description: 'Lista para cobrar con QR o efectivo.',
            badge: 'border-amber-500/30 bg-amber-500/10 text-amber-700',
            card: 'border-amber-500/25',
            dot: 'bg-amber-500',
            panel: 'bg-amber-500/10 border-amber-500/20',
        };
    }

    return {
        label: sale?.status || 'Sin estado',
        title: 'Venta registrada',
        description: 'Revisa el estado de la venta antes de cobrar.',
        badge: 'border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-muted)]',
        card: 'border-[var(--app-border)]',
        dot: 'bg-[var(--app-muted)]',
        panel: 'bg-[var(--app-surface-soft)] border-[var(--app-border)]',
    };
};

const deliveredRows = computed(() => {
    return rows.value.filter((sale) => {
        return String(sale?.status || '').toLowerCase() === 'entregado' && !isPaidSale(sale);
    });
});

const paidRows = computed(() => {
    return rows.value.filter((sale) => isPaidSale(sale));
});

const qrRows = computed(() => {
    return rows.value.filter((sale) => {
        const currentPayment = payment(sale);

        return !isPaidSale(sale)
            && currentPayment?.status === 'qr_generated'
            && currentPayment?.qr_base64
            && !isExpiredPayment(currentPayment);
    });
});

const issueRows = computed(() => {
    return rows.value.filter((sale) => {
        const currentPayment = payment(sale);

        return !isPaidSale(sale)
            && (isFinalUnpaidPayment(currentPayment) || isExpiredPayment(currentPayment));
    });
});

const quickFilters = computed(() => [
    {
        label: 'Todos',
        value: '',
        count: rows.value.length,
    },
    {
        label: 'Por cobrar',
        value: 'delivered',
        count: deliveredRows.value.length,
    },
    {
        label: 'QR activos',
        value: 'qr_generated',
        count: qrRows.value.length,
    },
    {
        label: 'Pagados',
        value: 'paid',
        count: paidRows.value.length,
    },
    {
        label: 'Por revisar',
        value: 'failed',
        count: issueRows.value.length,
    },
]);

const qrImage = computed(() => {
    if (!selectedPayment.value?.qr_base64) {
        return null;
    }

    return `data:image/png;base64,${selectedPayment.value.qr_base64}`;
});

const saleItems = (sale) => {
    return sale?.details || [];
};

const itemSubtotal = (item) => {
    return Number(item?.subtotal ?? ((item?.amount || 0) * (item?.price_sale || 0)));
};

const toggleDetails = (saleId) => {
    const next = new Set(expandedSaleIds.value);

    if (next.has(saleId)) {
        next.delete(saleId);
    } else {
        next.add(saleId);
    }

    expandedSaleIds.value = next;
};

const isExpanded = (sale) => {
    return expandedSaleIds.value.has(sale?.id);
};

const canGenerateQr = (sale) => {
    const saleStatus = String(sale?.status || '').toLowerCase();
    const currentPayment = payment(sale);
    const payStatus = String(currentPayment?.status || '').toLowerCase();

    return !isPaidSale(sale)
        && saleStatus === 'entregado'
        && (!currentPayment || ['pending', 'failed', 'expired', 'cancelled'].includes(payStatus) || isExpiredPayment(currentPayment));
};

const canSeeQr = (sale) => {
    const currentPayment = payment(sale);

    return !isPaidSale(sale)
        && Boolean(currentPayment?.qr_base64)
        && currentPayment?.status === 'qr_generated'
        && !isExpiredPayment(currentPayment);
};

const canRegisterManual = (sale) => {
    return !isPaidSale(sale)
        && String(sale?.status || '').toLowerCase() === 'entregado';
};

const canSeeReceipt = (sale) => {
    return isPaidSale(sale);
};

const applyFilters = () => {
    router.get(route('payments.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
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

const setQuickFilter = (value) => {
    filterForm.status = value;
    applyFilters();
};

const generateQr = (sale) => {
    generatingSaleId.value = sale.id;

    router.post(route('payments.generate-qr', sale.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            generatingSaleId.value = null;
        },
    });
};

const stopPaymentPolling = () => {
    if (pollingTimer.value) {
        clearInterval(pollingTimer.value);
    }

    pollingTimer.value = null;
};

const stopDashboardPolling = () => {
    if (dashboardPollingTimer.value) {
        clearInterval(dashboardPollingTimer.value);
    }

    dashboardPollingTimer.value = null;
};

const checkStatus = (currentPayment, silent = true) => {
    if (!currentPayment || currentPayment.status === 'paid' || checkingPaymentId.value === currentPayment.id) {
        return;
    }

    checkingPaymentId.value = currentPayment.id;

    router.post(route('payments.check-status', currentPayment.id), {
        silent,
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            checkingPaymentId.value = null;
        },
    });
};

const autoCheckActiveQr = () => {
    if (showManualModal.value || showReceiptModal.value || checkingPaymentId.value) {
        return;
    }

    const saleWithQr = qrRows.value.find((sale) => {
        const currentPayment = payment(sale);

        return currentPayment
            && currentPayment.id
            && currentPayment.status === 'qr_generated'
            && !isExpiredPayment(currentPayment)
            && !isPaidSale(sale);
    });

    if (!saleWithQr) {
        return;
    }

    checkStatus(payment(saleWithQr), true);
};

const startDashboardPolling = () => {
    stopDashboardPolling();

    dashboardPollingTimer.value = setInterval(() => {
        autoCheckActiveQr();
    }, 8000);
};

const startPaymentPolling = (sale) => {
    const currentPayment = payment(sale);

    if (!currentPayment || currentPayment.status === 'paid' || isFinalUnpaidPayment(currentPayment)) {
        return;
    }

    stopPaymentPolling();

    pollingTimer.value = setInterval(() => {
        if (!selectedPayment.value || selectedPayment.value.status === 'paid') {
            stopPaymentPolling();
            return;
        }

        checkStatus(selectedPayment.value, true);
    }, 6000);
};

const openQrModal = (sale) => {
    selectedSale.value = sale;
    selectedPayment.value = payment(sale);
    showQrModal.value = true;

    startPaymentPolling(sale);
};

const closeQrModal = () => {
    stopPaymentPolling();

    selectedSale.value = null;
    selectedPayment.value = null;
    showQrModal.value = false;
    showQrImageModal.value = false;
};

const openQrImageModal = () => {
    if (!qrImage.value) {
        return;
    }

    showQrImageModal.value = true;
};

const closeQrImageModal = () => {
    showQrImageModal.value = false;
};

const openManualModal = (sale) => {
    selectedSale.value = sale;

    manualForm.reset();
    manualForm.clearErrors();

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

    manualForm.payment_method = 'cash';

    manualForm.post(route('payments.manual', selectedSale.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeManualModal();
        },
    });
};

const openReceiptModal = (sale) => {
    receiptSale.value = sale;
    showReceiptModal.value = true;
};

const closeReceiptModal = () => {
    receiptSale.value = null;
    showReceiptModal.value = false;
};

const receiptPayment = computed(() => {
    return receiptSale.value ? payment(receiptSale.value) : null;
});

const receiptItems = computed(() => {
    return receiptSale.value?.details || [];
});

const receiptSubtotal = computed(() => {
    return receiptItems.value.reduce((total, item) => total + itemSubtotal(item), 0);
});

const receiptPayerName = computed(() => {
    const currentPayment = receiptPayment.value;

    return getValue(currentPayment, 'query_payload.values.payerName')
        || getValue(currentPayment, 'callback_payload.payerName')
        || getValue(currentPayment, 'callback_payload.Nombre')
        || clientName(receiptSale.value);
});

const receiptPayerDocument = computed(() => {
    const currentPayment = receiptPayment.value;

    return getValue(currentPayment, 'query_payload.values.payerDocument')
        || getValue(currentPayment, 'callback_payload.payerDocument')
        || getValue(currentPayment, 'callback_payload.Documento')
        || clientDocument(receiptSale.value);
});

const receiptTransaction = computed(() => {
    const currentPayment = receiptPayment.value;

    return currentPayment?.payment_number
        || currentPayment?.transaction_id
        || currentPayment?.pagofacil_transaction_id
        || 'Confirmado';
});

const receiptPaymentDate = computed(() => {
    const currentPayment = receiptPayment.value;

    return currentPayment?.paid_at
        || currentPayment?.payment_date
        || getValue(currentPayment, 'query_payload.values.paymentDate')
        || receiptSale.value?.date;
});

const printReceipt = () => {
    window.print();
};

watch(
    () => props.sales,
    () => {
        if (selectedSale.value) {
            const freshSale = rows.value.find((sale) => sale.id === selectedSale.value.id);

            if (freshSale) {
                selectedSale.value = freshSale;
                selectedPayment.value = payment(freshSale);

                if (isPaidSale(freshSale)) {
                    stopPaymentPolling();
                    showQrModal.value = false;
                    openReceiptModal(freshSale);
                }
            }
        }

        if (receiptSale.value) {
            const freshReceiptSale = rows.value.find((sale) => sale.id === receiptSale.value.id);

            if (freshReceiptSale) {
                receiptSale.value = freshReceiptSale;
            }
        }
    }
);

onMounted(() => {
    startDashboardPolling();
});

onBeforeUnmount(() => {
    stopPaymentPolling();
    stopDashboardPolling();
});
</script>

<template>

    <Head title="Gestión de pagos" />

    <SidebarLayout title="Gestión de pagos"
        subtitle="Panel de caja para cobrar con QR, efectivo y emitir notas de venta.">
        <div class="admin-payments-page space-y-6 pt-24 lg:pt-10 print:pt-0">
            <div v-if="flashSuccess || flashError || flashInfo" class="grid gap-3 print:hidden">
                <div v-if="flashSuccess"
                    class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-sm font-bold text-emerald-700">
                    {{ flashSuccess }}
                </div>

                <div v-if="flashError"
                    class="rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-700">
                    {{ flashError }}
                </div>

                <div v-if="flashInfo"
                    class="rounded-2xl border border-blue-500/30 bg-blue-500/10 px-5 py-4 text-sm font-bold text-blue-700">
                    {{ flashInfo }}
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 print:hidden">
                <article class="rounded-[1.4rem] border border-amber-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Por cobrar
                            </p>

                            <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ money(summary.pending_total) }}
                            </p>

                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                {{ summary.pending_count || 0 }} ventas entregadas
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-700">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M12 8v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.4rem] border border-emerald-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Pagado hoy
                            </p>

                            <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ money(summary.paid_today_total) }}
                            </p>

                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                {{ summary.paid_today_count || 0 }} pagos confirmados
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-700">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.4rem] border border-blue-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                QR activos
                            </p>

                            <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ summary.qr_active_count || qrRows.length || 0 }}
                            </p>

                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                Verificación automática
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-700">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z" />
                                <path d="M14 14h2v2h-2zM18 14h2v6h-4v-2h2zM14 18h2v2h-2z" />
                            </svg>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Pagos totales
                            </p>

                            <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ summary.paid_count || 0 }}
                            </p>

                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                Historial confirmado
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--app-primary)]/10 text-[var(--app-primary)]">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 0 1 2-2z" />
                                <path d="M9 8h6M9 12h6M9 16h4" stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-4 shadow-sm print:hidden">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex flex-wrap gap-2">
                        <button v-for="filter in quickFilters" :key="filter.value || 'all'" type="button" :class="[
                            'rounded-xl border px-4 py-2 text-sm font-black transition',
                            filterForm.status === filter.value
                                ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white'
                                : 'border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] hover:border-[var(--app-primary)]'
                        ]" @click="setQuickFilter(filter.value)">
                            {{ filter.label }}
                            <span class="ml-2 text-xs">
                                {{ filter.count }}
                            </span>
                        </button>
                    </div>

                    <button type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-4 py-2 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                        @click="router.reload({ preserveScroll: true })">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Actualizar
                    </button>
                </div>

                <div class="mt-4 grid gap-3 xl:grid-cols-[1.4fr_190px_170px_170px_auto_auto]">
                    <input v-model="filterForm.search" type="text"
                        placeholder="Buscar venta, cliente, CI, teléfono o transacción"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]"
                        @keyup.enter="applyFilters" />

                    <select v-model="filterForm.status"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition focus:border-[var(--app-primary)]">
                        <option value="">Todos</option>
                        <option value="delivered">Ventas entregadas</option>
                        <option value="without_payment">Sin pago creado</option>
                        <option value="pending">Pago pendiente</option>
                        <option value="qr_generated">QR activo</option>
                        <option value="paid">Pago confirmado</option>
                        <option value="failed">Fallido</option>
                        <option value="expired">Expirado</option>
                        <option value="paid_sale">Ventas pagadas</option>
                    </select>

                    <input v-model="filterForm.date_from" type="date"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition focus:border-[var(--app-primary)]" />

                    <input v-model="filterForm.date_to" type="date"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition focus:border-[var(--app-primary)]" />

                    <button type="button"
                        class="rounded-xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90"
                        @click="applyFilters">
                        Filtrar
                    </button>

                    <button type="button"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                        @click="resetFilters">
                        Limpiar
                    </button>
                </div>
            </section>

            <section class="grid gap-3 print:hidden">
                <article v-for="sale in rows" :key="sale.id" :class="[
                    'rounded-[1.4rem] border bg-[var(--app-card)] p-5 shadow-sm',
                    saleState(sale).card
                ]">
                    <div class="grid gap-5 xl:grid-cols-[1fr_230px] xl:items-start">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span :class="[
                                    'h-2.5 w-2.5 rounded-full',
                                    saleState(sale).dot
                                ]"></span>

                                <p class="text-lg font-black text-[var(--app-text)]">
                                    Venta #{{ sale.id }}
                                </p>

                                <span :class="[
                                    'rounded-full border px-3 py-1 text-xs font-black',
                                    saleState(sale).badge
                                ]">
                                    {{ saleState(sale).label }}
                                </span>

                                <span
                                    class="rounded-full bg-[var(--app-surface-soft)] px-3 py-1 text-xs font-black text-[var(--app-muted)]">
                                    {{ sale.order_type || 'Pedido' }}
                                </span>
                            </div>

                            <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                                {{ saleState(sale).description }}
                            </p>

                            <div class="mt-4 grid gap-3 md:grid-cols-4">
                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Cliente
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ clientName(sale) }}
                                    </p>

                                    <p class="mt-1 truncate text-xs font-semibold text-[var(--app-muted)]">
                                        {{ clientEmail(sale) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Contacto
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ clientPhone(sale) }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        CI: {{ clientDocument(sale) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Fecha
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ formatDate(sale.date) }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        {{ sale.hour || 'Sin hora' }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Pago
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ payment(sale)?.method_label ||
                                            paymentMethodLabel(payment(sale)?.payment_method) }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        {{ payment(sale)?.status_label || paymentStatusLabel(paymentStatus(sale), sale)
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="isExpanded(sale)"
                                class="mt-4 overflow-hidden rounded-xl border border-[var(--app-border)]">
                                <table class="min-w-full divide-y divide-[var(--app-border)]">
                                    <tbody class="divide-y divide-[var(--app-border)]">
                                        <tr v-for="item in saleItems(sale)" :key="item.id" class="bg-[var(--app-card)]">
                                            <td class="px-4 py-3">
                                                <p class="text-sm font-black text-[var(--app-text)]">
                                                    {{ item.product?.name || 'Producto' }}
                                                </p>

                                                <p class="text-xs font-semibold text-[var(--app-muted)]">
                                                    {{ item.amount }} x {{ money(item.price_sale) }}
                                                </p>
                                            </td>

                                            <td class="px-4 py-3 text-right text-sm font-black text-[var(--app-text)]">
                                                {{ money(itemSubtotal(item)) }}
                                            </td>
                                        </tr>

                                        <tr v-if="saleItems(sale).length === 0">
                                            <td class="px-4 py-3 text-sm font-semibold text-[var(--app-muted)]">
                                                No hay detalle registrado.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <div :class="[
                                'rounded-[1.2rem] border p-4',
                                saleState(sale).panel
                            ]">
                                <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                    Total venta
                                </p>

                                <p class="mt-1 text-3xl font-black text-[var(--app-text)]">
                                    {{ money(sale.total_price) }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                    {{ saleItems(sale).length }} producto(s)
                                </p>
                            </div>

                            <div class="mt-3">
                                <div
                                    class="flex flex-wrap items-center gap-2 rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] p-2">
                                    <button v-if="canGenerateQr(sale)" type="button" title="Generar QR"
                                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--app-primary)] text-white transition hover:opacity-90 disabled:opacity-60"
                                        :disabled="generatingSaleId === sale.id" @click="generateQr(sale)">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z" />
                                            <path d="M14 14h2v2h-2zM18 14h2v6h-4v-2h2zM14 18h2v2h-2z" />
                                        </svg>
                                    </button>

                                    <button v-if="canSeeQr(sale)" type="button" title="Ver QR activo"
                                        class="flex h-11 w-11 items-center justify-center rounded-xl border border-blue-500/30 bg-blue-500/10 text-blue-700 transition hover:bg-blue-500/20"
                                        @click="openQrModal(sale)">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z" />
                                            <path d="M14 14h2v2h-2zM18 14h2v6h-4v-2h2zM14 18h2v2h-2z" />
                                        </svg>
                                    </button>

                                    <button v-if="canRegisterManual(sale)" type="button" title="Registrar efectivo"
                                        class="flex h-11 w-11 items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-700 transition hover:bg-amber-500/20"
                                        @click="openManualModal(sale)">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
                                            <rect x="3" y="6" width="18" height="12" rx="2" />
                                            <path d="M7 10h.01M17 14h.01" stroke-linecap="round" />
                                            <circle cx="12" cy="12" r="2.5" />
                                        </svg>
                                    </button>

                                    <button v-if="canSeeReceipt(sale)" type="button" title="Ver nota de venta"
                                        class="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-700 transition hover:bg-emerald-500/20"
                                        @click="openReceiptModal(sale)">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path
                                                d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 0 1 2-2z" />
                                            <path d="M9 8h6M9 12h6M9 16h4" stroke-linecap="round" />
                                        </svg>
                                    </button>

                                    <button type="button" title="Ver detalle"
                                        class="flex h-11 w-11 items-center justify-center rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                                        @click="toggleDetails(sale.id)">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </div>

                                <p v-if="canSeeQr(sale)"
                                    class="mt-2 rounded-xl bg-blue-500/10 px-3 py-2 text-xs font-bold text-blue-700">
                                    Verificación automática activa cada 8 segundos.
                                </p>

                                <p v-if="canGenerateQr(sale)"
                                    class="mt-2 rounded-xl bg-amber-500/10 px-3 py-2 text-xs font-bold text-amber-700">
                                    Pendiente de cobro: QR o efectivo.
                                </p>

                                <p v-if="canSeeReceipt(sale)"
                                    class="mt-2 rounded-xl bg-emerald-500/10 px-3 py-2 text-xs font-bold text-emerald-700">
                                    Venta pagada. Nota disponible.
                                </p>
                            </div>
                        </div>
                    </div>
                </article>

                <article v-if="rows.length === 0"
                    class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-10 text-center shadow-sm">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--app-surface-soft)] text-xl font-black text-[var(--app-muted)]">
                        0
                    </div>

                    <p class="mt-4 text-base font-black text-[var(--app-text)]">
                        No hay pagos para mostrar
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Cuando una venta pase a entregada aparecerá en esta sección.
                    </p>
                </article>
            </section>

            <div v-if="sales.links?.length"
                class="flex flex-wrap items-center justify-between gap-3 rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-4 print:hidden">
                <p class="text-sm font-semibold text-[var(--app-muted)]">
                    Mostrando {{ sales.from || 0 }} - {{ sales.to || 0 }} de {{ sales.total || 0 }}
                </p>

                <div class="flex flex-wrap gap-2">
                    <Link v-for="link in sales.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                        preserve-scroll :class="[
                            'rounded-xl border px-3 py-2 text-sm font-black transition',
                            link.active
                                ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white'
                                : 'border-[var(--app-border)] bg-[var(--app-card)] text-[var(--app-text)] hover:border-[var(--app-primary)]',
                            !link.url ? 'pointer-events-none opacity-40' : ''
                        ]" />
                </div>
            </div>
        </div>

        <div v-if="showQrModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-3 py-4 backdrop-blur-sm print:hidden">
            <div
                class="flex max-h-[92vh] w-[min(94vw,900px)] flex-col overflow-hidden rounded-[1.5rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-start justify-between border-b border-[var(--app-border)] px-5 py-4">
                    <div>
                        <p class="text-xl font-black text-[var(--app-text)]">
                            QR PagoFácil
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Venta #{{ selectedSale?.id }} · {{ money(selectedSale?.total_price) }}
                        </p>
                    </div>

                    <button type="button"
                        class="rounded-xl px-3 py-2 text-xl font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                        @click="closeQrModal">
                        ×
                    </button>
                </div>

                <div class="overflow-y-auto p-5">
                    <div class="grid gap-5 lg:grid-cols-[330px_1fr]">
                        <div
                            class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5 text-center">
                            <button v-if="qrImage" type="button"
                                class="mx-auto block rounded-2xl bg-white p-2 shadow-sm transition hover:scale-[1.02] focus:outline-none focus:ring-4 focus:ring-blue-500/20"
                                title="Ampliar QR" @click="openQrImageModal">
                                <img :src="qrImage" alt="QR PagoFácil"
                                    class="h-64 w-64 rounded-xl bg-white p-2 sm:h-72 sm:w-72" />

                                <span class="mt-2 block text-xs font-black text-blue-700">
                                    Toca para ampliar
                                </span>
                            </button>
                            <div v-if="showQrImageModal"
                                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 px-3 py-4 backdrop-blur-md print:hidden"
                                @click="closeQrImageModal">
                                <div class="relative flex max-h-[94vh] w-[min(96vw,720px)] flex-col items-center rounded-[1.5rem] bg-white p-4 shadow-2xl"
                                    @click.stop>
                                    <button type="button"
                                        class="absolute right-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-xl font-black text-white shadow-lg transition hover:bg-slate-700"
                                        @click="closeQrImageModal">
                                        ×
                                    </button>

                                    <div
                                        class="w-full rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 text-center">
                                        <p class="text-sm font-black text-slate-900">
                                            QR PagoFácil ampliado
                                        </p>

                                        <p class="mt-1 text-xs font-semibold text-slate-500">
                                            Venta #{{ selectedSale?.id }} · {{ money(selectedSale?.total_price) }}
                                        </p>
                                    </div>

                                    <div
                                        class="mt-4 flex max-h-[72vh] w-full items-center justify-center overflow-auto rounded-[1.2rem] bg-white p-3">
                                        <img :src="qrImage" alt="QR PagoFácil ampliado"
                                            class="h-auto max-h-[68vh] w-auto max-w-full rounded-xl bg-white object-contain" />
                                    </div>

                                    <p class="mt-3 text-center text-xs font-semibold text-slate-500">
                                        Escanea este QR desde la app bancaria del cliente.
                                    </p>
                                </div>
                            </div>
                            <!-- <div v-else
                                class="mx-auto flex h-64 w-64 items-center justify-center rounded-2xl bg-[var(--app-card)] text-sm font-bold text-[var(--app-muted)] sm:h-72 sm:w-72">
                                QR no disponible
                            </div> -->

                            <p class="mt-4 text-sm font-black text-[var(--app-text)]">
                                QR listo para que el cliente pague
                            </p>

                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                Expira: {{ formatDateTime(selectedPayment?.expiration_date) }}
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-[1.2rem] border border-blue-500/30 bg-blue-500/10 p-5">
                                <div class="flex items-center gap-3">
                                    <span class="h-3 w-3 animate-pulse rounded-full bg-blue-600"></span>

                                    <p class="text-base font-black text-blue-800">
                                        Esperando confirmación automática
                                    </p>
                                </div>

                                <p class="mt-3 text-sm font-semibold leading-6 text-blue-700">
                                    El sistema verificará este pago automáticamente. Cuando PagoFácil confirme,
                                    se abrirá la nota de venta.
                                </p>
                            </div>

                            <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5">
                                <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Cliente
                                </p>

                                <p class="mt-2 text-sm font-black text-[var(--app-text)]">
                                    {{ clientName(selectedSale) }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                    {{ clientPhone(selectedSale) }}
                                </p>
                            </div>

                            <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5">
                                <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Número de pago
                                </p>

                                <p class="mt-2 break-all text-sm font-bold text-[var(--app-text)]">
                                    {{ selectedPayment?.payment_number || 'Sin número' }}
                                </p>

                                <p class="mt-1 break-all text-xs font-semibold text-[var(--app-muted)]">
                                    PagoFácil: {{ selectedPayment?.pagofacil_transaction_id || 'Pendiente' }}
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <button type="button"
                                    class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                                    @click="closeQrModal">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showManualModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-3 py-4 backdrop-blur-sm print:hidden">
            <div
                class="flex max-h-[92vh] w-[min(94vw,540px)] flex-col overflow-hidden rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-start justify-between border-b border-[var(--app-border)] px-5 py-4">
                    <div>
                        <p class="text-xl font-black text-[var(--app-text)]">
                            Registrar pago en efectivo
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Venta #{{ selectedSale?.id }} · {{ money(selectedSale?.total_price) }}
                        </p>
                    </div>

                    <button type="button"
                        class="rounded-xl px-3 py-2 text-xl font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                        @click="closeManualModal">
                        ×
                    </button>
                </div>

                <form class="space-y-4 overflow-y-auto p-5" @submit.prevent="submitManualPayment">
                    <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                            Cliente
                        </p>

                        <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                            {{ clientName(selectedSale) }}
                        </p>

                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                            {{ clientPhone(selectedSale) }}
                        </p>
                    </div>

                    <div class="rounded-[1.2rem] border border-amber-500/30 bg-amber-500/10 p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-amber-700">
                            Método de pago
                        </p>

                        <div class="mt-2 flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-700">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <rect x="3" y="6" width="18" height="12" rx="2" />
                                    <path d="M7 10h.01M17 14h.01" stroke-linecap="round" />
                                    <circle cx="12" cy="12" r="2.5" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-black text-[var(--app-text)]">
                                    Efectivo
                                </p>

                                <p class="text-xs font-semibold text-[var(--app-muted)]">
                                    Registro manual de pago recibido en caja.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                            Monto recibido
                        </label>

                        <input v-model="manualForm.amount_received" type="number" step="0.01" min="0"
                            class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition focus:border-[var(--app-primary)]" />

                        <p v-if="manualForm.errors.amount_received" class="mt-1 text-xs font-bold text-red-600">
                            {{ manualForm.errors.amount_received }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                            Código o referencia
                        </label>

                        <input v-model="manualForm.transaction_id" type="text" placeholder="Opcional"
                            class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]" />

                        <p v-if="manualForm.errors.transaction_id" class="mt-1 text-xs font-bold text-red-600">
                            {{ manualForm.errors.transaction_id }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button type="button"
                            class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                            @click="closeManualModal">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="rounded-xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90 disabled:opacity-60"
                            :disabled="manualForm.processing">
                            {{ manualForm.processing ? 'Guardando...' : 'Confirmar efectivo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showReceiptModal"
            class="receipt-modal fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-2 py-3 backdrop-blur-sm sm:px-4 sm:py-5">
            <div
                class="receipt-shell flex max-h-[92vh] w-[min(96vw,780px)] flex-col overflow-hidden rounded-[1.2rem] bg-white shadow-2xl">
                <div
                    class="screen-only flex shrink-0 items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                    <div>
                        <p class="text-base font-black text-slate-900 sm:text-lg">
                            Nota de venta
                        </p>

                        <p class="text-xs font-semibold text-slate-500">
                            Venta #{{ receiptSale?.id }} · Pago confirmado
                        </p>
                    </div>

                    <button type="button"
                        class="rounded-xl px-3 py-1.5 text-lg font-black text-slate-500 transition hover:bg-slate-200 hover:text-slate-900"
                        @click="closeReceiptModal">
                        ×
                    </button>
                </div>

                <div class="receipt-scroll flex-1 overflow-y-auto bg-white p-4 text-slate-900 sm:p-5">
                    <article class="receipt-print-root mx-auto max-w-[720px] bg-white">
                        <div
                            class="grid gap-4 border-b-2 border-slate-900 pb-4 sm:grid-cols-[1.2fr_0.8fr] sm:items-start">
                            <div>
                                <p class="text-xl font-black uppercase tracking-tight text-slate-950 sm:text-2xl">
                                    Churrasquería Roberto
                                </p>

                                <p class="mt-1 text-xs font-semibold text-slate-600 sm:text-sm">
                                    Restaurante · Parrillada · Atención en mesa
                                </p>

                                <div class="mt-3 grid gap-0.5 text-xs font-semibold text-slate-600">
                                    <p>Santa Cruz, Bolivia</p>
                                    <p>Documento interno de venta</p>
                                    <p>No sustituye factura fiscal</p>
                                </div>
                            </div>

                            <div class="rounded-2xl border-2 border-slate-900 p-4 text-center">
                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-500">
                                    Nota de venta
                                </p>

                                <p class="mt-1 text-3xl font-black text-slate-950 sm:text-4xl">
                                    #{{ receiptSale?.id }}
                                </p>

                                <div
                                    class="mx-auto mt-2 inline-flex rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-black text-white">
                                    PAGADO
                                </div>

                                <p class="mt-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Fecha de emisión
                                </p>

                                <p class="mt-0.5 text-xs font-black text-slate-900">
                                    {{ formatDateTime(receiptPaymentDate) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    Cliente
                                </p>

                                <p class="mt-1 text-xs font-black text-slate-900">
                                    {{ receiptPayerName }}
                                </p>

                                <p class="mt-0.5 text-[11px] font-semibold text-slate-600">
                                    Doc: {{ receiptPayerDocument }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    Venta
                                </p>

                                <p class="mt-1 text-xs font-black text-slate-900">
                                    Venta #{{ receiptSale?.id }}
                                </p>

                                <p class="mt-0.5 text-[11px] font-semibold text-slate-600">
                                    {{ formatDate(receiptSale?.date) }} · {{ receiptSale?.hour || 'Sin hora' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    Pago
                                </p>

                                <p class="mt-1 text-xs font-black text-slate-900">
                                    {{ receiptPayment?.method_label ||
                                        paymentMethodLabel(receiptPayment?.payment_method) }}
                                </p>

                                <p class="mt-0.5 break-all text-[11px] font-semibold text-slate-600">
                                    Ref: {{ shortText(receiptTransaction, 26) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-300">
                            <table class="min-w-full divide-y divide-slate-300">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-[10px] font-black uppercase tracking-wider">
                                            Detalle
                                        </th>
                                        <th
                                            class="px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider">
                                            Cant.
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right text-[10px] font-black uppercase tracking-wider">
                                            Precio
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right text-[10px] font-black uppercase tracking-wider">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr v-for="item in receiptItems" :key="item.id">
                                        <td class="px-3 py-3">
                                            <p class="text-xs font-black text-slate-900">
                                                {{ item.product?.name || 'Producto' }}
                                            </p>

                                            <p class="mt-0.5 text-[10px] font-semibold text-slate-500">
                                                Producto consumido
                                            </p>
                                        </td>

                                        <td class="px-3 py-3 text-center text-xs font-black text-slate-900">
                                            {{ item.amount }}
                                        </td>

                                        <td class="px-3 py-3 text-right text-xs font-bold text-slate-700">
                                            {{ money(item.price_sale) }}
                                        </td>

                                        <td class="px-3 py-3 text-right text-xs font-black text-slate-900">
                                            {{ money(itemSubtotal(item)) }}
                                        </td>
                                    </tr>

                                    <tr v-if="receiptItems.length === 0">
                                        <td colspan="4"
                                            class="px-3 py-6 text-center text-xs font-semibold text-slate-500">
                                            No hay detalle registrado para esta venta.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_260px]">
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    Observación
                                </p>

                                <p class="mt-1 text-xs font-semibold leading-5 text-slate-600">
                                    Esta nota confirma el pago registrado para la venta indicada.
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                        Pago confirmado
                                    </span>

                                    <span
                                        class="rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-black text-slate-700">
                                        Documento interno
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border-2 border-slate-900 bg-white p-4">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                    <p class="text-xs font-black text-slate-600">
                                        Subtotal
                                    </p>

                                    <p class="text-xs font-black text-slate-900">
                                        {{ money(receiptSubtotal) }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between border-b border-slate-200 py-2">
                                    <p class="text-xs font-black text-slate-600">
                                        Descuento
                                    </p>

                                    <p class="text-xs font-black text-slate-900">
                                        {{ money(0) }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between pt-3">
                                    <p class="text-sm font-black text-slate-950">
                                        Total pagado
                                    </p>

                                    <p class="text-2xl font-black text-slate-950">
                                        {{ money(receiptSale?.total_price) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 border-t border-slate-200 pt-4 sm:grid-cols-2">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    Recibido por
                                </p>

                                <p class="mt-2 text-xs font-black text-slate-900">
                                    Churrasquería Roberto
                                </p>
                            </div>

                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    Cliente
                                </p>

                                <p class="mt-2 text-xs font-black text-slate-900">
                                    {{ receiptPayerName }}
                                </p>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="screen-only shrink-0 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="button"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-black text-slate-700 transition hover:bg-slate-100"
                            @click="closeReceiptModal">
                            Cerrar
                        </button>

                        <button type="button"
                            class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-800"
                            @click="printReceipt">
                            Imprimir nota
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>

<style>
@media print {
    @page {
        size: A4;
        margin: 10mm;
    }

    html,
    body {
        background: #ffffff !important;
    }

    body * {
        visibility: hidden !important;
    }

    .receipt-print-root,
    .receipt-print-root * {
        visibility: visible !important;
    }

    .receipt-modal,
    .receipt-shell,
    .receipt-scroll {
        position: static !important;
        display: block !important;
        width: 100% !important;
        max-width: none !important;
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
        background: #ffffff !important;
        box-shadow: none !important;
        border: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .receipt-print-root {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: 11px !important;
    }

    .screen-only {
        display: none !important;
    }

    .admin-payments-page {
        padding: 0 !important;
    }

    .receipt-print-root table {
        page-break-inside: auto !important;
    }

    .receipt-print-root tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }
}
</style>
