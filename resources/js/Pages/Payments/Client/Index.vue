<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
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
const receiptSale = ref(null);

const showQrModal = ref(false);
const showReceiptModal = ref(false);

const activeTab = ref('all');
const search = ref('');

const pollingTimer = ref(null);
const checkingPayment = ref(false);
const generatingSaleId = ref(null);
const pendingOpenQrSaleId = ref(null);

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
        qr_generated: 'Esperando pago',
        paid: 'Pagado',
        failed: 'Fallido',
        reverted: 'Revertido',
        cancelled: 'Anulado',
        expired: 'Expirado',
    };

    return map[status] || 'Pendiente';
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

const saleStatusData = (sale) => {
    const currentPayment = payment(sale);
    const status = String(currentPayment?.status || '').toLowerCase();

    if (isPaidSale(sale)) {
        return {
            label: 'Pagado',
            description: 'Pago confirmado. Tu nota de venta está disponible.',
            dot: 'bg-emerald-500',
            badge: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700',
            card: 'border-emerald-500/20',
        };
    }

    if (status === 'qr_generated' && !isExpiredPayment(currentPayment)) {
        return {
            label: 'QR activo',
            description: 'Escanea el QR. El sistema confirmará el pago automáticamente.',
            dot: 'bg-blue-500',
            badge: 'border-blue-500/30 bg-blue-500/10 text-blue-700',
            card: 'border-blue-500/20',
        };
    }

    if (['failed', 'reverted', 'cancelled', 'expired'].includes(status) || isExpiredPayment(currentPayment)) {
        return {
            label: 'Por revisar',
            description: 'El pago no fue confirmado. Puedes generar un nuevo QR.',
            dot: 'bg-red-500',
            badge: 'border-red-500/30 bg-red-500/10 text-red-700',
            card: 'border-red-500/20',
        };
    }

    return {
        label: 'Por pagar',
        description: 'Pedido entregado pendiente de pago.',
        dot: 'bg-amber-500',
        badge: 'border-amber-500/30 bg-amber-500/10 text-amber-700',
        card: 'border-amber-500/20',
    };
};

const debtRows = computed(() => {
    return rows.value.filter((sale) => {
        return !isPaidSale(sale) && String(sale?.status || '').toLowerCase() === 'entregado';
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

const paidTotalOnPage = computed(() => {
    return paidRows.value.reduce((total, sale) => total + Number(sale?.total_price || 0), 0);
});

const lastSale = computed(() => rows.value[0] || null);

const tabs = computed(() => [
    {
        key: 'all',
        label: 'Todos',
        count: rows.value.length,
    },
    {
        key: 'debt',
        label: 'Deudas',
        count: debtRows.value.length,
    },
    {
        key: 'qr',
        label: 'QR activos',
        count: qrRows.value.length,
    },
    {
        key: 'paid',
        label: 'Pagados',
        count: paidRows.value.length,
    },
    {
        key: 'issues',
        label: 'Por revisar',
        count: issueRows.value.length,
    },
]);

const filteredRows = computed(() => {
    let data = rows.value;

    if (activeTab.value === 'debt') {
        data = debtRows.value;
    }

    if (activeTab.value === 'qr') {
        data = qrRows.value;
    }

    if (activeTab.value === 'paid') {
        data = paidRows.value;
    }

    if (activeTab.value === 'issues') {
        data = issueRows.value;
    }

    const term = search.value.trim().toLowerCase();

    if (!term) {
        return data;
    }

    return data.filter((sale) => {
        const currentPayment = payment(sale);

        return [
            sale?.id,
            sale?.status,
            sale?.order_type,
            sale?.date,
            sale?.hour,
            sale?.total_price,
            currentPayment?.payment_number,
            currentPayment?.transaction_id,
            currentPayment?.pagofacil_transaction_id,
            currentPayment?.status_label,
            ...(sale?.details || []).map((item) => item?.product?.name),
        ].filter(Boolean).join(' ').toLowerCase().includes(term);
    });
});

const qrImage = computed(() => {
    if (!selectedPayment.value?.qr_base64) {
        return null;
    }

    return `data:image/png;base64,${selectedPayment.value.qr_base64}`;
});

const canGenerateQr = (sale) => {
    const status = String(sale?.status || '').toLowerCase();
    const currentPayment = payment(sale);
    const payStatus = String(currentPayment?.status || '').toLowerCase();

    return !isPaidSale(sale)
        && status === 'entregado'
        && (!currentPayment || ['pending', 'failed', 'expired', 'cancelled'].includes(payStatus) || isExpiredPayment(currentPayment));
};

const canSeeQr = (sale) => {
    const currentPayment = payment(sale);

    return !isPaidSale(sale)
        && Boolean(currentPayment?.qr_base64)
        && currentPayment?.status === 'qr_generated'
        && !isExpiredPayment(currentPayment);
};

const canSeeReceipt = (sale) => {
    return isPaidSale(sale);
};

const saleItems = (sale) => {
    return sale?.details || [];
};

const itemSubtotal = (item) => {
    return Number(item?.subtotal ?? ((item?.amount || 0) * (item?.price_sale || 0)));
};

const stopPaymentPolling = () => {
    if (pollingTimer.value) {
        clearInterval(pollingTimer.value);
    }

    pollingTimer.value = null;
};

const checkStatus = (currentPayment, silent = true) => {
    if (!currentPayment || currentPayment.status === 'paid' || checkingPayment.value) {
        return;
    }

    checkingPayment.value = true;

    router.post(route('client.payments.check-status', currentPayment.id), {
        silent,
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            checkingPayment.value = false;
        },
    });
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
    }, 4000);
};

const generateQr = (sale) => {
    generatingSaleId.value = sale.id;
    pendingOpenQrSaleId.value = sale.id;

    router.post(route('client.payments.generate-qr', sale.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            generatingSaleId.value = null;
        },
    });
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
        || 'Cliente';
});

