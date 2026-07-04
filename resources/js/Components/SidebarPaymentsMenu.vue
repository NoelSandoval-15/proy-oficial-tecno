<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props.auth?.user)

const roleNames = computed(() => {
    const roles = user.value?.roles || []

    return roles.map((role) => {
        if (typeof role === 'string') {
            return role
        }

        return role?.name
    }).filter(Boolean)
})

const hasRole = (allowedRoles) => {
    return roleNames.value.some((role) => allowedRoles.includes(role))
}

const canManagePayments = computed(() => {
    return hasRole(['Master', 'Administrador', 'Mesero'])
})

const canSeeClientPayments = computed(() => {
    return hasRole(['Cliente'])
})

const canSeePaymentsMenu = computed(() => {
    return canManagePayments.value || canSeeClientPayments.value
})
</script>

<template>
    <div v-if="canSeePaymentsMenu" class="mt-4 space-y-1">
        <div class="px-3 pb-1">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">
                Pagos
            </p>
        </div>

        <Link
            v-if="canManagePayments"
            :href="route('payments.index')"
            :class="[
                'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
                route().current('payments.*')
                    ? 'bg-gray-900 text-white shadow-sm'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-950'
            ]"
        >
            <span
                :class="[
                    'flex h-9 w-9 items-center justify-center rounded-xl transition-all duration-200',
                    route().current('payments.*')
                        ? 'bg-white/10 text-white'
                        : 'bg-gray-100 text-gray-500 group-hover:bg-white group-hover:text-gray-900'
                ]"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3.5" y="6" width="17" height="12" rx="2" />
                    <path d="M3.5 10h17" />
                    <path d="M7 15h4" />
                    <path d="M16.5 14.5h1.5" />
                </svg>
            </span>

            <span class="flex-1">
                Gestión de pagos
            </span>
        </Link>

        <Link
            v-if="canSeeClientPayments"
            :href="route('client.payments.index')"
            :class="[
                'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
                route().current('client.payments.*')
                    ? 'bg-gray-900 text-white shadow-sm'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-950'
            ]"
        >
            <span
                :class="[
                    'flex h-9 w-9 items-center justify-center rounded-xl transition-all duration-200',
                    route().current('client.payments.*')
                        ? 'bg-white/10 text-white'
                        : 'bg-gray-100 text-gray-500 group-hover:bg-white group-hover:text-gray-900'
                ]"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3.5" y="5" width="17" height="14" rx="2.5" />
                    <path d="M7 9h10" />
                    <path d="M7 13h4" />
                    <path d="m14 15 1.7 1.7L19 13.5" />
                </svg>
            </span>

            <span class="flex-1">
                Mis pagos
            </span>
        </Link>
    </div>
</template>
