<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    suppliers: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            per_page: '10',
        }),
    },
});

const page = usePage();

const modalOpen = ref(false);
const modalMode = ref('create');
const editingSupplier = ref(null);
const supplierToDelete = ref(null);
const tableLoading = ref(false);

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? '10');

const form = useForm({
    _method: 'post',
    name: '',
    description: '',
    telephone: '',
    photo: null,
});

let searchTimeout = null;

const rows = computed(() => props.suppliers?.data ?? []);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const totalSuppliers = computed(() => {
    return props.suppliers?.total ?? rows.value.length ?? 0;
});

const currentPage = computed(() => props.suppliers?.current_page ?? 1);
const lastPage = computed(() => props.suppliers?.last_page ?? 1);
const fromRow = computed(() => props.suppliers?.from ?? 0);
const toRow = computed(() => props.suppliers?.to ?? 0);

const buildFilters = (pageNumber = null) => {
    const payload = {
        search: search.value,
        per_page: perPage.value,
    };

    if (pageNumber) {
        payload.page = pageNumber;
    }

    return payload;
};

const reloadList = (pageNumber = null) => {
    tableLoading.value = true;

    router.get(route('insumos.suppliers.index'), buildFilters(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['suppliers', 'filters'],
        onFinish: () => {
            tableLoading.value = false;
        },
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        reloadList();
    }, 400);
});

watch(perPage, () => {
    reloadList();
});

const clearFilters = () => {
    search.value = '';
    perPage.value = '10';

    reloadList();
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form._method = 'post';
    form.photo = null;
    editingSupplier.value = null;

    const input = document.getElementById('supplier_photo');

    if (input) {
        input.value = '';
    }
};

const openCreate = () => {
    modalMode.value = 'create';
    resetForm();
    modalOpen.value = true;
};

const openEdit = (supplier) => {
    modalMode.value = 'edit';
    editingSupplier.value = supplier;

    form._method = 'put';
    form.name = supplier.name ?? '';
    form.description = supplier.description ?? '';
    form.telephone = supplier.telephone ?? '';
    form.photo = null;

    form.clearErrors();
    modalOpen.value = true;
};

const closeModal = () => {
    if (form.processing) {
        return;
    }

    modalOpen.value = false;
    resetForm();
};

const submit = () => {
    if (modalMode.value === 'create') {
        form._method = 'post';

        form.post(route('insumos.suppliers.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeModal();
                reloadList();
            },
        });

        return;
    }

    form._method = 'put';

    form.post(route('insumos.suppliers.update', editingSupplier.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
            reloadList();
        },
    });
};

const confirmDelete = (supplier) => {
    supplierToDelete.value = supplier;
};

const closeDeleteModal = () => {
    supplierToDelete.value = null;
};

const destroySupplier = () => {
    if (!supplierToDelete.value) {
        return;
    }

    tableLoading.value = true;

    router.delete(route('insumos.suppliers.destroy', supplierToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            supplierToDelete.value = null;
            reloadList();
        },
        onFinish: () => {
            tableLoading.value = false;
        },
    });
};

const handlePhoto = (event) => {
    form.photo = event.target.files?.[0] ?? null;
};

const exportSuppliers = (type) => {
    const params = new URLSearchParams(buildFilters()).toString();

    const routeName = {
        excel: 'insumos.suppliers.export.excel',
        pdf: 'insumos.suppliers.export.pdf',
        txt: 'insumos.suppliers.export.txt',
    }[type];

    window.open(`${route(routeName)}?${params}`, '_blank');
};

const goToPage = (pageNumber) => {
    reloadList(pageNumber);
};
</script>

