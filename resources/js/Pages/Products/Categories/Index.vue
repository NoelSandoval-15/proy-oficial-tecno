<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
    categoryOptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            per_page: '10',
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            categories: 0,
            sub_categories: 0,
            with_products: 0,
        }),
    },
});

const page = usePage();

const categoryModalOpen = ref(false);
const categoryModalMode = ref('create');
const editingCategory = ref(null);

const subCategoryModalOpen = ref(false);
const subCategoryModalMode = ref('create');
const editingSubCategory = ref(null);
const photoPreview = ref(null);

const imageModalOpen = ref(false);
const imageModalSrc = ref(null);
const imageModalTitle = ref('Vista previa');

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? '10');
const tableLoading = ref(false);

let searchTimeout = null;

const rows = computed(() => props.categories?.data ?? []);
const meta = computed(() => props.categories?.meta ?? {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const categoryForm = useForm({
    name: '',
});

const subCategoryForm = useForm({
    categories_id: '',
    name: '',
    url_photo: null,
});

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

    router.get(route('products.categories.index'), buildFilters(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['categories', 'filters', 'stats', 'categoryOptions'],
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

const resetCategoryForm = () => {
    categoryForm.reset();
    categoryForm.clearErrors();
    editingCategory.value = null;
};

const openCreateCategory = () => {
    categoryModalMode.value = 'create';
    resetCategoryForm();
    categoryModalOpen.value = true;
};

const openEditCategory = (category) => {
    categoryModalMode.value = 'edit';
    editingCategory.value = category;
    categoryForm.name = category.name ?? '';
    categoryForm.clearErrors();
    categoryModalOpen.value = true;
};

const closeCategoryModal = () => {
    if (categoryForm.processing) {
        return;
    }

    categoryModalOpen.value = false;
    resetCategoryForm();
};

const submitCategory = () => {
    if (categoryModalMode.value === 'create') {
        categoryForm.post(route('products.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeCategoryModal();
                reloadList();
            },
        });

        return;
    }

    categoryForm.put(route('products.categories.update', editingCategory.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCategoryModal();
            reloadList();
        },
    });
};

const deleteCategory = (category) => {
    if (!confirm(`¿Eliminar la categoría "${category.name}"?`)) {
        return;
    }

    tableLoading.value = true;

    router.delete(route('products.categories.destroy', category.id), {
        preserveScroll: true,
        onSuccess: () => {
            reloadList();
        },
        onFinish: () => {
            tableLoading.value = false;
        },
    });
};

const resetSubCategoryForm = () => {
    subCategoryForm.reset();
    subCategoryForm.clearErrors();
    subCategoryForm.url_photo = null;
    editingSubCategory.value = null;
    photoPreview.value = null;

    const input = document.getElementById('sub_category_photo');

    if (input) {
        input.value = '';
    }
};

const openCreateSubCategory = (category = null) => {
    subCategoryModalMode.value = 'create';
    resetSubCategoryForm();
    subCategoryForm.categories_id = category?.id ?? '';
    subCategoryModalOpen.value = true;
};

const openEditSubCategory = (subCategory) => {
    subCategoryModalMode.value = 'edit';
    editingSubCategory.value = subCategory;
    subCategoryForm.categories_id = subCategory.categories_id ?? '';
    subCategoryForm.name = subCategory.name ?? '';
    subCategoryForm.url_photo = null;
    photoPreview.value = subCategory.url_photo ?? null;
    subCategoryForm.clearErrors();
    subCategoryModalOpen.value = true;
};

const closeSubCategoryModal = () => {
    if (subCategoryForm.processing) {
        return;
    }

    subCategoryModalOpen.value = false;
    resetSubCategoryForm();
};

const handleSubCategoryPhoto = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        subCategoryForm.url_photo = null;
        photoPreview.value = subCategoryModalMode.value === 'edit'
            ? editingSubCategory.value?.url_photo ?? null
            : null;
        return;
    }

    subCategoryForm.url_photo = file;
    photoPreview.value = URL.createObjectURL(file);
};

const submitSubCategory = () => {
    if (subCategoryModalMode.value === 'create') {
        subCategoryForm.post(route('products.sub-categories.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeSubCategoryModal();
                reloadList();
            },
        });

        return;
    }

    subCategoryForm.post(route('products.sub-categories.update', editingSubCategory.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeSubCategoryModal();
            reloadList();
        },
    });
};

const deleteSubCategory = (subCategory) => {
    if (!confirm(`¿Eliminar la subcategoría "${subCategory.name}"?`)) {
        return;
    }

    tableLoading.value = true;

    router.delete(route('products.sub-categories.destroy', subCategory.id), {
        preserveScroll: true,
        onSuccess: () => {
            reloadList();
        },
        onFinish: () => {
            tableLoading.value = false;
        },
    });
};

