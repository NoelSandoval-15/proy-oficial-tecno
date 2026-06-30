<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();

const user = computed(() => page.props.auth.user);

const userInitial = computed(() => {
    return user.value?.name?.charAt(0)?.toUpperCase() ?? 'U';
});

const userRoles = computed(() => {
    const roles =
        user.value?.roles ??
        user.value?.role_names ??
        user.value?.roleNames ??
        [];

    if (Array.isArray(roles)) {
        return roles
            .map((role) => {
                if (typeof role === 'string') {
                    return role;
                }

                return role?.name;
            })
            .filter(Boolean);
    }

    if (roles && typeof roles === 'object') {
        return Object.values(roles)
            .map((role) => {
                if (typeof role === 'string') {
                    return role;
                }

                return role?.name;
            })
            .filter(Boolean);
    }

    return [];
});

const mainRole = computed(() => {
    return userRoles.value[0] ?? 'Sin rol';
});

const roleClass = computed(() => {
    const role = mainRole.value;

    if (role === 'Master') {
        return 'bg-red-500/10 text-red-600 ring-red-500/20';
    }

    if (role === 'Administrador') {
        return 'bg-blue-500/10 text-blue-600 ring-blue-500/20';
    }

    if (role === 'Mesero') {
        return 'bg-amber-500/10 text-amber-600 ring-amber-500/20';
    }

    if (role === 'Cliente') {
        return 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20';
    }

    return 'bg-slate-500/10 text-slate-600 ring-slate-500/20';
});

const createdAt = computed(() => {
    if (!user.value?.created_at) {
        return 'Sin fecha';
    }

    return new Date(user.value.created_at).toLocaleDateString('es-BO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const currentTheme = computed(() => {
    return user.value?.theme?.name ?? 'Sin tema';
});
</script>

<template>
    <Head title="Mi perfil" />

    <SidebarLayout
        title="Mi perfil"
        subtitle="Administra tus datos personales, correo y seguridad de acceso."
    >
        <section class="space-y-6">
            <article
                class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
            >
                <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[var(--app-primary)]/15 blur-3xl"></div>
                <div class="absolute -bottom-16 left-10 h-36 w-36 rounded-full bg-orange-400/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center rounded-[2rem] bg-[var(--app-primary)] text-4xl font-black text-white shadow-sm"
                        >
                            {{ userInitial }}
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="truncate text-3xl font-black tracking-tight text-[var(--app-text)]">
                                    {{ user?.name }}
                                </h1>

                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1"
                                    :class="roleClass"
                                >
                                    {{ mainRole }}
                                </span>
                            </div>

                            <p class="mt-2 truncate text-sm font-semibold text-[var(--app-muted)]">
                                {{ user?.email }}
                            </p>

                            <p class="mt-3 max-w-2xl text-sm font-medium leading-6 text-[var(--app-muted)]">
                                Desde esta sección puedes actualizar tu información de cuenta,
                                cambiar tu contraseña y administrar la seguridad de tu usuario.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[420px]">
                        <div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                Rol actual
                            </p>

                            <p class="mt-1 text-base font-black text-[var(--app-text)]">
                                {{ mainRole }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                Tema visual
                            </p>

                            <p class="mt-1 text-base font-black text-[var(--app-text)]">
                                {{ currentTheme }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4 sm:col-span-2">
                            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                Cuenta creada
                            </p>

                            <p class="mt-1 text-base font-black text-[var(--app-text)]">
                                {{ createdAt }}
                            </p>
                        </div>
                    </div>
                </div>
            </article>

            <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
                <section class="space-y-6">
                    <article
                        class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
                    >
                        <div class="mb-6 flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[var(--app-primary-soft)] text-[var(--app-primary)]"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-xl font-black text-[var(--app-text)]">
                                    Información de cuenta
                                </h2>

                                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                    Actualiza tu nombre de usuario y correo electrónico.
                                </p>
                            </div>
                        </div>

                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </article>

                    <article
                        class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
                    >
                        <div class="mb-6 flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V8a5 5 0 00-10 0v3H6a2 2 0 00-2 2v6a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-xl font-black text-[var(--app-text)]">
                                    Seguridad
                                </h2>

                                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                                    Cambia tu contraseña para mantener protegida tu cuenta.
                                </p>
                            </div>
                        </div>

                        <UpdatePasswordForm />
                    </article>
                </section>

                <aside class="space-y-6">
                    <article
                        class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm"
                    >
                        <h3 class="text-lg font-black text-[var(--app-text)]">
                            Resumen del usuario
                        </h3>

                        <div class="mt-5 space-y-3">
                            <div class="rounded-2xl bg-[var(--app-surface-soft)] p-4">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Nombre
                                </p>

                                <p class="mt-1 break-words text-sm font-black text-[var(--app-text)]">
                                    {{ user?.name }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[var(--app-surface-soft)] p-4">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Correo
                                </p>

                                <p class="mt-1 break-all text-sm font-black text-[var(--app-text)]">
                                    {{ user?.email }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[var(--app-surface-soft)] p-4">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                    Rol
                                </p>

                                <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                                    {{ mainRole }}
                                </p>
                            </div>
                        </div>
                    </article>

                    <article
                        class="rounded-[2rem] border border-red-200 bg-red-50 p-6 shadow-sm"
                    >
                        <div class="mb-6 flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-500/10 text-red-600"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-xl font-black text-red-700">
                                    Zona de peligro
                                </h2>

                                <p class="mt-1 text-sm font-semibold text-red-600/80">
                                    Esta acción es permanente.
                                </p>
                            </div>
                        </div>

                        <DeleteUserForm />
                    </article>
                </aside>
            </div>
        </section>
    </SidebarLayout>
</template>
