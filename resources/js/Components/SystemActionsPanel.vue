<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    search: {
        type: String,
        default: '',
    },
    selectedUser: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const currentRoles = computed(() => {
    return page.props.auth.user?.roles ?? [];
});

const can = (roles) => {
    return roles.some((role) => currentRoles.value.includes(role));
};

const routeWithQuery = (routeName, query = {}) => {
    const params = new URLSearchParams();

    Object.entries(query).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            params.append(key, value);
        }
    });

    const queryString = params.toString();

    return queryString ? `${route(routeName)}?${queryString}` : route(routeName);
};

const selectedRole = computed(() => {
    return props.selectedUser?.roles?.[0] ?? null;
});

const selectedEmail = computed(() => {
    return props.selectedUser?.email ?? '';
});

const baseActions = computed(() => {
    const actions = [
        {
            key: 'dashboard',
            title: 'Ir al dashboard',
            description: 'Volver al panel principal del sistema.',
            category: 'General',
            roles: ['Master', 'Administrador', 'Mesero', 'Cliente'],
            href: route('dashboard'),
            icon: 'dashboard',
        },
        {
            key: 'profile',
            title: 'Mi perfil',
            description: 'Ver y editar mis datos de cuenta.',
            category: 'Cuenta',
            roles: ['Master', 'Administrador', 'Mesero', 'Cliente'],
            href: route('profile.edit'),
            icon: 'user',
        },
        {
            key: 'view-employees',
            title: 'Visualizar empleados',
            description: 'Listar, buscar, editar y exportar empleados.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            href: route('administracion.empleados.index'),
            icon: 'users',
        },
        {
            key: 'add-employee',
            title: 'Añadir empleado',
            description: 'Registrar un nuevo empleado con rol Mesero.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            href: routeWithQuery('administracion.empleados.index', { action: 'create' }),
            icon: 'plus',
        },
        {
            key: 'view-clients',
            title: 'Visualizar clientes',
            description: 'Listar clientes, reservas y compras registradas.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            href: route('administracion.clientes.index'),
            icon: 'clients',
        },
        {
            key: 'add-client',
            title: 'Añadir cliente',
            description: 'Registrar un nuevo cliente en el sistema.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            href: routeWithQuery('administracion.clientes.index', { action: 'create' }),
            icon: 'plus',
        },
        {
            key: 'view-admins',
            title: 'Visualizar administradores',
            description: 'Revisar usuarios con rol Administrador.',
            category: 'Seguridad',
            roles: ['Master', 'Administrador'],
            href: route('administracion.administradores.index'),
            icon: 'shield',
        },
        {
            key: 'add-admin',
            title: 'Añadir administrador',
            description: 'Registrar un usuario administrador.',
            category: 'Seguridad',
            roles: ['Master'],
            href: routeWithQuery('administracion.administradores.index', { action: 'create' }),
            icon: 'shield',
        },
        {
            key: 'search-users',
            title: 'Buscar usuario',
            description: 'Consultar datos y estadísticas por usuario.',
            category: 'Consultas',
            roles: ['Master', 'Administrador'],
            href: route('administracion.usuarios.buscar'),
            icon: 'search',
        },
    ];

    if (props.selectedUser && can(['Master', 'Administrador'])) {
        if (selectedRole.value === 'Mesero') {
            actions.unshift({
                key: 'selected-employee',
                title: 'Ver empleado seleccionado',
                description: `Abrir el listado filtrado por ${selectedEmail.value}.`,
                category: 'Usuario seleccionado',
                roles: ['Master', 'Administrador'],
                href: routeWithQuery('administracion.empleados.index', { search: selectedEmail.value }),
                icon: 'users',
            });
        }

        if (selectedRole.value === 'Cliente') {
            actions.unshift({
                key: 'selected-client',
                title: 'Ver cliente seleccionado',
                description: `Abrir el listado filtrado por ${selectedEmail.value}.`,
                category: 'Usuario seleccionado',
                roles: ['Master', 'Administrador'],
                href: routeWithQuery('administracion.clientes.index', { search: selectedEmail.value }),
                icon: 'clients',
            });
        }

        if (selectedRole.value === 'Administrador' || selectedRole.value === 'Master') {
            actions.unshift({
                key: 'selected-admin',
                title: 'Ver administrador seleccionado',
                description: `Abrir el listado filtrado por ${selectedEmail.value}.`,
                category: 'Usuario seleccionado',
                roles: ['Master', 'Administrador'],
                href: routeWithQuery('administracion.administradores.index', { search: selectedEmail.value }),
                icon: 'shield',
            });
        }
    }

    return actions;
});

