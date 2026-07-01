<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    suppliers: {
        type: Array,
        default: () => [],
    },
    insumos: {
        type: Array,
        default: () => [],
    },
});

const providerSearch = ref('');
const providerOpen = ref(false);

const form = useForm({
    suppliers_id: '',
    details: [
        {
            insumos_id: '',
            amount: 1,
            search: '',
            open: false,
        },
    ],
});

const filteredSuppliers = computed(() => {
    const text = providerSearch.value.toLowerCase().trim();

    if (!text) {
        return props.suppliers;
    }

    return props.suppliers.filter((supplier) => {
        return [
            supplier.name,
            supplier.telephone,
        ]
            .join(' ')
            .toLowerCase()
            .includes(text);
    });
});

const selectedSupplier = computed(() => {
    return props.suppliers.find((supplier) => {
        return Number(supplier.id) === Number(form.suppliers_id);
    });
});

const selectedInsumosIds = computed(() => {
    return form.details
        .map((detail) => Number(detail.insumos_id))
        .filter(Boolean);
});

const getInsumo = (id) => {
    return props.insumos.find((insumo) => Number(insumo.id) === Number(id));
};

const filteredInsumos = (detail) => {
    const text = (detail.search ?? '').toLowerCase().trim();

    if (!text) {
        return props.insumos;
    }

    return props.insumos.filter((insumo) => {
        return insumo.name.toLowerCase().includes(text);
    });
};

const detailUnitPrice = (detail) => {
    const insumo = getInsumo(detail.insumos_id);

    return Number(insumo?.price ?? 0);
};

const detailSubtotal = (detail) => {
    const amount = Number(detail.amount) || 0;

    return amount * detailUnitPrice(detail);
};

const totalPrice = computed(() => {
    return form.details.reduce((total, detail) => {
        return total + detailSubtotal(detail);
    }, 0);
});

const totalAmount = computed(() => {
    return form.details.reduce((total, detail) => {
        return total + Number(detail.amount || 0);
    }, 0);
});

const selectSupplier = (supplier) => {
    form.suppliers_id = supplier.id;
    providerSearch.value = `${supplier.name} - ${supplier.telephone}`;
    providerOpen.value = false;
};

const clearSupplier = () => {
    form.suppliers_id = '';
    providerSearch.value = '';
    providerOpen.value = false;
};

const selectInsumo = (detail, insumo) => {
    detail.insumos_id = insumo.id;
    detail.search = insumo.name;
    detail.open = false;
};

const addDetail = () => {
    form.details.push({
        insumos_id: '',
        amount: 1,
        search: '',
        open: false,
    });
};

const removeDetail = (index) => {
    if (form.details.length === 1) {
        return;
    }

    form.details.splice(index, 1);
};

const blockNegative = (event) => {
    if (['-', '+', 'e', 'E'].includes(event.key)) {
        event.preventDefault();
    }
};

const sanitizeAmount = (detail) => {
    if (detail.amount === '' || detail.amount === null) {
        return;
    }

    if (Number(detail.amount) < 1 || Number.isNaN(Number(detail.amount))) {
        detail.amount = 1;
    }
};

const submit = () => {
    form.details.forEach((detail) => {
        sanitizeAmount(detail);
    });

    form.post(route('insumos.purchases.store'), {
        preserveScroll: true,
    });
};

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};
</script>