<template>
    <Head title="Proveedores de insumos" />

    <SidebarLayout
        title="Proveedores de insumos"
        subtitle="Administra proveedores para las compras y reposición de stock"
    >
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

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Proveedores registrados
                    </p>

                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">
                        {{ totalSuppliers }}
                    </p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Mostrando
                    </p>

                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">
                        {{ rows.length }}
                    </p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">
                        Permiso
                    </p>

                    <p class="mt-2 text-xl font-black text-[var(--app-primary)]">
                        Master / Administrador
                    </p>
                </article>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Gestión de insumos
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Proveedores
                        </h1>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Los filtros se aplican en tiempo real sin recargar toda la página.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportSuppliers('excel')"
                        >
                            Exportar Excel
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportSuppliers('pdf')"
                        >
                            Exportar PDF
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportSuppliers('txt')"
                        >
                            Exportar TXT
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90"
                            @click="openCreate"
                        >
                            Nuevo proveedor
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[1fr_160px_160px] xl:items-end">
                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">
                            Buscar en tiempo real
                        </label>

                        <div class="relative mt-2">
                            <svg
                                class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                                />
                            </svg>

                            <input
                                v-model="search"
                                type="text"
                                placeholder="Nombre, descripción o teléfono"
                                class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] py-3 pl-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">
                            Mostrar
                        </label>

                        <select
                            v-model="perPage"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)]"
                        @click="clearFilters"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </section>

            <section class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
                <div
                    v-if="tableLoading"
                    class="absolute inset-0 z-30 flex items-center justify-center bg-[var(--app-card)]/70 backdrop-blur-sm"
                >
                    <div class="flex flex-col items-center gap-3 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] px-7 py-6 shadow-xl">
                        <div class="h-12 w-12 animate-spin rounded-full border-4 border-[var(--app-primary-soft)] border-t-[var(--app-primary)]"></div>
                        <p class="text-sm font-black text-[var(--app-text)]">
                            Actualizando proveedores...
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-b border-[var(--app-border)] px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-[var(--app-text)]">
                            Lista de proveedores
                        </h2>

                        <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                            Mostrando {{ fromRow }} - {{ toRow }} de {{ totalSuppliers }}
                        </p>
                    </div>

                    <p class="text-sm font-black text-[var(--app-primary)]">
                        Cantidad del listado: {{ rows.length }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                <th class="px-6 py-4">Proveedor</th>
                                <th class="px-6 py-4">Descripción</th>
                                <th class="px-6 py-4">Teléfono</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--app-border)]">
                            <tr
                                v-for="supplier in rows"
                                :key="supplier.id"
                                class="transition hover:bg-[var(--app-surface-soft)]"
                            >
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="supplier.url_photo"
                                            :src="supplier.url_photo"
                                            :alt="supplier.name"
                                            class="h-14 w-14 rounded-2xl object-cover shadow-sm"
                                        />

                                        <div
                                            v-else
                                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-sm font-black text-white shadow-sm"
                                        >
                                            PR
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate font-black text-[var(--app-text)]">
                                                {{ supplier.name }}
                                            </p>

                                            <p class="truncate text-xs font-semibold text-[var(--app-muted)]">
                                                Proveedor de insumos
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5 text-sm font-semibold text-[var(--app-muted)]">
                                    {{ supplier.description }}
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ supplier.telephone }}
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            title="Editar proveedor"
                                            class="rounded-xl bg-[var(--app-surface-soft)] p-2 text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)]"
                                            @click="openEdit(supplier)"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-9.193 9.193a4.5 4.5 0 01-1.897 1.13L7 17l.84-3.423a4.5 4.5 0 011.13-1.897l7.892-7.193z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5M5 21h14" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            title="Eliminar proveedor"
                                            class="rounded-xl bg-red-500/10 p-2 text-red-500 transition hover:bg-red-500/20"
                                            @click="confirmDelete(supplier)"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6M14 11v6" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <p class="text-lg font-black text-[var(--app-text)]">
                                        No hay proveedores registrados
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                        Intenta cambiar filtros o registra un nuevo proveedor.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="lastPage > 1"
                    class="flex items-center justify-between border-t border-[var(--app-border)] px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] disabled:opacity-40"
                        :disabled="currentPage <= 1"
                        @click="goToPage(currentPage - 1)"
                    >
                        Anterior
                    </button>

                    <p class="text-sm font-black text-[var(--app-muted)]">
                        Página {{ currentPage }} de {{ lastPage }}
                    </p>

                    <button
                        type="button"
                        class="rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-black text-[var(--app-muted)] disabled:opacity-40"
                        :disabled="currentPage >= lastPage"
                        @click="goToPage(currentPage + 1)"
                    >
                        Siguiente
                    </button>
                </div>
            </section>
        </div>

        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeModal"
        >
            <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                                {{ modalMode === 'create' ? 'Nuevo registro' : 'Editar proveedor' }}
                            </p>

                            <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ modalMode === 'create' ? 'Crear proveedor' : 'Actualizar datos' }}
                            </h2>

                            <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                Completa los datos del proveedor de insumos.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl bg-[var(--app-card)] p-2 text-[var(--app-muted)] transition hover:text-[var(--app-primary)]"
                            @click="closeModal"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">
                            Nombre
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />

                        <p v-if="form.errors.name" class="mt-1 text-sm font-bold text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">
                            Descripción
                        </label>

                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        ></textarea>

                        <p v-if="form.errors.description" class="mt-1 text-sm font-bold text-red-500">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">
                            Teléfono
                        </label>

                        <input
                            v-model="form.telephone"
                            type="number"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />

                        <p v-if="form.errors.telephone" class="mt-1 text-sm font-bold text-red-500">
                            {{ form.errors.telephone }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">
                            Foto del proveedor
                        </label>

                        <input
                            id="supplier_photo"
                            type="file"
                            accept="image/*"
                            class="mt-2 block w-full text-sm font-semibold text-[var(--app-muted)] file:mr-4 file:rounded-xl file:border-0 file:bg-[var(--app-primary)] file:px-4 file:py-2 file:text-sm file:font-black file:text-white"
                            @change="handlePhoto"
                        />

                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                            JPG, PNG o WEBP. Máximo 2 MB.
                        </p>

                        <p v-if="form.errors.photo" class="mt-1 text-sm font-bold text-red-500">
                            {{ form.errors.photo }}
                        </p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            class="flex-1 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-muted)]"
                            @click="closeModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="flex-1 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Guardando...' : modalMode === 'create' ? 'Guardar' : 'Actualizar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="supplierToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeDeleteModal"
        >
            <div class="w-full max-w-md rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <h3 class="text-xl font-black text-[var(--app-text)]">
                    Eliminar proveedor
                </h3>

                <p class="mt-2 text-sm font-semibold text-[var(--app-muted)]">
                    ¿Seguro que deseas eliminar a
                    <strong>{{ supplierToDelete.name }}</strong>?
                </p>

                <p class="mt-2 text-xs font-bold text-red-500">
                    Si tiene compras registradas, el sistema no permitirá eliminarlo.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] px-5 py-3 text-sm font-black text-[var(--app-muted)]"
                        @click="closeDeleteModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl bg-red-600 px-5 py-3 text-sm font-black text-white"
                        @click="destroySupplier"
                    >
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
