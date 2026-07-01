<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const userRoles = computed(() => {
    const roles = page.props.auth?.user?.roles ?? page.props.auth?.roles ?? [];

    return roles.map((role) => {
        if (typeof role === 'string') {
            return role;
        }

        return role.name;
    });
});

const hasRole = (roles) => {
    return roles.some((role) => userRoles.value.includes(role));
};

const canSeeKitchenTickets = computed(() => {
    return hasRole(['Master', 'Administrador', 'Mesero']);
});

const canSeeClientTickets = computed(() => {
    return hasRole(['Cliente']);
});

const itemClass = (active) => {
    return [
        'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-black transition',
        active
            ? 'bg-[var(--app-primary)] text-white shadow-sm'
            : 'text-[var(--app-muted)] hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]',
    ];
};
</script>

<template>
    <div v-if="canSeeKitchenTickets || canSeeClientTickets" class="space-y-2">
        <p class="px-4 text-xs font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
            Tickets
        </p>

        <Link
            v-if="canSeeKitchenTickets"
            :href="route('tickets.index')"
            :class="itemClass(route().current('tickets.*'))"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2.2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 14h6m-6-4h6m-7 10h8a2 2 0 002-2V6.5L14.5 3H8a2 2 0 00-2 2v13a2 2 0 002 2z"
                />
            </svg>

            <span>Tickets</span>
        </Link>

        <Link
            v-if="canSeeClientTickets"
            :href="route('client.tickets.index')"
            :class="itemClass(route().current('client.tickets.*'))"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2.2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.75 9V5.75A2.75 2.75 0 0013 3H7.75A2.75 2.75 0 005 5.75v12.5A2.75 2.75 0 007.75 21h8.5A2.75 2.75 0 0019 18.25V12.5"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 8h4M9 12h3m4.5-4.5L19 10l-5 5-2.5.5.5-2.5 4.5-4.5z"
                />
            </svg>

            <span>Mis tickets</span>
        </Link>
    </div>
</template>
