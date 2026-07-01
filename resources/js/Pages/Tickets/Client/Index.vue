<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    tickets: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const listLoading = ref(false);

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
    if (value === 'Pendiente') return 'bg-yellow-500/10 text-yellow-600';
    if (value === 'En preparación') return 'bg-purple-500/10 text-purple-600';
    if (value === 'Listo') return 'bg-blue-500/10 text-blue-600';
    if (value === 'Entregado') return 'bg-green-500/10 text-green-600';
    if (value === 'Cancelado') return 'bg-red-500/10 text-red-500';

    return 'bg-gray-500/10 text-gray-600';
};

const goToPage = (pageNumber) => {
    listLoading.value = true;

    router.get(route('client.tickets.index'), { page: pageNumber }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['tickets'],
        onFinish: () => {
            listLoading.value = false;
        },
    });
};
</script>

<template>
    <Head title="Mis tickets" />

    <SidebarLayout title="Mis tickets" subtitle="Consulta el avance de tus pedidos">
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
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Seguimiento
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Tickets de tus pedidos
                        </h1>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Selecciona un ticket para ver el avance cronológico de tu pedido.
                        </p>
                    </div>

                    <Link
                        :href="route('client.orders.index', { action: 'create' })"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-center text-sm font-black text-white shadow-sm transition hover:opacity-90"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.4"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                        </svg>
                        <span>Nuevo pedido</span>
                    </Link>
                </div>
            </section>

            <section class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div
                    v-if="listLoading"
                    class="absolute inset-0 z-30 flex items-center justify-center bg-[var(--app-card)]/70 backdrop-blur-sm"
                >
                    <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] px-7 py-6 shadow-xl">
                        <p class="text-sm font-black text-[var(--app-text)]">
                            Actualizando tickets...
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 p-5 lg:grid-cols-2 2xl:grid-cols-3">
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
                                    {{ ticket.order_type }}
                                </h2>

                                <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                                    {{ ticket.table?.name ?? 'Sin mesa' }}
                                </p>
                            </div>

                            <span
                                class="rounded-xl px-3 py-1 text-xs font-black"
                                :class="stateClass(ticket.status)"
                            >
                                {{ ticket.status }}
                            </span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-[var(--app-card)] px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Fecha
                                </p>

                                <p class="mt-1 font-black text-[var(--app-text)]">
                                    {{ ticket.date_formatted }}
                                </p>

                                <p class="text-sm font-semibold text-[var(--app-muted)]">
                                    {{ ticket.hour }}
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

                        <Link
                            :href="route('client.tickets.show', ticket.id)"
                            class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-center text-sm font-black text-white transition hover:opacity-90"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.4"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            <span>Ver seguimiento</span>
                        </Link>
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
                            Todavía no tienes tickets
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Cuando realices un pedido, aparecerá aquí para hacer seguimiento.
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
    </SidebarLayout>
</template>
