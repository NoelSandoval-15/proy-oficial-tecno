<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';

const props = defineProps({
    title: String,
    subtitle: String,
    roleName: String,
    createLabel: String,
    storeRoute: String,
    users: {
        type: Object,
        default: () => ({
            data: [],
            meta: {},
        }),
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
            total: 0,
            with_profile: 0,
            created_this_month: 0,
        }),
    },
});

const modalOpen = ref(false);
const modalMode = ref('create');
const editingUser = ref(null);
const selectedIds = ref([]);

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? '10');
const tableLoading = ref(false);
const showPassword = ref(false);
const photoPreview = ref(null);

let searchTimeout = null;

const rows = computed(() => props.users.data ?? []);
const meta = computed(() => props.users.meta ?? {});

const form = useForm({
    _method: null,
    name: '',
    last_name: '',
    ci: '',
    telephone: '',
    email: '',
    password: '',
    password_confirmation: '',
    photo: null,
});

const displayName = (user) => {
    const profileName = user.profile?.name;
    const profileLastName = user.profile?.last_name;

    if (profileName || profileLastName) {
        return `${profileName ?? ''} ${profileLastName ?? ''}`.trim();
    }

    return user.name ?? 'Sin nombre';
};

const initials = (user) => {
    return displayName(user).charAt(0).toUpperCase();
};

const allVisibleSelected = computed(() => {
    return rows.value.length > 0 && rows.value.every((user) => selectedIds.value.includes(user.id));
});

const selectedRows = computed(() => {
    return rows.value.filter((user) => selectedIds.value.includes(user.id));
});

const exportRows = computed(() => {
    return selectedRows.value.length > 0 ? selectedRows.value : rows.value;
});