const visibleActions = computed(() => {
    const text = props.search.toLowerCase().trim();

    return baseActions.value
        .filter((action) => can(action.roles))
        .filter((action) => {
            if (!text) {
                return true;
            }

            return [
                action.title,
                action.description,
                action.category,
            ].join(' ').toLowerCase().includes(text);
        });
});

const groupedActions = computed(() => {
    return visibleActions.value.reduce((groups, action) => {
        if (!groups[action.category]) {
            groups[action.category] = [];
        }

        groups[action.category].push(action);

        return groups;
    }, {});
});
</script>

<template>
    <section class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                    Acciones disponibles
                </p>

                <h3 class="mt-1 text-2xl font-black text-[var(--app-text)]">
                    ¿Qué puedes hacer?
                </h3>

                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                    Las opciones se muestran según tu rol y la búsqueda actual.
                </p>
            </div>

            <span class="rounded-2xl bg-[var(--app-primary-soft)] px-4 py-2 text-xs font-black text-[var(--app-primary-text)]">
                {{ currentRoles.join(', ') || 'Sin rol' }}
            </span>
        </div>

        <div v-if="visibleActions.length > 0" class="mt-6 space-y-6">
            <div
                v-for="(actions, category) in groupedActions"
                :key="category"
            >
                <p class="mb-3 text-xs font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                    {{ category }}
                </p>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="action in actions"
                        :key="action.key"
                        :href="action.href"
                        class="group rounded-[1.5rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4 transition hover:-translate-y-0.5 hover:border-[var(--app-primary)] hover:bg-[var(--app-primary-soft)] hover:shadow-lg"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[var(--app-card)] text-[var(--app-primary)] shadow-sm transition group-hover:scale-105">
                                <svg
                                    v-if="action.icon === 'dashboard'"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h7v7H4V5zm9 0h7v5h-7V5zM4 14h7v5H4v-5zm9-2h7v7h-7v-7z" />
                                </svg>

                                <svg
                                    v-else-if="action.icon === 'users'"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 118 0 4 4 0 01-8 0z" />
                                </svg>

                                <svg
                                    v-else-if="action.icon === 'clients'"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11a4 4 0 100-8 4 4 0 000 8zM8 13a4 4 0 100-8 4 4 0 000 8zM2 21a6 6 0 0112 0M12 21a6 6 0 0110 0" />
                                </svg>

                                <svg
                                    v-else-if="action.icon === 'shield'"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z" />
                                </svg>

                                <svg
                                    v-else-if="action.icon === 'plus'"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                                </svg>

                                <svg
                                    v-else
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-black text-[var(--app-text)]">
                                    {{ action.title }}
                                </p>

                                <p class="mt-1 line-clamp-2 text-sm font-semibold text-[var(--app-muted)]">
                                    {{ action.description }}
                                </p>
                            </div>

                            <svg class="h-5 w-5 shrink-0 text-[var(--app-muted)] transition group-hover:translate-x-1 group-hover:text-[var(--app-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <div v-else class="mt-6 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-6 text-center">
            <p class="font-black text-[var(--app-text)]">
                No hay acciones disponibles
            </p>

            <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                Tu rol no tiene permisos para estas opciones o la búsqueda no coincide.
            </p>
        </div>
    </section>
</template>
