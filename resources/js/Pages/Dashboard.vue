<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    dashboard: {
        type: Object,
        default: () => ({}),
    },
});

const roleGroup = computed(() => props.dashboard?.role_group || 'client');
const hero = computed(() => props.dashboard?.hero || {});
const metrics = computed(() => props.dashboard?.metrics || []);
const statusBreakdown = computed(() => props.dashboard?.status_breakdown || []);
const topProducts = computed(() => props.dashboard?.top_products || []);
const recentSales = computed(() => props.dashboard?.recent_sales || []);
const quickActions = computed(() => props.dashboard?.quick_actions || []);

const isAdmin = computed(() => roleGroup.value === 'admin');
const isWaiter = computed(() => roleGroup.value === 'waiter');
const isClient = computed(() => roleGroup.value === 'client');

const money = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const number = (value) => {
    return new Intl.NumberFormat('es-BO').format(Number(value || 0));
};

const metricValue = (metric) => {
    if (metric.type === 'money') {
        return money(metric.value);
    }

    return number(metric.value);
};

const toneClasses = (tone) => {
    const map = {
        emerald: {
            card: 'border-emerald-500/20',
            icon: 'bg-emerald-500/10 text-emerald-700',
            badge: 'bg-emerald-500/10 text-emerald-700',
            bar: 'bg-emerald-500',
        },
        amber: {
            card: 'border-amber-500/20',
            icon: 'bg-amber-500/10 text-amber-700',
            badge: 'bg-amber-500/10 text-amber-700',
            bar: 'bg-amber-500',
        },
        blue: {
            card: 'border-blue-500/20',
            icon: 'bg-blue-500/10 text-blue-700',
            badge: 'bg-blue-500/10 text-blue-700',
            bar: 'bg-blue-500',
        },
        primary: {
            card: 'border-[var(--app-primary)]/20',
            icon: 'bg-[var(--app-primary)]/10 text-[var(--app-primary)]',
            badge: 'bg-[var(--app-primary)]/10 text-[var(--app-primary)]',
            bar: 'bg-[var(--app-primary)]',
        },
    };

    return map[tone] || map.primary;
};

const statusTotal = computed(() => {
    return statusBreakdown.value.reduce((total, item) => total + Number(item.count || 0), 0);
});

const statusPercent = (item) => {
    if (!statusTotal.value) {
        return 0;
    }

    return Math.round((Number(item.count || 0) / statusTotal.value) * 100);
};

