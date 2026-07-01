<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
});

const details = computed(() => props.ticket?.details ?? []);
const timeline = computed(() => props.ticket?.timeline ?? []);
const allowedNextStatuses = computed(() => props.ticket?.allowed_next_statuses ?? []);

const statusForm = useForm({
    status: '',
});

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

const changeStatus = (nextStatus) => {
    statusForm.status = nextStatus;

    statusForm.patch(route('tickets.change-status', props.ticket.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Ticket ${ticket.ticket_code}`" />

    <SidebarLayout title="Detalle del ticket" subtitle="Seguimiento interno del pedido">
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            {{ ticket.ticket_code }}
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            {{ ticket.client?.label ?? ticket.client?.name ?? 'Venta sin cliente' }}
                        </h1>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            {{ ticket.order_type }} · {{ ticket.table?.name ?? 'Sin mesa' }} · {{ ticket.date_formatted }} {{ ticket.hour }}
                        </p>
                    </div>

                    <div class="flex flex-col items-start gap-3 xl:items-end">
                        <span
                            class="rounded-xl border px-4 py-2 text-sm font-black"
                            :class="stateClass(ticket.status)"
                        >
                            {{ ticket.status }}
                        </span>

                        <Link
                            :href="route('tickets.index')"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[var(--app-border)] px-5 py-3 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.4"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Volver a tickets</span>
                        </Link>
                    </div>
                </div>
            </section>

            <section
                v-if="allowedNextStatuses.length"
                class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
            >
                <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                    Acciones rápidas
                </p>

                <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                    Actualizar estado
                </h2>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button
                        v-for="nextStatus in allowedNextStatuses"
                        :key="nextStatus"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-black transition disabled:opacity-60"
                        :class="nextButtonClass(nextStatus)"
                        :disabled="statusForm.processing"
                        @click="changeStatus(nextStatus)"
                    >
                        <svg
                            v-if="nextStatus !== 'Cancelado'"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
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
                            class="h-5 w-5"
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
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                        Historial
                    </p>

                    <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        Movimientos del ticket
                    </h2>

                    <div class="mt-6 space-y-4">
                        <article
                            v-for="item in timeline"
                            :key="`${item.status}-${item.date}-${item.hour}`"
                            class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5"
                        >
                            <div class="flex gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-white">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.4"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-lg font-black text-[var(--app-text)]">
                                        {{ item.title }}
                                    </h3>

                                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                        {{ item.description }}
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                        <span>{{ item.date }} {{ item.hour }}</span>
                                        <span v-if="item.user">· {{ item.user.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <div
                            v-if="timeline.length === 0"
                            class="rounded-[2rem] border border-dashed border-[var(--app-border)] bg-[var(--app-surface-soft)] px-6 py-10 text-center"
                        >
                            <p class="text-lg font-black text-[var(--app-text)]">
                                Todavía no hay movimientos registrados
                            </p>
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Resumen
                        </p>

                        <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                            Datos del ticket
                        </h2>

                        <div class="mt-5 space-y-3">
                            <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Cliente
                                </p>

                                <p class="mt-1 font-black text-[var(--app-text)]">
                                    {{ ticket.client?.label ?? ticket.client?.name ?? 'Sin cliente' }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Ubicación
                                </p>

                                <p class="mt-1 font-black text-[var(--app-text)]">
                                    {{ ticket.table?.name ?? 'Sin mesa' }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Total
                                </p>

                                <p class="mt-1 text-2xl font-black text-[var(--app-text)]">
                                    {{ formatMoney(ticket.total_price) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Productos
                        </p>

                        <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                            Detalle
                        </h2>

                        <div class="mt-5 space-y-3">
                            <article
                                v-for="detail in details"
                                :key="detail.id"
                                class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3"
                            >
                                <div class="flex items-start justify-between gap-3">
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
                                </div>
                            </article>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </SidebarLayout>
</template>
