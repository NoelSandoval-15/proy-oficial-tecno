<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
});

const details = computed(() => props.ticket?.details ?? []);
const timeline = computed(() => props.ticket?.timeline ?? []);

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};

const stateClass = (value) => {
    if (value === 'Pendiente') return 'bg-yellow-500/10 text-yellow-600';
    if (value === 'En preparación') return 'bg-purple-500/10 text-purple-600';
    if (value === 'Listo') return 'bg-blue-500/10 text-blue-600';
    if (value === 'Entregado') return 'bg-green-500/10 text-green-600';
    if (value === 'Cancelado') return 'bg-red-500/10 text-red-500';

    return 'bg-gray-500/10 text-gray-600';
};
</script>

<template>
    <Head :title="`Ticket ${ticket.ticket_code}`" />

    <SidebarLayout title="Seguimiento del ticket" subtitle="Estado cronológico de tu pedido">
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            {{ ticket.ticket_code }}
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Seguimiento de tu pedido
                        </h1>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            {{ ticket.order_type }} · {{ ticket.table?.name ?? 'Sin mesa' }} · {{ ticket.date_formatted }} {{ ticket.hour }}
                        </p>
                    </div>

                    <div class="flex flex-col items-start gap-3 lg:items-end">
                        <span
                            class="rounded-xl px-4 py-2 text-sm font-black"
                            :class="stateClass(ticket.status)"
                        >
                            {{ ticket.status }}
                        </span>

                        <Link
                            :href="route('client.tickets.index')"
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
                            <span>Volver a mis tickets</span>
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Cronología
                        </p>

                        <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                            ¿Por dónde va tu pedido?
                        </h2>
                    </div>

                    <div class="mt-6 space-y-4">
                        <article
                            v-for="(item, index) in timeline"
                            :key="item.status"
                            class="rounded-[2rem] border p-5"
                            :class="item.is_active
                                ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)]'
                                : item.is_completed
                                    ? 'border-green-500/20 bg-green-500/10'
                                    : 'border-[var(--app-border)] bg-[var(--app-surface-soft)]'"
                        >
                            <div class="flex gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-lg font-black"
                                    :class="item.is_active
                                        ? 'bg-[var(--app-primary)] text-white'
                                        : item.is_completed
                                            ? 'bg-green-500 text-white'
                                            : 'bg-[var(--app-card)] text-[var(--app-muted)]'"
                                >
                                    <svg
                                        v-if="item.is_completed"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="3"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>

                                    <span v-else>{{ index + 1 }}</span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <h3 class="text-lg font-black text-[var(--app-text)]">
                                            {{ item.title }}
                                        </h3>

                                        <span
                                            v-if="item.is_active"
                                            class="rounded-xl bg-[var(--app-primary)] px-3 py-1 text-xs font-black text-white"
                                        >
                                            Estado actual
                                        </span>
                                    </div>

                                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                        {{ item.description }}
                                    </p>

                                    <p
                                        v-if="item.date || item.hour"
                                        class="mt-3 inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]"
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
                                                d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"
                                            />
                                        </svg>
                                        <span>{{ item.date }} {{ item.hour }}</span>
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Resumen
                        </p>

                        <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                            Datos del pedido
                        </h2>

                        <div class="mt-5 space-y-3">
                            <div class="rounded-2xl bg-[var(--app-surface-soft)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Tipo
                                </p>

                                <p class="mt-1 font-black text-[var(--app-text)]">
                                    {{ ticket.order_type }}
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