const paymentStatusLabel = (status) => {
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

const saleStatusClass = (status) => {
    const normalized = String(status || '').toLowerCase();

    if (normalized === 'pagado') {
        return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700';
    }

    if (normalized === 'entregado') {
        return 'border-amber-500/30 bg-amber-500/10 text-amber-700';
    }

    if (normalized === 'cancelado') {
        return 'border-red-500/30 bg-red-500/10 text-red-700';
    }

    return 'border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-muted)]';
};

const emptyMessage = computed(() => {
    if (isAdmin.value) {
        return 'Todavía no hay suficiente información para reportes gerenciales.';
    }

    if (isWaiter.value) {
        return 'Aún no tienes ventas recientes asignadas.';
    }

    return 'Aún no tienes consumos recientes.';
});
</script>

<template>
    <Head title="Dashboard" />

    <SidebarLayout
        title="Dashboard"
        :subtitle="hero.subtitle || 'Panel general de Churrasquería Roberto'"
        v-slot="{ theme }"
    >
        <div class="space-y-6 pt-24 lg:pt-10">
            <section
                class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-hero)] p-6 text-[var(--app-hero-text)] shadow-xl lg:p-8"
            >
                <div class="absolute -right-16 -top-20 h-72 w-72 rounded-full bg-[var(--app-primary)]/25 blur-3xl"></div>
                <div class="absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-[var(--app-accent)]/20 blur-3xl"></div>

                <div class="relative grid gap-6 xl:grid-cols-[1.2fr_0.8fr] xl:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white/15 px-4 py-2 text-xs font-black uppercase tracking-wider">
                                {{ hero.eyebrow || 'Panel principal' }}
                            </span>

                            <span class="rounded-full bg-[var(--app-primary-soft)] px-4 py-2 text-xs font-black text-[var(--app-primary-text)]">
                                {{ hero.badge || theme.displayName }}
                            </span>
                        </div>

                        <h1 class="mt-5 max-w-4xl text-3xl font-black leading-tight sm:text-4xl xl:text-5xl">
                            {{ hero.title || 'Churrasquería Roberto' }}
                        </h1>

                        <p class="mt-4 max-w-3xl text-sm font-semibold leading-7 opacity-85 sm:text-base">
                            {{ hero.subtitle || 'Sistema de administración para ventas, reservas, pagos, clientes e inventario.' }}
                        </p>
                    </div>

                    <div class="rounded-[1.7rem] border border-white/15 bg-white/10 p-5 backdrop-blur">
                        <p class="text-sm font-bold opacity-75">
                            Vista personalizada para
                        </p>

                        <p class="mt-1 text-2xl font-black">
                            {{ hero.badge || 'Usuario' }}
                        </p>

                        <div class="mt-5 grid grid-cols-3 gap-3">
                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-xs font-black opacity-70">Tema</p>
                                <p class="mt-1 text-sm font-black">{{ theme.displayName }}</p>
                            </div>

                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-xs font-black opacity-70">Rol</p>
                                <p class="mt-1 text-sm font-black">{{ hero.badge }}</p>
                            </div>

                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-xs font-black opacity-70">Estado</p>
                                <p class="mt-1 text-sm font-black">Activo</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="metric in metrics"
                    :key="metric.label"
                    :class="[
                        'relative overflow-hidden rounded-[1.5rem] border bg-[var(--app-card)] p-5 shadow-sm',
                        toneClasses(metric.tone).card
                    ]"
                >
                    <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full opacity-20 blur-2xl"
                         :class="toneClasses(metric.tone).bar"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                {{ metric.label }}
                            </p>

                            <p class="mt-3 text-2xl font-black tracking-tight text-[var(--app-text)]">
                                {{ metricValue(metric) }}
                            </p>

                            <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                                {{ metric.helper }}
                            </p>
                        </div>

                        <div
                            :class="[
                                'flex h-11 w-11 items-center justify-center rounded-xl',
                                toneClasses(metric.tone).icon
                            ]"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path
                                    v-if="metric.tone === 'emerald'"
                                    d="m5 12 4 4L19 6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    v-else-if="metric.tone === 'amber'"
                                    d="M12 8v5l3 2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle
                                    v-if="metric.tone === 'amber'"
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />
                                <template v-else-if="metric.tone === 'blue'">
                                    <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z" />
                                    <path d="M14 14h2v2h-2zM18 14h2v6h-4v-2h2zM14 18h2v2h-2z" />
                                </template>
                                <template v-else-if="metric.tone === 'primary'">
                                    <path d="M4 19V5" stroke-linecap="round" />
                                    <path d="M8 17V9M12 17V7M16 17v-5M20 17V4" stroke-linecap="round" />
                                </template>
                            </svg>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                <article class="rounded-[1.6rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-lg font-black text-[var(--app-text)]">
                                {{ isClient ? 'Mis consumos recientes' : 'Ventas recientes' }}
                            </p>

                            <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                {{ isClient ? 'Últimos pedidos y pagos registrados.' : 'Últimas ventas con estado de pago y cliente.' }}
                            </p>
                        </div>

                        <span class="rounded-full bg-[var(--app-surface-soft)] px-3 py-1 text-xs font-black text-[var(--app-muted)]">
                            {{ recentSales.length }} registros
                        </span>
                    </div>

                    <div class="mt-5 grid gap-3">
                        <article
                            v-for="sale in recentSales"
                            :key="sale.id"
                            class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-black text-[var(--app-text)]">
                                            Venta #{{ sale.id }}
                                        </p>

                                        <span
                                            :class="[
                                                'rounded-full border px-3 py-1 text-xs font-black',
                                                saleStatusClass(sale.status)
                                            ]"
                                        >
                                            {{ sale.status }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                                        {{ sale.client }} · {{ sale.date }} {{ sale.hour || '' }}
                                    </p>

                                    <p class="mt-1 text-xs font-bold text-[var(--app-muted)]">
                                        {{ sale.items_count || 0 }} producto(s) · {{ paymentMethodLabel(sale.payment_method) }} · {{ paymentStatusLabel(sale.payment_status) }}
                                    </p>
                                </div>

                                <p class="text-2xl font-black text-[var(--app-text)]">
                                    {{ money(sale.total) }}
                                </p>
                            </div>
                        </article>

                        <article
                            v-if="recentSales.length === 0"
                            class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-8 text-center"
                        >
                            <p class="text-base font-black text-[var(--app-text)]">
                                Sin movimientos todavía
                            </p>

                            <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                {{ emptyMessage }}
                            </p>
                        </article>
                    </div>
                </article>

                <aside class="space-y-6">
                    <article class="rounded-[1.6rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                        <p class="text-lg font-black text-[var(--app-text)]">
                            Acciones rápidas
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Atajos según tu rol.
                        </p>

                        <div class="mt-5 grid gap-3">
                            <Link
                                v-for="action in quickActions"
                                :key="action.label"
                                :href="action.url"
                                :class="[
                                    'group rounded-[1.2rem] border p-4 transition hover:-translate-y-0.5 hover:shadow-md',
                                    toneClasses(action.tone).card
                                ]"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-[var(--app-text)]">
                                            {{ action.label }}
                                        </p>

                                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                            {{ action.description }}
                                        </p>
                                    </div>

                                    <div
                                        :class="[
                                            'flex h-10 w-10 items-center justify-center rounded-xl',
                                            toneClasses(action.tone).icon
                                        ]"
                                    >
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </article>

                    <article class="rounded-[1.6rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                        <p class="text-lg font-black text-[var(--app-text)]">
                            {{ isClient ? 'Mis favoritos' : 'Productos destacados' }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Basado en cantidad vendida.
                        </p>

                        <div class="mt-5 grid gap-3">
                            <div
                                v-for="product in topProducts"
                                :key="product.name"
                                class="rounded-[1.1rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-[var(--app-text)]">
                                            {{ product.name }}
                                        </p>

                                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                            {{ number(product.quantity) }} unidad(es)
                                        </p>
                                    </div>

                                    <p class="text-sm font-black text-[var(--app-text)]">
                                        {{ money(product.total) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="topProducts.length === 0"
                                class="rounded-[1.1rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5 text-center"
                            >
                                <p class="text-sm font-black text-[var(--app-text)]">
                                    Sin productos aún
                                </p>

                                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                    Aparecerán cuando existan consumos.
                                </p>
                            </div>
                        </div>
                    </article>
                </aside>
            </section>

            <section class="rounded-[1.6rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-lg font-black text-[var(--app-text)]">
                            Estado de ventas
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Distribución general según tu rol.
                        </p>
                    </div>

                    <span class="rounded-full bg-[var(--app-surface-soft)] px-3 py-1 text-xs font-black text-[var(--app-muted)]">
                        Total: {{ number(statusTotal) }}
                    </span>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="item in statusBreakdown"
                        :key="item.label"
                        class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-[var(--app-text)]">
                                {{ item.label }}
                            </p>

                            <p class="text-sm font-black text-[var(--app-muted)]">
                                {{ number(item.count) }}
                            </p>
                        </div>

                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-[var(--app-card)]">
                            <div
                                class="h-full rounded-full bg-[var(--app-primary)]"
                                :style="{ width: `${statusPercent(item)}%` }"
                            ></div>
                        </div>

                        <p class="mt-2 text-xs font-semibold text-[var(--app-muted)]">
                            {{ statusPercent(item) }}% del total
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </SidebarLayout>
</template>