const reloadList = () => {
    tableLoading.value = true;

    router.get(
        window.location.pathname,
        {
            search: search.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters', 'stats'],
            onFinish: () => {
                tableLoading.value = false;
            },
        }
    );
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

const resetForm = () => {
    form.reset();
    form.clearErrors();

    form._method = null;
    form.photo = null;

    editingUser.value = null;
    photoPreview.value = null;
    showPassword.value = false;
};

const openCreate = () => {
    modalMode.value = 'create';
    resetForm();
    modalOpen.value = true;
};

const openEdit = (user) => {
    modalMode.value = 'edit';
    editingUser.value = user;

    form._method = 'patch';
    form.name = user.profile?.name ?? user.name ?? '';
    form.last_name = user.profile?.last_name ?? '';
    form.ci = user.profile?.ci ?? '';
    form.telephone = user.profile?.telephone ?? '';
    form.email = user.email ?? '';
    form.password = '';
    form.password_confirmation = '';
    form.photo = null;

    photoPreview.value = user.profile?.url_photo ?? null;
    showPassword.value = false;

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

const closeByBackdrop = () => {
    closeModal();
};

const generatePassword = () => {
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower = 'abcdefghijkmnpqrstuvwxyz';
    const numbers = '23456789';
    const symbols = '@#$%&*';
    const all = upper + lower + numbers + symbols;

    let password = '';

    password += upper[Math.floor(Math.random() * upper.length)];
    password += lower[Math.floor(Math.random() * lower.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    password += symbols[Math.floor(Math.random() * symbols.length)];

    for (let i = password.length; i < 10; i++) {
        password += all[Math.floor(Math.random() * all.length)];
    }

    password = password
        .split('')
        .sort(() => Math.random() - 0.5)
        .join('');

    form.password = password;
    form.password_confirmation = password;
    showPassword.value = true;
};

const handlePhoto = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        form.photo = null;
        photoPreview.value = modalMode.value === 'edit'
            ? editingUser.value?.profile?.url_photo ?? null
            : null;

        return;
    }

    form.photo = file;
    photoPreview.value = URL.createObjectURL(file);
};

const submit = () => {
    if (modalMode.value === 'create') {
        form._method = null;

        form.post(route(props.storeRoute), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeModal();
            },
        });

        return;
    }

    form._method = 'patch';

    form.post(route('administracion.usuarios.update', editingUser.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const toggleAllVisible = () => {
    if (allVisibleSelected.value) {
        selectedIds.value = selectedIds.value.filter(
            (id) => !rows.value.some((user) => user.id === id)
        );

        return;
    }

    const visibleIds = rows.value.map((user) => user.id);

    selectedIds.value = Array.from(new Set([...selectedIds.value, ...visibleIds]));
};

const toggleOne = (id) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((selectedId) => selectedId !== id);
        return;
    }

    selectedIds.value.push(id);
};

const deleteUser = (user) => {
    if (!confirm(`¿Eliminar la cuenta de ${displayName(user)}?`)) {
        return;
    }

    router.delete(route('administracion.usuarios.destroy', user.id), {
        preserveScroll: true,
    });
};

const exportExcel = () => {
    const data = exportRows.value;

    if (data.length === 0) {
        alert('No hay datos para exportar.');
        return;
    }

    const headers = [
        'ID',
        'Nombre completo',
        'Correo electrónico',
        'CI',
        'Teléfono',
        'Rol',
    ];

    const csvRows = [
        headers.join(';'),
        ...data.map((user) => [
            user.id,
            displayName(user),
            user.email ?? '',
            user.profile?.ci ?? '',
            user.profile?.telephone ?? '',
            user.roles?.[0] ?? 'Sin rol',
        ].map((value) => `"${String(value).replaceAll('"', '""')}"`).join(';')),
    ];

    const bom = '\uFEFF';

    const blob = new Blob([bom + csvRows.join('\r\n')], {
        type: 'text/csv;charset=utf-8;',
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = `${props.title.toLowerCase()}-churrasqueria-roberto.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
};

const exportPdf = () => {
    const ids = selectedIds.value.join(',');

    const params = new URLSearchParams({
        role: props.roleName,
        search: search.value ?? '',
        ids,
    });

    window.open(`${route('administracion.usuarios.export.pdf')}?${params.toString()}`, '_blank');
};

const goToPage = (pageNumber) => {
    tableLoading.value = true;

    router.get(
        window.location.pathname,
        {
            search: search.value,
            per_page: perPage.value,
            page: pageNumber,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters', 'stats'],
            onFinish: () => {
                tableLoading.value = false;
            },
        }
    );
};

const clearFilters = () => {
    search.value = '';
    perPage.value = '10';
};
</script>

<template>
    <Head :title="title" />

    <SidebarLayout :title="title" :subtitle="subtitle">
        <section class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <p class="text-sm font-black text-[var(--app-muted)]">Total registrados</p>
                <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ stats.total }}</p>
            </article>

            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <p class="text-sm font-black text-[var(--app-muted)]">Con perfil completo</p>
                <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ stats.with_profile }}</p>
            </article>

            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5 shadow-sm">
                <p class="text-sm font-black text-[var(--app-muted)]">Registrados este mes</p>
                <p class="mt-3 text-3xl font-black text-[var(--app-text)]">{{ stats.created_this_month }}</p>
            </article>
        </section>

        <section class="mt-6 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                        Administración
                    </p>

                    <h1 class="mt-2 text-3xl font-black text-[var(--app-text)]">
                        {{ title }}
                    </h1>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Rol asignado por defecto: {{ roleName }}
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                        @click="exportExcel"
                    >
                        Exportar Excel
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]"
                        @click="exportPdf"
                    >
                        Exportar PDF
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl bg-[var(--app-primary)] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:opacity-90"
                        @click="openCreate"
                    >
                        {{ createLabel }}
                    </button>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-[1fr_220px_160px] lg:items-end">
                <div>
                    <label class="text-sm font-black text-[var(--app-text)]">
                        Buscar en tiempo real
                    </label>

                    <div class="relative mt-2">
                        <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                        </svg>

                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nombre, apellido, correo, CI o teléfono"
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
                        <option value="10">10 usuarios</option>
                        <option value="20">20 usuarios</option>
                        <option value="50">50 usuarios</option>
                        <option value="100">100 usuarios</option>
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

        <section class="relative mt-6 overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-sm">
            <div
                v-if="tableLoading"
                class="absolute inset-0 z-30 flex items-center justify-center bg-[var(--app-card)]/70 backdrop-blur-sm"
            >
                <div class="flex flex-col items-center gap-3 rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] px-7 py-6 shadow-xl">
                    <div class="h-12 w-12 animate-spin rounded-full border-4 border-[var(--app-primary-soft)] border-t-[var(--app-primary)]"></div>
                    <p class="text-sm font-black text-[var(--app-text)]">Actualizando tabla...</p>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-b border-[var(--app-border)] px-6 py-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-black text-[var(--app-text)]">
                        Lista de {{ title.toLowerCase() }}
                    </h2>

                    <p class="mt-1 text-sm font-bold text-[var(--app-muted)]">
                        Mostrando {{ meta.from ?? 0 }} - {{ meta.to ?? 0 }} de {{ meta.total ?? 0 }}
                    </p>
                </div>

                <p class="text-sm font-black text-[var(--app-primary)]">
                    Seleccionados para exportar: {{ selectedIds.length }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead>
                        <tr class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] text-xs font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                            <th class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :checked="allVisibleSelected"
                                    class="rounded border-[var(--app-border)] text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                    @change="toggleAllVisible"
                                />
                            </th>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4">CI</th>
                            <th class="px-6 py-4">Teléfono</th>
                            <th class="px-6 py-4">Rol</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[var(--app-border)]">
                        <tr
                            v-for="user in rows"
                            :key="user.id"
                            class="transition hover:bg-[var(--app-surface-soft)]"
                        >
                            <td class="px-6 py-5">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(user.id)"
                                    class="rounded border-[var(--app-border)] text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                    @change="toggleOne(user.id)"
                                />
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="user.profile?.url_photo"
                                        :src="user.profile.url_photo"
                                        :alt="displayName(user)"
                                        class="h-12 w-12 rounded-2xl object-cover shadow-sm"
                                    />

                                    <div
                                        v-else
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-sm font-black text-white shadow-sm"
                                    >
                                        {{ initials(user) }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate font-black text-[var(--app-text)]">
                                            {{ displayName(user) }}
                                        </p>
                                        <p class="truncate text-xs font-semibold text-[var(--app-muted)]">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-sm font-semibold text-[var(--app-muted)]">
                                {{ user.profile?.ci ?? 'Sin CI' }}
                            </td>

                            <td class="px-6 py-5 text-sm font-semibold text-[var(--app-muted)]">
                                {{ user.profile?.telephone ?? 'Sin teléfono' }}
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-xl bg-[var(--app-primary-soft)] px-3 py-1 text-xs font-black text-[var(--app-primary-text)]">
                                    {{ user.roles?.[0] ?? 'Sin rol' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        title="Editar usuario"
                                        class="rounded-xl bg-[var(--app-surface-soft)] p-2 text-[var(--app-text)] transition hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary-text)]"
                                        @click="openEdit(user)"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-9.193 9.193a4.5 4.5 0 01-1.897 1.13L7 17l.84-3.423a4.5 4.5 0 011.13-1.897l7.892-7.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5M5 21h14" />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        title="Eliminar usuario"
                                        class="rounded-xl bg-red-500/10 p-2 text-red-500 transition hover:bg-red-500/20"
                                        @click="deleteUser(user)"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6M14 11v6" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="rows.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-lg font-black text-[var(--app-text)]">
                                    No hay registros
                                </p>
                                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                    Intenta cambiar los filtros o crea un nuevo usuario.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
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

        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8 backdrop-blur-sm"
            @mousedown.self="closeByBackdrop"
        >
            <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-2xl">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                                {{ modalMode === 'create' ? 'Nuevo registro' : 'Editar usuario' }}
                            </p>

                            <h2 class="mt-2 text-2xl font-black text-[var(--app-text)]">
                                {{ modalMode === 'create' ? createLabel : 'Actualizar datos' }}
                            </h2>

                            <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                Rol: {{ roleName }}
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
                    <div class="flex items-center gap-4 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                        <img
                            v-if="photoPreview"
                            :src="photoPreview"
                            alt="Foto de perfil"
                            class="h-20 w-20 rounded-2xl object-cover"
                        />

                        <div
                            v-else
                            class="flex h-20 w-20 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-2xl font-black text-white"
                        >
                            {{ form.name ? form.name.charAt(0).toUpperCase() : '?' }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <label class="text-sm font-black text-[var(--app-text)]">
                                Foto de perfil
                            </label>

                            <input
                                type="file"
                                accept=".jpg,.jpeg,image/jpeg"
                                class="mt-2 block w-full text-sm font-semibold text-[var(--app-muted)] file:mr-4 file:rounded-xl file:border-0 file:bg-[var(--app-primary)] file:px-4 file:py-2 file:text-sm file:font-black file:text-white"
                                @change="handlePhoto"
                            />

                            <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                Solo JPG/JPEG. Se guardará en administracion/{{ roleName }}/nombreapellido.jpg
                            </p>

                            <p v-if="form.errors.photo" class="mt-1 text-sm font-bold text-red-500">
                                {{ form.errors.photo }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Nombre</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Apellido</label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                            <p v-if="form.errors.last_name" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">CI</label>
                            <input
                                v-model="form.ci"
                                type="number"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                            <p v-if="form.errors.ci" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.ci }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-black text-[var(--app-text)]">Teléfono</label>
                            <input
                                v-model="form.telephone"
                                type="number"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                            <p v-if="form.errors.telephone" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.telephone }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-[var(--app-text)]">Correo electrónico</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-[var(--app-text)]">
                                    Contraseña
                                </p>
                                <p class="text-xs font-semibold text-[var(--app-muted)]">
                                    Puedes escribirla o generarla automáticamente.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-xl bg-[var(--app-primary)] px-4 py-2 text-xs font-black text-white"
                                @click="generatePassword"
                            >
                                Generar contraseña
                            </button>
                        </div>

                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-black text-[var(--app-text)]">
                                    Contraseña
                                </label>

                                <div class="relative mt-2">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        :placeholder="modalMode === 'edit' ? 'Dejar vacío para no cambiar' : ''"
                                        class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-card)] pr-14 text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                    />

                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs font-black text-[var(--app-primary)]"
                                        @click="showPassword = !showPassword"
                                    >
                                        {{ showPassword ? 'Ocultar' : 'Ver' }}
                                    </button>
                                </div>

                                <p v-if="form.errors.password" class="mt-1 text-sm font-bold text-red-500">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-black text-[var(--app-text)]">Confirmar contraseña</label>
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-card)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                        <p class="text-sm font-black text-[var(--app-text)]">
                            Rol asignado automáticamente
                        </p>
                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            {{ roleName }}
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
    </SidebarLayout>
</template>
