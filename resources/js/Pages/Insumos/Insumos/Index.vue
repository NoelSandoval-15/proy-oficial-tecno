<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    insumos: {
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
});

const page = usePage();

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingInsumo = ref(null);
const deletingInsumo = ref(null);

const filterForm = useForm({
    search: props.filters.search || '',
    stock: props.filters.stock || '',
});

const form = useForm({
    name: '',
    amount: '',
    price: '',
});

const rows = computed(() => props.insumos?.data || []);

const flashSuccess = computed(() => page.props.flash?.success || null);
const flashError = computed(() => page.props.flash?.error || null);

const money = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const stockClass = (insumo) => {
    const amount = Number(insumo.amount || 0);

    if (amount <= 0) {
        return 'border-red-500/30 bg-red-500/10 text-red-700';
    }

    if (amount <= 5) {
        return 'border-amber-500/30 bg-amber-500/10 text-amber-700';
    }

    return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700';
};

const stockDotClass = (insumo) => {
    const amount = Number(insumo.amount || 0);

    if (amount <= 0) {
        return 'bg-red-500';
    }

    if (amount <= 5) {
        return 'bg-amber-500';
    }

    return 'bg-emerald-500';
};

const stockLabel = (insumo) => {
    const amount = Number(insumo.amount || 0);

    if (amount <= 0) {
        return 'Sin stock';
    }

    if (amount <= 5) {
        return 'Stock bajo';
    }

    return 'Disponible';
};

const inventoryValue = (insumo) => {
    return Number(insumo.amount || 0) * Number(insumo.price || 0);
};