const receiptPayerDocument = computed(() => {
    const currentPayment = receiptPayment.value;

    return getValue(currentPayment, 'query_payload.values.payerDocument')
        || getValue(currentPayment, 'callback_payload.payerDocument')
        || getValue(currentPayment, 'callback_payload.Documento')
        || 'No registrado';
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
        if (pendingOpenQrSaleId.value) {
            const freshSale = rows.value.find((sale) => sale.id === pendingOpenQrSaleId.value);

            if (freshSale && canSeeQr(freshSale)) {
                pendingOpenQrSaleId.value = null;
                openQrModal(freshSale);
            }
        }

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

                if (selectedPayment.value && isFinalUnpaidPayment(selectedPayment.value)) {
                    stopPaymentPolling();
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

onBeforeUnmount(() => {
    stopPaymentPolling();
});
</script>

<template>
    <Head title="Mis pagos" />

    <SidebarLayout
        title="Mis pagos"
        subtitle="Historial de pagos, deudas pendientes y notas de venta."
    >
        <div class="client-payments-page space-y-6 pt-24 lg:pt-10 print:pt-0">
            <div
                v-if="flashSuccess || flashError || flashInfo"
                class="grid gap-3 print:hidden"
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

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 print:hidden">
                <article class="rounded-[1.4rem] border border-amber-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Deuda actual
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ money(summary.pending_total) }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        {{ summary.pending_count || 0 }} pedido(s) por pagar
                    </p>
                </article>

                <article class="rounded-[1.4rem] border border-blue-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        QR activos
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ qrRows.length }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        Esperando confirmación
                    </p>
                </article>

                <article class="rounded-[1.4rem] border border-emerald-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Pagos confirmados
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ summary.paid_count || 0 }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        {{ money(paidTotalOnPage) }} en esta página
                    </p>
                </article>

                <article class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Último movimiento
                    </p>

                    <p class="mt-2 text-xl font-black text-[var(--app-text)]">
                        {{ lastSale ? `Venta #${lastSale.id}` : 'Sin ventas' }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        {{ lastSale ? saleStatusData(lastSale).label : 'Sin registros' }}
                    </p>
                </article>
            </section>

            <section class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-4 shadow-sm print:hidden">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            :class="[
                                'rounded-xl border px-4 py-2 text-sm font-black transition',
                                activeTab === tab.key
                                    ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white'
                                    : 'border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] hover:border-[var(--app-primary)]'
                            ]"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                            <span class="ml-2 text-xs">
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar venta, producto, estado o transacción"
                        class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)] xl:max-w-sm"
                    />
                </div>
            </section>

            <section class="grid gap-3 print:hidden">
                <article
                    v-for="sale in filteredRows"
                    :key="sale.id"
                    :class="[
                        'rounded-[1.4rem] border bg-[var(--app-card)] p-5 shadow-sm',
                        saleStatusData(sale).card
                    ]"
                >
                    <div class="grid gap-4 xl:grid-cols-[1fr_220px] xl:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="[
                                        'h-2.5 w-2.5 rounded-full',
                                        saleStatusData(sale).dot
                                    ]"
                                ></span>

                                <p class="text-lg font-black text-[var(--app-text)]">
                                    Venta #{{ sale.id }}
                                </p>

                                <span
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs font-black',
                                        saleStatusData(sale).badge
                                    ]"
                                >
                                    {{ saleStatusData(sale).label }}
                                </span>
                            </div>

                            <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                                {{ saleStatusData(sale).description }}
                            </p>

                            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Fecha
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ formatDate(sale.date) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Hora
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ sale.hour || 'Sin hora' }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Tipo
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ sale.order_type || 'Pedido' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-xl border border-[var(--app-border)]">
                                <table class="min-w-full divide-y divide-[var(--app-border)]">
                                    <tbody class="divide-y divide-[var(--app-border)]">
                                        <tr
                                            v-for="item in saleItems(sale)"
                                            :key="item.id"
                                            class="bg-[var(--app-card)]"
                                        >
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
                            <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                                <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                    Total
                                </p>

                                <p class="mt-1 text-2xl font-black text-[var(--app-text)]">
                                    {{ money(sale.total_price) }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                    {{ saleItems(sale).length }} producto(s)
                                </p>
                            </div>

                            <div class="mt-3 grid gap-2">
                                <button
                                    v-if="canGenerateQr(sale)"
                                    type="button"
                                    class="rounded-xl bg-[var(--app-primary)] px-4 py-3 text-sm font-black text-white transition hover:opacity-90 disabled:opacity-60"
                                    :disabled="generatingSaleId === sale.id"
                                    @click="generateQr(sale)"
                                >
                                    {{ generatingSaleId === sale.id ? 'Generando QR...' : 'Pagar con QR' }}
                                </button>

                                <button
                                    v-if="canSeeQr(sale)"
                                    type="button"
                                    class="rounded-xl border border-blue-500/30 bg-blue-500/10 px-4 py-3 text-sm font-black text-blue-700 transition hover:bg-blue-500/20"
                                    @click="openQrModal(sale)"
                                >
                                    Ver QR activo
                                </button>

                                <button
                                    v-if="canSeeReceipt(sale)"
                                    type="button"
                                    class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-500/20"
                                    @click="openReceiptModal(sale)"
                                >
                                    Ver nota de venta
                                </button>
                            </div>
                        </div>
                    </div>
                </article>

                <article
                    v-if="filteredRows.length === 0"
                    class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-10 text-center shadow-sm"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--app-surface-soft)] text-xl font-black text-[var(--app-muted)]">
                        0
                    </div>

                    <p class="mt-4 text-base font-black text-[var(--app-text)]">
                        No hay resultados para mostrar
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Cambia el filtro o revisa cuando tengas nuevos pedidos entregados.
                    </p>
                </article>
            </section>

            <div
                v-if="sales.links?.length"
                class="flex flex-wrap items-center justify-between gap-3 rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-4 print:hidden"
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
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-3 py-4 backdrop-blur-sm print:hidden"
        >
            <div class="flex max-h-[92vh] w-[min(94vw,900px)] flex-col overflow-hidden rounded-[1.5rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-start justify-between border-b border-[var(--app-border)] px-5 py-4">
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
                        class="rounded-xl px-3 py-2 text-xl font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                        @click="closeQrModal"
                    >
                        ×
                    </button>
                </div>

                <div class="overflow-y-auto p-5">
                    <div class="grid gap-5 lg:grid-cols-[330px_1fr]">
                        <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5 text-center">
                            <img
                                v-if="qrImage"
                                :src="qrImage"
                                alt="QR PagoFácil"
                                class="mx-auto h-64 w-64 rounded-2xl bg-white p-4 shadow-sm sm:h-72 sm:w-72"
                            />

                            <div
                                v-else
                                class="mx-auto flex h-64 w-64 items-center justify-center rounded-2xl bg-[var(--app-card)] text-sm font-bold text-[var(--app-muted)] sm:h-72 sm:w-72"
                            >
                                QR no disponible
                            </div>

                            <p class="mt-4 text-sm font-black text-[var(--app-text)]">
                                Escanea este QR con tu aplicación bancaria
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
                                    Realiza el pago desde tu banco. Cuando PagoFácil confirme la transacción,
                                    esta ventana se cerrará y verás tu nota de venta automáticamente.
                                </p>
                            </div>

                            <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5">
                                <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Número de pago
                                </p>

                                <p class="mt-2 break-all text-sm font-bold text-[var(--app-text)]">
                                    {{ selectedPayment?.payment_number || 'Sin número' }}
                                </p>
                            </div>

                            <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5">
                                <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                                    Estado
                                </p>

                                <p class="mt-2 text-lg font-black text-[var(--app-text)]">
                                    {{ checkingPayment ? 'Verificando pago...' : paymentStatusLabel(selectedPayment?.status, selectedSale) }}
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                                    @click="closeQrModal"
                                >
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showReceiptModal"
            class="receipt-modal fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-2 py-3 backdrop-blur-sm sm:px-4 sm:py-5"
        >
            <div class="receipt-shell flex max-h-[92vh] w-[min(96vw,780px)] flex-col overflow-hidden rounded-[1.2rem] bg-white shadow-2xl">
                <div class="screen-only flex shrink-0 items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                    <div>
                        <p class="text-base font-black text-slate-900 sm:text-lg">
                            Nota de venta
                        </p>

                        <p class="text-xs font-semibold text-slate-500">
                            Venta #{{ receiptSale?.id }} · Pago confirmado
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-xl px-3 py-1.5 text-lg font-black text-slate-500 transition hover:bg-slate-200 hover:text-slate-900"
                        @click="closeReceiptModal"
                    >
                        ×
                    </button>
                </div>

                <div class="receipt-scroll flex-1 overflow-y-auto bg-white p-4 text-slate-900 sm:p-5">
                    <article class="receipt-print-root mx-auto max-w-[720px] bg-white">
                        <div class="receipt-top-grid grid gap-4 border-b-2 border-slate-900 pb-4 sm:grid-cols-[1.2fr_0.8fr] sm:items-start">
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

                                <div class="mx-auto mt-2 inline-flex rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-black text-white">
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
                                    {{ receiptPayment?.method_label || paymentMethodLabel(receiptPayment?.payment_method) }}
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
                                        <th class="px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider">
                                            Cant.
                                        </th>
                                        <th class="px-3 py-2 text-right text-[10px] font-black uppercase tracking-wider">
                                            Precio
                                        </th>
                                        <th class="px-3 py-2 text-right text-[10px] font-black uppercase tracking-wider">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr
                                        v-for="item in receiptItems"
                                        :key="item.id"
                                    >
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
                                        <td colspan="4" class="px-3 py-6 text-center text-xs font-semibold text-slate-500">
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
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                        Pago confirmado
                                    </span>

                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-black text-slate-700">
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
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-black text-slate-700 transition hover:bg-slate-100"
                            @click="closeReceiptModal"
                        >
                            Cerrar
                        </button>

                        <button
                            type="button"
                            class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-800"
                            @click="printReceipt"
                        >
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

    .client-payments-page {
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
