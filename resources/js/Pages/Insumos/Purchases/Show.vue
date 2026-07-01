<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    purchaseNote: {
        type: Object,
        required: true,
    },
});

const details = computed(() => {
    return props.purchaseNote?.details_purchases ?? [];
});

const totalItems = computed(() => {
    return details.value.reduce((total, detail) => {
        return total + Number(detail.amount ?? 0);
    }, 0);
});

const subtotal = (detail) => {
    return Number(detail.amount ?? 0) * Number(detail.price_purchase ?? 0);
};

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};
</script>

<template>
    <Head title="Detalle de compra" />

    <SidebarLayout
        title="Detalle de compra"
        subtitle="Información completa de la compra de insumos registrada"
    >
        <div class="space-y-6">
            <div class="flex justify-end">
                <Link
                    :href="route('insumos.purchases.index')"
                    class="rounded-2xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-text)] transition hover:bg-[var(--app-surface-soft)]"
                >
                    Volver a compras
                </Link>
            </div>

            <section class="grid gap-4 md:grid-cols-4">
                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Fecha
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ purchaseNote.date }}
                    </p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Hora
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ purchaseNote.hour }}
                    </p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Cantidad comprada
                    </p>

                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">
                        {{ totalItems }}
                    </p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-primary-soft)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-primary)]">
                        Total
                    </p>

                    <p class="mt-2 text-3xl font-black text-[var(--app-text)]">
                        {{ formatMoney(purchaseNote.total_price) }}
                    </p>
                </article>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Datos generales
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Compra de insumos
                        </h1>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Esta compra incrementó automáticamente el stock de los insumos registrados.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                            Registrado por
                        </p>

                        <p class="mt-1 text-base font-black text-[var(--app-text)]">
                            {{ purchaseNote.users?.name ?? 'Sin usuario' }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            {{ purchaseNote.users?.email ?? 'Sin correo' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                            Proveedor
                        </p>

                        <p class="mt-1 text-lg font-black text-[var(--app-text)]">
                            {{ purchaseNote.suppliers?.name ?? 'Sin proveedor' }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Teléfono: {{ purchaseNote.suppliers?.telephone ?? 'Sin teléfono' }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            {{ purchaseNote.suppliers?.description ?? '' }}
                        </p>
                    </div>

                    <div class="rounded-3xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                            Resumen
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Insumos diferentes: {{ details.length }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Cantidad total comprada: {{ totalItems }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Total registrado: {{ formatMoney(purchaseNote.total_price) }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div class="flex flex-col gap-3 border-b border-[var(--app-border)] px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-[var(--app-text)]">
                            Detalle de insumos comprados
                        </h2>

                        <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                            Cantidad, precio de compra y subtotal por insumo.
                        </p>
                    </div>

                    <p class="text-sm font-black text-[var(--app-primary)]">
                        {{ details.length }} registros
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                <th class="px-6 py-4">Insumo</th>
                                <th class="px-6 py-4">Cantidad</th>
                                <th class="px-6 py-4">Precio compra</th>
                                <th class="px-6 py-4 text-right">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--app-border)]">
                            <tr
                                v-for="detail in details"
                                :key="detail.id"
                                class="transition hover:bg-[var(--app-surface-soft)]"
                            >
                                <td class="px-6 py-5">
                                    <p class="font-black text-[var(--app-text)]">
                                        {{ detail.insumos?.name ?? 'Insumo eliminado' }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                        Stock actual: {{ detail.insumos?.amount ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ detail.amount }}
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ formatMoney(detail.price_purchase) }}
                                </td>

                                <td class="px-6 py-5 text-right text-sm font-black text-[var(--app-primary)]">
                                    {{ formatMoney(subtotal(detail)) }}
                                </td>
                            </tr>

                            <tr v-if="details.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <p class="text-lg font-black text-[var(--app-text)]">
                                        Esta compra no tiene detalle
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                        No se encontraron insumos asociados a esta compra.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </SidebarLayout>
</template>
