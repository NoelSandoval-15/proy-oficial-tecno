<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const open = ref(true);

const productLinks = computed(() => [
    {
        label: 'Productos',
        routeName: 'products.index',
        path: '/productos',
        exact: true,
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    },
    {
        label: 'Categorías',
        routeName: 'products.categories.index',
        path: '/productos/categorias',
        exact: false,
        icon: 'M4 6h16M4 12h16M4 18h10',
    },
]);

const sectionActive = computed(() => {
    return page.url.startsWith('/productos');
});

const isActive = (item) => {
    if (item.exact) {
        return page.url === item.path;
    }

    return page.url === item.path || page.url.startsWith(`${item.path}/`);
};

onMounted(() => {
    const saved = localStorage.getItem('sidebar.productos.open');

    if (saved !== null) {
        open.value = saved === 'true';
    }

    if (sectionActive.value) {
        open.value = true;
    }
});

watch(open, (value) => {
    localStorage.setItem('sidebar.productos.open', value ? 'true' : 'false');
});
</script>

<template>
    <div class="mt-3">
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
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                </svg>

                Productos
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
                v-for="item in productLinks"
                :key="item.path"
                :href="route(item.routeName)"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-bold transition"
                :class="isActive(item)
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