<template>
    <Head title="Nueva compra de insumos" />

    <SidebarLayout
        title="Nueva compra de insumos"
        subtitle="Registra compras y actualiza automáticamente el stock"
    >
        <div class="space-y-6">
            <div class="flex justify-end">
                <Link
                    :href="route('insumos.purchases.index')"
                    class="inline-flex items-center gap-2 rounded-2xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-text)] transition hover:bg-[var(--app-surface-soft)]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver a compras
                </Link>
            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Fecha y hora</p>
                    <p class="mt-2 text-lg font-black text-[var(--app-primary)]">
                        Automática
                    </p>
                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        Se guardará el momento exacto del registro.
                    </p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Cantidad total</p>
                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">{{ totalAmount }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-primary-soft)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-primary)]">Total calculado</p>
                    <p class="mt-2 text-3xl font-black text-[var(--app-text)]">{{ formatMoney(totalPrice) }}</p>
                </article>
            </section>

            <form
                class="space-y-6 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
                @submit.prevent="submit"
            >
                <section>
                    <div class="mb-4">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Proveedor
                        </p>

                        <h3 class="mt-1 text-2xl font-black text-[var(--app-text)]">
                            Selecciona o busca el proveedor
                        </h3>

                        <p class="text-sm font-semibold text-[var(--app-muted)]">
                            Puedes buscar por nombre o teléfono.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>

                            <input
                                v-model="providerSearch"
                                type="text"
                                placeholder="Buscar proveedor por nombre o teléfono..."
                                class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] py-3 pl-12 pr-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                @focus="providerOpen = true"
                            />

                            <button
                                v-if="form.suppliers_id"
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-[var(--app-muted)] transition hover:bg-[var(--app-card)] hover:text-red-500"
                                @click="clearSupplier"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="providerOpen"
                            class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-[1.5rem] border border-[var(--app-border)] bg-[var(--app-card)] p-2 shadow-2xl"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-bold text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)]"
                                @click="clearSupplier"
                            >
                                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--app-surface-soft)] text-[var(--app-primary)]">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </span>

                                Sin proveedor
                            </button>

                            <button
                                v-for="supplier in filteredSuppliers"
                                :key="supplier.id"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left transition hover:bg-[var(--app-primary-soft)]"
                                @click="selectSupplier(supplier)"
                            >
                                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-sm font-black text-white">
                                    PR
                                </span>

                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-black text-[var(--app-text)]">
                                        {{ supplier.name }}
                                    </span>

                                    <span class="block truncate text-xs font-semibold text-[var(--app-muted)]">
                                        Teléfono: {{ supplier.telephone }}
                                    </span>
                                </span>
                            </button>

                            <div
                                v-if="filteredSuppliers.length === 0"
                                class="px-4 py-6 text-center text-sm font-bold text-[var(--app-muted)]"
                            >
                                No se encontraron proveedores.
                            </div>
                        </div>
                    </div>

                    <p v-if="selectedSupplier" class="mt-3 text-sm font-black text-[var(--app-primary)]">
                        Proveedor seleccionado: {{ selectedSupplier.name }}
                    </p>

                    <p v-if="form.errors.suppliers_id" class="mt-1 text-sm font-bold text-red-500">
                        {{ form.errors.suppliers_id }}
                    </p>
                </section>

                <section>
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                                Detalle de compra
                            </p>

                            <h3 class="mt-1 text-2xl font-black text-[var(--app-text)]">
                                Insumos comprados
                            </h3>

                            <p class="text-sm font-semibold text-[var(--app-muted)]">
                                Subtotal = cantidad × precio actual del insumo.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-4 py-3 text-sm font-black text-white"
                            @click="addDetail"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" />
                            </svg>
                            Agregar insumo
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(detail, index) in form.details"
                            :key="index"
                            class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4"
                        >
                            <div class="grid gap-4 xl:grid-cols-[1fr_140px_170px_170px_70px] xl:items-end">
                                <div class="relative">
                                    <label class="text-sm font-black text-[var(--app-text)]">
                                        Buscar insumo
                                    </label>

                                    <div class="relative mt-2">
                                        <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                                        </svg>

                                        <input
                                            v-model="detail.search"
                                            type="text"
                                            placeholder="Buscar por nombre del insumo..."
                                            class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-card)] py-3 pl-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                            @focus="detail.open = true"
                                        />
                                    </div>

                                    <div
                                        v-if="detail.open"
                                        class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-[1.5rem] border border-[var(--app-border)] bg-[var(--app-card)] p-2 shadow-2xl"
                                    >
                                        <button
                                            v-for="insumo in filteredInsumos(detail)"
                                            :key="insumo.id"
                                            type="button"
                                            class="flex w-full items-center justify-between gap-3 rounded-2xl px-4 py-3 text-left transition hover:bg-[var(--app-primary-soft)] disabled:cursor-not-allowed disabled:opacity-40"
                                            :disabled="selectedInsumosIds.includes(insumo.id) && Number(detail.insumos_id) !== Number(insumo.id)"
                                            @click="selectInsumo(detail, insumo)"
                                        >
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-black text-[var(--app-text)]">
                                                    {{ insumo.name }}
                                                </span>

                                                <span class="block truncate text-xs font-semibold text-[var(--app-muted)]">
                                                    Stock actual: {{ insumo.amount }}
                                                </span>
                                            </span>

                                            <span class="shrink-0 rounded-xl bg-[var(--app-primary-soft)] px-3 py-1 text-xs font-black text-[var(--app-primary-text)]">
                                                {{ formatMoney(insumo.price) }}
                                            </span>
                                        </button>

                                        <div
                                            v-if="filteredInsumos(detail).length === 0"
                                            class="px-4 py-6 text-center text-sm font-bold text-[var(--app-muted)]"
                                        >
                                            No se encontraron insumos.
                                        </div>
                                    </div>

                                    <p
                                        v-if="form.errors[`details.${index}.insumos_id`]"
                                        class="mt-1 text-sm font-bold text-red-500"
                                    >
                                        {{ form.errors[`details.${index}.insumos_id`] }}
                                    </p>
                                </div>

                                <div>
                                    <label class="text-sm font-black text-[var(--app-text)]">
                                        Cantidad
                                    </label>

                                    <input
                                        v-model="detail.amount"
                                        type="number"
                                        min="1"
                                        step="1"
                                        inputmode="numeric"
                                        class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-card)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                        @keydown="blockNegative"
                                        @input="sanitizeAmount(detail)"
                                        @blur="sanitizeAmount(detail)"
                                    />

                                    <p
                                        v-if="form.errors[`details.${index}.amount`]"
                                        class="mt-1 text-sm font-bold text-red-500"
                                    >
                                        {{ form.errors[`details.${index}.amount`] }}
                                    </p>
                                </div>

                                <div>
                                    <label class="text-sm font-black text-[var(--app-text)]">
                                        Precio unitario
                                    </label>

                                    <div class="mt-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-text)]">
                                        {{ formatMoney(detailUnitPrice(detail)) }}
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-black text-[var(--app-text)]">
                                        Subtotal
                                    </label>

                                    <div class="mt-2 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)]">
                                        {{ formatMoney(detailSubtotal(detail)) }}
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        title="Quitar insumo"
                                        class="rounded-xl bg-red-500/10 p-3 text-red-500 transition hover:bg-red-500/20 disabled:opacity-40"
                                        :disabled="form.details.length === 1"
                                        @click="removeDetail(index)"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6M14 11v6" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.details" class="mt-2 text-sm font-bold text-red-500">
                        {{ form.errors.details }}
                    </p>
                </section>

                <section class="flex flex-col gap-4 rounded-3xl border border-[var(--app-border)] bg-[var(--app-primary-soft)] p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Total de compra
                        </p>

                        <p class="mt-1 text-4xl font-black text-[var(--app-text)]">
                            {{ formatMoney(totalPrice) }}
                        </p>

                        <p class="mt-1 text-xs font-bold text-[var(--app-muted)]">
                            Se guardará fecha y hora automáticamente al registrar.
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-6 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90 disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <svg v-if="!form.processing" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z" />
                        </svg>

                        {{ form.processing ? 'Guardando...' : 'Registrar compra' }}
                    </button>
                </section>
            </form>
        </div>
    </SidebarLayout>
</template>
