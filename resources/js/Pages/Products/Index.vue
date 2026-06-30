<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    subCategories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category_id: '',
            sub_category_id: '',
            stock_status: '',
            per_page: '10',
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            products: 0,
            stock: 0,
            low_stock: 0,
            out_stock: 0,
        }),
    },
});

const page = usePage();

const modalOpen = ref(false);
const modalMode = ref('create');
const editingProduct = ref(null);
const imageModalOpen = ref(false);
const imageModalSrc = ref(null);
const imageModalTitle = ref('Vista previa');

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const subCategoryId = ref(props.filters.sub_category_id ?? '');
const stockStatus = ref(props.filters.stock_status ?? '');
const perPage = ref(props.filters.per_page ?? '10');

const tableLoading = ref(false);
const photoPreview = ref(null);

let searchTimeout = null;

const rows = computed(() => props.products?.data ?? []);
const meta = computed(() => props.products?.meta ?? {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
    sub_categories_id: '',
    name: '',
    price: '',
    amount: '',
    url_photo: null,
});

const filteredSubCategories = computed(() => {
    if (!categoryId.value) {
        return props.subCategories;
    }

    return props.subCategories.filter((subCategory) => {
        return String(subCategory.categories_id) === String(categoryId.value);
    });
});

const buildFilters = (pageNumber = null) => {
    const payload = {
        search: search.value,
        category_id: categoryId.value,
        sub_category_id: subCategoryId.value,
        stock_status: stockStatus.value,
        per_page: perPage.value,
    };

    if (pageNumber) {
        payload.page = pageNumber;
    }

    return payload;
};

const reloadList = (pageNumber = null) => {
    tableLoading.value = true;

    router.get(route('products.index'), buildFilters(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['products', 'filters', 'stats'],
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

watch(stockStatus, () => {
    reloadList();
});

watch(subCategoryId, () => {
    reloadList();
});

const categoryChanged = () => {
    subCategoryId.value = '';
    reloadList();
};

const clearFilters = () => {
    search.value = '';
    categoryId.value = '';
    subCategoryId.value = '';
    stockStatus.value = '';
    perPage.value = '10';

    reloadList();
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.url_photo = null;
    editingProduct.value = null;
    photoPreview.value = null;

    const input = document.getElementById('product_photo');

    if (input) {
        input.value = '';
    }
};

const openCreate = () => {
    modalMode.value = 'create';
    resetForm();
    modalOpen.value = true;
};

const openEdit = (product) => {
    modalMode.value = 'edit';
    editingProduct.value = product;

    form.sub_categories_id = product.sub_categories_id ?? '';
    form.name = product.name ?? '';
    form.price = product.price ?? 0;
    form.amount = product.amount ?? 0;
    form.url_photo = null;

    photoPreview.value = product.url_photo ?? null;
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

const handleProductPhoto = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        form.url_photo = null;
        photoPreview.value = modalMode.value === 'edit'
            ? editingProduct.value?.url_photo ?? null
            : null;
        return;
    }

    form.url_photo = file;
    photoPreview.value = URL.createObjectURL(file);
};

const blockNegative = (event) => {
    if (['-', '+', 'e', 'E'].includes(event.key)) {
        event.preventDefault();
    }
};

const sanitizeProductNumbers = () => {
    if (form.price === '' || form.price === null) {
        return;
    }

    if (Number(form.price) < 0 || Number.isNaN(Number(form.price))) {
        form.price = 0;
    }

    if (form.amount === '' || form.amount === null) {
        return;
    }

    if (Number(form.amount) < 0 || Number.isNaN(Number(form.amount))) {
        form.amount = 0;
    }
};

const submit = () => {
    sanitizeProductNumbers();

    if (modalMode.value === 'create') {
        form.post(route('products.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeModal();
                reloadList();
            },
        });

        return;
    }

    form.post(route('products.update', editingProduct.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
            reloadList();
        },
    });
};

const deleteProduct = (product) => {
    if (!confirm(`¿Eliminar el producto "${product.name}"?`)) {
        return;
    }

    tableLoading.value = true;

    router.delete(route('products.destroy', product.id), {
        preserveScroll: true,
        onSuccess: () => {
            reloadList();
        },
        onFinish: () => {
            tableLoading.value = false;
        },
    });
};

