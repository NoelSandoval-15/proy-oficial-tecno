<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const open = ref(true);

const user = computed(() => page.props.auth?.user ?? null);

const userRoles = computed(() => {
    const roles =
        user.value?.roles ??
        user.value?.role_names ??
        user.value?.roleNames ??
        null;

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

    const singleRole =
        user.value?.role ??
        user.value?.user_role ??
        user.value?.role_name ??
        null;

    return singleRole ? [singleRole] : [];
});

const hasAnyRole = (allowedRoles) => {
    return userRoles.value.some((role) => allowedRoles.includes(role));
};

const menuItems = computed(() => {
    const items = [];

    if (hasAnyRole(['Master', 'Administrador', 'Mesero'])) {
        items.push({
            label: 'Pedidos internos',
            routeName: 'admin.orders.index',
            path: '/admin/pedidos',
            icon: 'M3 7h18M5 7l1 13h12l1-13M9 7V5a3 3 0 016 0v2M9 12h6M9 16h6',
        });

        items.push({
            label: 'Nuevo pedido',
            routeName: 'admin.orders.index',
            path: '/admin/pedidos',
            query: {
                action: 'create',
            },
            icon: 'M12 5v14M5 12h14',
        });
    }

    if (hasAnyRole(['Cliente'])) {
        items.push({
            label: 'Mis pedidos',
            routeName: 'client.orders.index',
            path: '/cliente/pedidos',
            icon: 'M9 12l2 2 4-4M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z',
        });

        items.push({
            label: 'Realizar pedido',
            routeName: 'client.orders.index',
            path: '/cliente/pedidos',
            query: {
                action: 'create',
            },
            icon: 'M12 5v14M5 12h14',
        });
    }

    return items;
});

const sectionActive = computed(() => {
    return page.url.startsWith('/admin/pedidos')
        || page.url.startsWith('/cliente/pedidos');
});

const isActive = (path) => {
    return page.url === path || page.url.startsWith(`${path}/`);
};

const buildHref = (item) => {
    const baseUrl = route(item.routeName);

    if (!item.query) {
        return baseUrl;
    }

    const params = new URLSearchParams();

    Object.entries(item.query).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            params.append(key, value);
        }
    });

    const queryString = params.toString();

    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
};

onMounted(() => {
    const saved = localStorage.getItem('sidebar.pedidos.open');

    if (saved !== null) {
        open.value = saved === 'true';
    }

    if (sectionActive.value) {
        open.value = true;
    }
});

watch(open, (value) => {
    localStorage.setItem('sidebar.pedidos.open', value ? 'true' : 'false');
});
</script>

<template>
    <div v-if="menuItems.length" class="mt-3">
        <button
            type="button"
            class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-black transition"
            :class="sectionActive
                ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                : 'text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            @click="open = !open"
        >
            <span class="flex items-center gap-3">
                <svg
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M3 7h18M5 7l1 13h12l1-13M9 7V5a3 3 0 016 0v2M9 12h6M9 16h6"
                    />
                </svg>

                Pedidos
            </span>

            <svg
                class="h-4 w-4 transition"
                :class="open ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <div v-if="open" class="mt-2 space-y-1 pl-2">
            <Link
                v-for="item in menuItems"
                :key="`${item.path}-${item.label}`"
                :href="buildHref(item)"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-bold transition"
                :class="isActive(item.path)
                    ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                    : 'text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]'"
            >
                <svg
                    class="h-5 w-5 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        :d="item.icon"
                    />
                </svg>

                <span class="truncate">
                    {{ item.label }}
                </span>
            </Link>
        </div>
    </div>
</template>