const applyFilters = () => {
    router.get(route('insumos.items.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.stock = '';

    applyFilters();
};

const openCreateModal = () => {
    editingInsumo.value = null;

    form.reset();
    form.clearErrors();

    form.name = '';
    form.amount = '';
    form.price = '';

    showModal.value = true;
};

const openEditModal = (insumo) => {
    editingInsumo.value = insumo;

    form.clearErrors();

    form.name = insumo.name;
    form.amount = insumo.amount;
    form.price = insumo.price;

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingInsumo.value = null;
    form.clearErrors();
};

const submitForm = () => {
    if (editingInsumo.value) {
        form.put(route('insumos.items.update', editingInsumo.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });

        return;
    }

    form.post(route('insumos.items.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const openDeleteModal = (insumo) => {
    deletingInsumo.value = insumo;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    deletingInsumo.value = null;
    showDeleteModal.value = false;
};

const deleteInsumo = () => {
    if (!deletingInsumo.value) return;

    router.delete(route('insumos.items.destroy', deletingInsumo.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="Catálogo de insumos" />

    <SidebarLayout
        title="Catálogo de insumos"
        subtitle="Registra, edita y controla los insumos base de la churrasquería."
    >
        <div class="space-y-6 pt-24 lg:pt-10">
            <div
                v-if="flashSuccess || flashError"
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
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Insumos registrados
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ summary.total || 0 }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        Catálogo general
                    </p>
                </article>

                <article class="rounded-[1.4rem] border border-emerald-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Disponibles
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ summary.available || 0 }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        Stock suficiente
                    </p>
                </article>

                <article class="rounded-[1.4rem] border border-amber-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Stock bajo
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ summary.low_stock || 0 }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        Requieren reposición
                    </p>
                </article>

                <article class="rounded-[1.4rem] border border-red-500/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Sin stock
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ summary.empty_stock || 0 }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        Agotados
                    </p>
                </article>

                <article class="rounded-[1.4rem] border border-[var(--app-primary)]/20 bg-[var(--app-card)] p-5 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Valor inventario
                    </p>

                    <p class="mt-2 text-2xl font-black text-[var(--app-text)]">
                        {{ money(summary.inventory_value) }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                        Cantidad x precio
                    </p>
                </article>
            </section>

            <section class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-4 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-lg font-black text-[var(--app-text)]">
                            Insumos
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Administra los ingredientes, carnes, bebidas y materiales usados en compras y producción.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90"
                        @click="openCreateModal"
                    >
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                        </svg>
                        Nuevo insumo
                    </button>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-[1fr_220px_auto_auto]">
                    <input
                        v-model="filterForm.search"
                        type="text"
                        placeholder="Buscar insumo por nombre"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]"
                        @keyup.enter="applyFilters"
                    />

                    <select
                        v-model="filterForm.stock"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition focus:border-[var(--app-primary)]"
                    >
                        <option value="">Todos los stocks</option>
                        <option value="low">Stock bajo</option>
                        <option value="empty">Sin stock</option>
                    </select>

                    <button
                        type="button"
                        class="rounded-xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90"
                        @click="applyFilters"
                    >
                        Filtrar
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                        @click="resetFilters"
                    >
                        Limpiar
                    </button>
                </div>
            </section>

            <section class="grid gap-3">
                <article
                    v-for="insumo in rows"
                    :key="insumo.id"
                    class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="grid gap-4 xl:grid-cols-[1fr_260px] xl:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="[
                                        'h-2.5 w-2.5 rounded-full',
                                        stockDotClass(insumo)
                                    ]"
                                ></span>

                                <p class="text-lg font-black text-[var(--app-text)]">
                                    {{ insumo.name }}
                                </p>

                                <span
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs font-black',
                                        stockClass(insumo)
                                    ]"
                                >
                                    {{ stockLabel(insumo) }}
                                </span>
                            </div>

                            <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                                Insumo registrado en el catálogo principal.
                            </p>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Stock actual
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ insumo.amount }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Precio ref.
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ money(insumo.price) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-[var(--app-surface-soft)] px-4 py-3">
                                    <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                        Valor
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                        {{ money(inventoryValue(insumo)) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                                <p class="text-xs font-black uppercase text-[var(--app-muted)]">
                                    Control rápido
                                </p>

                                <p class="mt-1 text-2xl font-black text-[var(--app-text)]">
                                    {{ money(insumo.price) }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                    Precio unitario de referencia
                                </p>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2 rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] p-2">
                                <button
                                    type="button"
                                    title="Editar insumo"
                                    class="flex h-11 w-11 items-center justify-center rounded-xl border border-blue-500/30 bg-blue-500/10 text-blue-700 transition hover:bg-blue-500/20"
                                    @click="openEditModal(insumo)"
                                >
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3z" />
                                        <path d="M13.5 7.5l3 3" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    title="Eliminar insumo"
                                    class="flex h-11 w-11 items-center justify-center rounded-xl border border-red-500/30 bg-red-500/10 text-red-700 transition hover:bg-red-500/20"
                                    @click="openDeleteModal(insumo)"
                                >
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 7h16" stroke-linecap="round" />
                                        <path d="M10 11v6M14 11v6" stroke-linecap="round" />
                                        <path d="M6 7l1 14h10l1-14" />
                                        <path d="M9 7V4h6v3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>

                <article
                    v-if="rows.length === 0"
                    class="rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-10 text-center shadow-sm"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--app-surface-soft)] text-xl font-black text-[var(--app-muted)]">
                        0
                    </div>

                    <p class="mt-4 text-base font-black text-[var(--app-text)]">
                        No hay insumos registrados
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Registra tus insumos base para poder usarlos en compras y movimientos.
                    </p>

                    <button
                        type="button"
                        class="mt-5 rounded-xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90"
                        @click="openCreateModal"
                    >
                        Crear primer insumo
                    </button>
                </article>
            </section>

            <div
                v-if="insumos.links?.length"
                class="flex flex-wrap items-center justify-between gap-3 rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-4"
            >
                <p class="text-sm font-semibold text-[var(--app-muted)]">
                    Mostrando {{ insumos.from || 0 }} - {{ insumos.to || 0 }} de {{ insumos.total || 0 }}
                </p>

                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in insumos.links"
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
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-3 py-4 backdrop-blur-sm"
        >
            <div class="flex max-h-[92vh] w-[min(94vw,540px)] flex-col overflow-hidden rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-start justify-between border-b border-[var(--app-border)] px-5 py-4">
                    <div>
                        <p class="text-xl font-black text-[var(--app-text)]">
                            {{ editingInsumo ? 'Editar insumo' : 'Nuevo insumo' }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            {{ editingInsumo ? 'Actualiza los datos del insumo.' : 'Registra un nuevo insumo base.' }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-xl px-3 py-2 text-xl font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                        @click="closeModal"
                    >
                        ×
                    </button>
                </div>

                <form class="space-y-4 overflow-y-auto p-5" @submit.prevent="submitForm">
                    <div>
                        <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                            Nombre del insumo
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Ej: Costilla de cerdo, carbón, sal parrillera"
                            class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]"
                        />

                        <p v-if="form.errors.name" class="mt-1 text-xs font-bold text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                                Cantidad inicial
                            </label>

                            <input
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                                class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]"
                            />

                            <p v-if="form.errors.amount" class="mt-1 text-xs font-bold text-red-600">
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-black text-[var(--app-text)]">
                                Precio unitario
                            </label>

                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-input)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] outline-none transition placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)]"
                            />

                            <p v-if="form.errors.price" class="mt-1 text-xs font-bold text-red-600">
                                {{ form.errors.price }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-[1.2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-[var(--app-muted)]">
                            Vista previa
                        </p>

                        <div class="mt-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-black text-[var(--app-text)]">
                                    {{ form.name || 'Nombre del insumo' }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                    Cantidad: {{ form.amount || 0 }}
                                </p>
                            </div>

                            <p class="text-lg font-black text-[var(--app-text)]">
                                {{ money(form.price) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                            @click="closeModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="rounded-xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white transition hover:opacity-90 disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Guardando...' : editingInsumo ? 'Actualizar insumo' : 'Registrar insumo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-3 py-4 backdrop-blur-sm"
        >
            <div class="w-[min(94vw,440px)] rounded-[1.4rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-500/10 text-red-700">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 9v4" stroke-linecap="round" />
                        <path d="M12 17h.01" stroke-linecap="round" />
                        <path d="M10.2 4.5 2.8 18a2 2 0 0 0 1.8 3h14.8a2 2 0 0 0 1.8-3L13.8 4.5a2 2 0 0 0-3.6 0z" />
                    </svg>
                </div>

                <p class="mt-4 text-xl font-black text-[var(--app-text)]">
                    Eliminar insumo
                </p>

                <p class="mt-2 text-sm font-semibold leading-6 text-[var(--app-muted)]">
                    ¿Seguro que deseas eliminar
                    <strong class="text-[var(--app-text)]">{{ deletingInsumo?.name }}</strong>?
                    Si el insumo ya fue usado en compras o movimientos, el sistema no permitirá eliminarlo.
                </p>

                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-text)] transition hover:border-[var(--app-primary)]"
                        @click="closeDeleteModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="rounded-xl bg-red-600 px-5 py-3 text-sm font-black text-white transition hover:bg-red-700"
                        @click="deleteInsumo"
                    >
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