const openImageModal = (product) => {
    if (!product.url_photo) {
        return;
    }

    imageModalSrc.value = product.url_photo;
    imageModalTitle.value = product.name ?? 'Vista previa';
    imageModalOpen.value = true;
};

const closeImageModal = () => {
    imageModalOpen.value = false;
    imageModalSrc.value = null;
};

const exportProducts = (type) => {
    const params = new URLSearchParams(buildFilters()).toString();
    const routeName = {
        excel: 'products.export.excel',
        pdf: 'products.export.pdf',
        txt: 'products.export.txt',
    }[type];

    window.open(`${route(routeName)}?${params}`, '_blank');
};

const goToPage = (pageNumber) => {
    reloadList(pageNumber);
};

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};
</script>

<template>
    <Head title="Productos" />

    <SidebarLayout
        title="Productos"
        subtitle="Gestiona productos, precios, stock, categorías e imágenes"
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

            <section class="grid gap-4 md:grid-cols-4">
                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Productos</p>
                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">{{ stats.products }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Stock total</p>
                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">{{ stats.stock }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Stock bajo</p>
                    <p class="mt-2 text-4xl font-black text-yellow-600">{{ stats.low_stock }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Sin stock</p>
                    <p class="mt-2 text-4xl font-black text-red-500">{{ stats.out_stock }}</p>
                </article>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Gestión comercial
                        </p>
                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Inventario de productos
                        </h1>
                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Los cambios se reflejan en la tabla sin recargar la página completa.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportProducts('excel')"
                        >
                            Exportar Excel
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportProducts('pdf')"
                        >
                            Exportar PDF
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="exportProducts('txt')"
                        >
                            Exportar TXT
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90"
                            @click="openCreate"
                        >
                            Nuevo producto
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[1fr_220px_220px_180px_160px_160px] xl:items-end">
                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Buscar en tiempo real</label>
                        <div class="relative mt-2">
                            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Nombre, categoría o subcategoría"
                                class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] py-3 pl-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Categoría</label>
                        <select
                            v-model="categoryId"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            @change="categoryChanged"
                        >
                            <option value="">Todas</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Subcategoría</label>
                        <select
                            v-model="subCategoryId"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="">Todas</option>
                            <option v-for="subCategory in filteredSubCategories" :key="subCategory.id" :value="subCategory.id">
                                {{ subCategory.category_name }} / {{ subCategory.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Stock</label>
                        <select
                            v-model="stockStatus"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="">Todo</option>
                            <option value="available">Disponible</option>
                            <option value="low">Stock bajo</option>
                            <option value="out">Sin stock</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Mostrar</label>
                        <select
                            v-model="perPage"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Todos</option>
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
                        <p class="text-sm font-black text-[var(--app-text)]">Actualizando productos...</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-b border-[var(--app-border)] px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-[var(--app-text)]">Lista de productos</h2>
                        <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                            Mostrando {{ meta.from ?? 0 }} - {{ meta.to ?? 0 }} de {{ meta.total ?? 0 }}
                        </p>
                    </div>

                    <p class="text-sm font-black text-[var(--app-primary)]">
                        Cantidad del listado: {{ rows.length }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-left">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                <th class="px-6 py-4">Producto</th>
                                <th class="px-6 py-4">Categoría</th>
                                <th class="px-6 py-4">Precio</th>
                                <th class="px-6 py-4">Stock</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--app-border)]">
                            <tr v-for="product in rows" :key="product.id" class="transition hover:bg-[var(--app-surface-soft)]">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="product.url_photo"
                                            :src="product.url_photo"
                                            :alt="product.name"
                                            class="h-14 w-14 cursor-pointer rounded-2xl object-cover shadow-sm transition hover:scale-105"
                                            @click="openImageModal(product)"
                                        />
                                        <div v-else class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-sm font-black text-white shadow-sm">
                                            PR
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate font-black text-[var(--app-text)]">{{ product.name }}</p>
                                            <p class="truncate text-xs font-semibold text-[var(--app-muted)]">
                                                {{ product.sub_categorie?.name ?? 'Sin subcategoría' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-[var(--app-text)]">
                                        {{ product.sub_categorie?.categorie?.name ?? 'Sin categoría' }}
                                    </p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">
                                        {{ product.sub_categorie?.name ?? 'Sin subcategoría' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5 text-sm font-black text-[var(--app-text)]">
                                    {{ formatMoney(product.price) }}
                                </td>

                                <td class="px-6 py-5">
                                    <span
                                        class="rounded-xl px-3 py-1 text-xs font-black"
                                        :class="product.amount <= 0
                                            ? 'bg-red-500/10 text-red-500'
                                            : product.amount <= 5
                                                ? 'bg-yellow-500/10 text-yellow-600'
                                                : 'bg-green-500/10 text-green-600'"
                                    >
                                        {{ product.amount <= 0 ? 'Sin stock' : product.amount <= 5 ? `Bajo (${product.amount})` : product.amount }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            v-if="product.url_photo"
                                            type="button"
                                            title="Ver imagen"
                                            class="rounded-xl bg-[var(--app-surface-soft)] p-2 text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)]"
                                            @click="openImageModal(product)"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            title="Editar producto"
                                            class="rounded-xl bg-[var(--app-surface-soft)] p-2 text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)]"
                                            @click="openEdit(product)"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-9.193 9.193a4.5 4.5 0 01-1.897 1.13L7 17l.84-3.423a4.5 4.5 0 011.13-1.897l7.892-7.193z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5M5 21h14" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            title="Eliminar producto"
                                            class="rounded-xl bg-red-500/10 p-2 text-red-500 transition hover:bg-red-500/20"
                                            @click="deleteProduct(product)"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6M14 11v6" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <p class="text-lg font-black text-[var(--app-text)]">No hay productos registrados</p>
                                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                        Intenta cambiar filtros o crea un nuevo producto.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-[var(--app-border)] px-6 py-4">
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
                                {{ modalMode === 'create' ? 'Nuevo registro' : 'Editar producto' }}
                            </p>
                            <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ modalMode === 'create' ? 'Crear producto' : 'Actualizar datos' }}
                            </h2>
                            <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                Precio y cantidad no permiten valores negativos.
                            </p>
                        </div>

                        <button type="button" class="rounded-xl bg-[var(--app-card)] p-2 text-[var(--app-muted)] transition hover:text-[var(--app-primary)]" @click="closeModal">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div class="flex items-center gap-4 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                        <img v-if="photoPreview" :src="photoPreview" alt="Producto" class="h-24 w-24 rounded-2xl object-cover" />
                        <div v-else class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-2xl font-black text-white">
                            {{ form.name ? form.name.charAt(0).toUpperCase() : 'PR' }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <label class="text-sm font-black text-[var(--app-text)]">Imagen del producto</label>
                            <input
                                id="product_photo"
                                type="file"
                                accept="image/*"
                                class="mt-2 block w-full text-sm font-semibold text-[var(--app-muted)] file:mr-4 file:rounded-xl file:border-0 file:bg-[var(--app-primary)] file:px-4 file:py-2 file:text-sm file:font-black file:text-white"
                                @change="handleProductPhoto"
                            />
                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">JPG, PNG o WEBP. Máximo 4 MB.</p>
                            <p v-if="form.errors.url_photo" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.url_photo }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Subcategoría</label>
                        <select
                            v-model="form.sub_categories_id"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        >
                            <option value="">Selecciona una subcategoría</option>
                            <option v-for="subCategory in subCategories" :key="subCategory.id" :value="subCategory.id">
                                {{ subCategory.category_name }} / {{ subCategory.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.sub_categories_id" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.sub_categories_id }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Precio</label>
                            <input
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                @keydown="blockNegative"
                                @input="sanitizeProductNumbers"
                                @blur="sanitizeProductNumbers"
                            />
                            <p v-if="form.errors.price" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.price }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Cantidad</label>
                            <input
                                v-model="form.amount"
                                type="number"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                @keydown="blockNegative"
                                @input="sanitizeProductNumbers"
                                @blur="sanitizeProductNumbers"
                            />
                            <p v-if="form.errors.amount" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.amount }}</p>
                        </div>
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
            v-if="imageModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeImageModal"
        >
            <div class="w-full max-w-3xl overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl">
                <div class="flex items-center justify-between border-b border-[var(--app-border)] px-6 py-4">
                    <p class="text-lg font-black text-[var(--app-text)]">{{ imageModalTitle }}</p>
                    <button type="button" class="rounded-xl bg-[var(--app-surface-soft)] p-2 text-[var(--app-muted)] hover:text-[var(--app-primary)]" @click="closeImageModal">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="bg-black/5 p-4">
                    <img :src="imageModalSrc" alt="Vista previa" class="mx-auto max-h-[75vh] rounded-2xl object-contain" />
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>