const openImageModal = (subCategory) => {
    if (!subCategory.url_photo) {
        return;
    }

    imageModalSrc.value = subCategory.url_photo;
    imageModalTitle.value = subCategory.name ?? 'Vista previa';
    imageModalOpen.value = true;
};

const closeImageModal = () => {
    imageModalOpen.value = false;
    imageModalSrc.value = null;
};

const exportCategories = (type) => {
    const params = new URLSearchParams(buildFilters()).toString();
    const routeName = {
        excel: 'products.categories.export.excel',
        pdf: 'products.categories.export.pdf',
        txt: 'products.categories.export.txt',
    }[type];

    window.open(`${route(routeName)}?${params}`, '_blank');
};

const goToPage = (pageNumber) => {
    reloadList(pageNumber);
};
</script>

<template>
    <Head title="Categorías" />

    <SidebarLayout
        title="Categorías"
        subtitle="Gestiona categorías y subcategorías de productos"
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
                    <p class="text-sm font-bold text-[var(--app-muted)]">Categorías</p>
                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">{{ stats.categories }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Subcategorías</p>
                    <p class="mt-2 text-4xl font-black text-[var(--app-text)]">{{ stats.sub_categories }}</p>
                </article>

                <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                    <p class="text-sm font-bold text-[var(--app-muted)]">Subcategorías con productos</p>
                    <p class="mt-2 text-4xl font-black text-[var(--app-primary)]">{{ stats.with_products }}</p>
                </article>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                            Catálogo
                        </p>
                        <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                            Categorías y subcategorías
                        </h1>
                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Los cambios se actualizan en la tabla sin recargar la página completa.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
                        <button type="button" class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]" @click="exportCategories('excel')">
                            Exportar Excel
                        </button>

                        <button type="button" class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]" @click="exportCategories('pdf')">
                            Exportar PDF
                        </button>

                        <button type="button" class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]" @click="exportCategories('txt')">
                            Exportar TXT
                        </button>

                        <button type="button" class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]" @click="openCreateSubCategory()">
                            Nueva subcategoría
                        </button>

                        <button type="button" class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90" @click="openCreateCategory">
                            Nueva categoría
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <div class="grid gap-4 lg:grid-cols-[1fr_220px_160px] lg:items-end">
                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Buscar en tiempo real</label>
                        <div class="relative mt-2">
                            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Categoría o subcategoría"
                                class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] py-3 pl-12 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                        </div>
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
                        <p class="text-sm font-black text-[var(--app-text)]">Actualizando categorías...</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-b border-[var(--app-border)] px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-[var(--app-text)]">Lista de categorías</h2>
                        <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                            Mostrando {{ meta.from ?? 0 }} - {{ meta.to ?? 0 }} de {{ meta.total ?? 0 }}
                        </p>
                    </div>

                    <p class="text-sm font-black text-[var(--app-primary)]">
                        Cantidad del listado: {{ rows.length }}
                    </p>
                </div>

                <div class="space-y-4 p-6">
                    <article
                        v-for="category in rows"
                        :key="category.id"
                        class="rounded-[1.6rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5 transition hover:shadow-sm"
                    >
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-xl font-black text-[var(--app-text)]">{{ category.name }}</h3>
                                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                    {{ category.sub_categories_count }} subcategorías
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" title="Agregar subcategoría" class="rounded-xl bg-[var(--app-card)] p-2 text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]" @click="openCreateSubCategory(category)">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" />
                                    </svg>
                                </button>

                                <button type="button" title="Editar categoría" class="rounded-xl bg-[var(--app-card)] p-2 text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)]" @click="openEditCategory(category)">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-9.193 9.193a4.5 4.5 0 01-1.897 1.13L7 17l.84-3.423a4.5 4.5 0 011.13-1.897l7.892-7.193z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5M5 21h14" />
                                    </svg>
                                </button>

                                <button type="button" title="Eliminar categoría" class="rounded-xl bg-red-500/10 p-2 text-red-500 transition hover:bg-red-500/20" @click="deleteCategory(category)">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6M14 11v6" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="category.sub_categories?.length" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <div
                                v-for="subCategory in category.sub_categories"
                                :key="subCategory.id"
                                class="flex items-center gap-3 rounded-2xl bg-[var(--app-card)] p-3"
                            >
                                <img
                                    v-if="subCategory.url_photo"
                                    :src="subCategory.url_photo"
                                    :alt="subCategory.name"
                                    class="h-14 w-14 cursor-pointer rounded-2xl object-cover shadow-sm transition hover:scale-105"
                                    @click="openImageModal(subCategory)"
                                />
                                <div v-else class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-sm font-black text-white">
                                    SC
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-black text-[var(--app-text)]">{{ subCategory.name }}</p>
                                    <p class="text-xs font-semibold text-[var(--app-muted)]">{{ subCategory.products_count }} productos</p>
                                </div>

                                <div class="flex gap-1">
                                    <button v-if="subCategory.url_photo" type="button" title="Ver imagen" class="rounded-xl p-2 text-[var(--app-primary)] hover:bg-[var(--app-primary-soft)]" @click="openImageModal(subCategory)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                        </svg>
                                    </button>

                                    <button type="button" title="Editar subcategoría" class="rounded-xl p-2 text-[var(--app-text)] hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)]" @click="openEditSubCategory(subCategory)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-9.193 9.193a4.5 4.5 0 01-1.897 1.13L7 17l.84-3.423a4.5 4.5 0 011.13-1.897l7.892-7.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5M5 21h14" />
                                        </svg>
                                    </button>

                                    <button type="button" title="Eliminar subcategoría" class="rounded-xl p-2 text-red-500 hover:bg-red-500/10" @click="deleteSubCategory(subCategory)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6M14 11v6" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="mt-5 rounded-2xl border border-dashed border-[var(--app-border)] p-5 text-sm font-bold text-[var(--app-muted)]">
                            Esta categoría todavía no tiene subcategorías.
                        </div>
                    </article>

                    <div v-if="rows.length === 0" class="rounded-[1.6rem] border border-dashed border-[var(--app-border)] p-10 text-center">
                        <p class="text-lg font-black text-[var(--app-text)]">No hay categorías registradas</p>
                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">Intenta cambiar filtros o crea una nueva categoría.</p>
                    </div>
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

        <div v-if="categoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm" @mousedown.self="closeCategoryModal">
            <div class="w-full max-w-xl rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                                {{ categoryModalMode === 'create' ? 'Nueva categoría' : 'Editar categoría' }}
                            </p>
                            <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ categoryModalMode === 'create' ? 'Crear categoría' : 'Actualizar categoría' }}
                            </h2>
                        </div>

                        <button type="button" class="rounded-xl bg-[var(--app-card)] p-2 text-[var(--app-muted)] transition hover:text-[var(--app-primary)]" @click="closeCategoryModal">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitCategory">
                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Nombre</label>
                        <input v-model="categoryForm.name" type="text" class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                        <p v-if="categoryForm.errors.name" class="mt-1 text-sm font-bold text-red-500">{{ categoryForm.errors.name }}</p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" class="flex-1 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-muted)]" @click="closeCategoryModal">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white disabled:opacity-60" :disabled="categoryForm.processing">
                            {{ categoryForm.processing ? 'Guardando...' : categoryModalMode === 'create' ? 'Guardar' : 'Actualizar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="subCategoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm" @mousedown.self="closeSubCategoryModal">
            <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                                {{ subCategoryModalMode === 'create' ? 'Nueva subcategoría' : 'Editar subcategoría' }}
                            </p>
                            <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ subCategoryModalMode === 'create' ? 'Crear subcategoría' : 'Actualizar subcategoría' }}
                            </h2>
                        </div>

                        <button type="button" class="rounded-xl bg-[var(--app-card)] p-2 text-[var(--app-muted)] transition hover:text-[var(--app-primary)]" @click="closeSubCategoryModal">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitSubCategory">
                    <div class="flex items-center gap-4 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                        <img v-if="photoPreview" :src="photoPreview" alt="Subcategoría" class="h-24 w-24 rounded-2xl object-cover" />
                        <div v-else class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-2xl font-black text-white">SC</div>

                        <div class="min-w-0 flex-1">
                            <label class="text-sm font-black text-[var(--app-text)]">Imagen</label>
                            <input
                                id="sub_category_photo"
                                type="file"
                                accept="image/*"
                                class="mt-2 block w-full text-sm font-semibold text-[var(--app-muted)] file:mr-4 file:rounded-xl file:border-0 file:bg-[var(--app-primary)] file:px-4 file:py-2 file:text-sm file:font-black file:text-white"
                                @change="handleSubCategoryPhoto"
                            />
                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">JPG, PNG o WEBP. Máximo 4 MB.</p>
                            <p v-if="subCategoryForm.errors.url_photo" class="mt-1 text-sm font-bold text-red-500">{{ subCategoryForm.errors.url_photo }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Categoría</label>
                        <select v-model="subCategoryForm.categories_id" class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]">
                            <option value="">Selecciona una categoría</option>
                            <option v-for="category in categoryOptions" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="subCategoryForm.errors.categories_id" class="mt-1 text-sm font-bold text-red-500">{{ subCategoryForm.errors.categories_id }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Nombre</label>
                        <input v-model="subCategoryForm.name" type="text" class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                        <p v-if="subCategoryForm.errors.name" class="mt-1 text-sm font-bold text-red-500">{{ subCategoryForm.errors.name }}</p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" class="flex-1 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-5 py-3 text-sm font-black text-[var(--app-muted)]" @click="closeSubCategoryModal">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white disabled:opacity-60" :disabled="subCategoryForm.processing">
                            {{ subCategoryForm.processing ? 'Guardando...' : subCategoryModalMode === 'create' ? 'Guardar' : 'Actualizar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="imageModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm" @mousedown.self="closeImageModal">
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
