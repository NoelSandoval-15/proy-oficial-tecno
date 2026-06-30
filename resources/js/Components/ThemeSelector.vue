<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { getVisualTheme, normalizeThemeName } from '@/Theme/visualThemes';

const props = defineProps({
    variant: {
        type: String,
        default: 'card',
    },
});

const page = usePage();

const open = ref(false);

const rawThemes = computed(() => page.props.visualThemes ?? []);
const user = computed(() => page.props.auth.user);

const isCompact = computed(() => props.variant === 'compact');

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

const isClient = computed(() => {
    return userRoles.value.includes('Cliente');
});

const currentThemeKey = computed(() => {
    const key = normalizeThemeName(user.value?.theme?.name ?? 'Adultos');

    if (isClient.value && key === 'administrador') {
        return 'adultos';
    }

    return key;
});

const currentTheme = computed(() => {
    const themeName = user.value?.theme?.name ?? 'Adultos';
    const key = normalizeThemeName(themeName);

    if (isClient.value && key === 'administrador') {
        return getVisualTheme('Adultos');
    }

    return getVisualTheme(themeName);
});

const themeOptions = computed(() => {
    const map = new Map();

    rawThemes.value.forEach((theme) => {
        const key = normalizeThemeName(theme.name);

        if (!map.has(key)) {
            map.set(key, {
                id: theme.id,
                key,
                name: getVisualTheme(theme.name).displayName,
                theme: getVisualTheme(theme.name),
            });
        }
    });

    if (!map.has('administrador')) {
        map.set('administrador', {
            id: null,
            key: 'administrador',
            name: 'Administrador',
            theme: getVisualTheme('Administrador'),
        });
    }

    if (!map.has('adultos')) {
        map.set('adultos', {
            id: null,
            key: 'adultos',
            name: 'Adultos',
            theme: getVisualTheme('Adultos'),
        });
    }

    if (!map.has('nino')) {
        map.set('nino', {
            id: null,
            key: 'nino',
            name: 'Niño',
            theme: getVisualTheme('Niño'),
        });
    }

    const options = [
        map.get('administrador'),
        map.get('adultos'),
        map.get('nino'),
    ].filter(Boolean);

    if (isClient.value) {
        return options.filter((theme) => theme.key !== 'administrador');
    }

    return options;
});

const form = useForm({
    themes_id: null,
});

watch(
    () => currentThemeKey.value,
    () => {
        const selectedOption = themeOptions.value.find((theme) => theme.key === currentThemeKey.value);
        form.themes_id = selectedOption?.id ?? null;
    },
    { immediate: true }
);

const selectTheme = (themeOption) => {
    if (!themeOption) {
        return;
    }

    if (isClient.value && themeOption.key === 'administrador') {
        open.value = false;
        return;
    }

    if (!themeOption.id) {
        alert('Este tema no existe todavía en la base de datos.');
        return;
    }

    if (currentThemeKey.value === themeOption.key) {
        open.value = false;
        return;
    }

    form.themes_id = themeOption.id;

    form.post(route('themes.select'), {
        preserveScroll: true,
        onFinish: () => {
            open.value = false;
        },
    });
};

const closeDropdown = () => {
    open.value = false;
};
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="group flex items-center gap-3 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-3 py-2 shadow-sm transition hover:border-[var(--app-primary)] hover:bg-[var(--app-primary-soft)]"
            :class="isCompact ? '' : 'w-full justify-between p-4'"
            @click="open = !open"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--app-card)] text-[var(--app-primary)] shadow-sm">
                    <svg class="h-5 w-5 transition group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 3l2.5 5.5L20 11l-5.5 2.5L12 19l-2.5-5.5L4 11l5.5-2.5L12 3z"
                        />
                    </svg>
                </div>

                <div class="text-left leading-tight" :class="isCompact ? 'hidden xl:block' : ''">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                        Tema visual
                    </p>

                    <p class="text-sm font-black text-[var(--app-text)]">
                        {{ currentTheme.displayName }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span
                    class="hidden h-3 w-3 rounded-full sm:block"
                    :style="{ backgroundColor: currentTheme.variables['--app-primary'] }"
                ></span>

                <svg
                    class="h-4 w-4 text-[var(--app-muted)] transition"
                    :class="open ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <div
            v-if="open"
            class="fixed inset-0 z-40"
            @click="closeDropdown"
        ></div>

        <div
            v-if="open"
            class="absolute right-0 top-14 z-50 w-72 overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-2 shadow-2xl"
        >
            <button
                v-for="themeOption in themeOptions"
                :key="themeOption.key"
                type="button"
                class="group flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left transition"
                :class="themeOption.key === currentThemeKey
                    ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                    : 'text-[var(--app-text)] hover:bg-[var(--app-surface-soft)]'"
                @click="selectTheme(themeOption)"
            >
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm transition group-hover:scale-105"
                    :style="{ backgroundColor: themeOption.theme.variables['--app-primary'] }"
                >
                    <svg
                        v-if="themeOption.key === 'administrador'"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z" />
                    </svg>

                    <svg
                        v-else-if="themeOption.key === 'adultos'"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M6 19V8a6 6 0 0112 0v11M9 11h6" />
                    </svg>

                    <svg
                        v-else
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l2 4 4 .5-3 3 .8 4.5L12 13l-3.8 2 .8-4.5-3-3L10 7l2-4z" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="font-black">
                        {{ themeOption.name }}
                    </p>

                    <p class="truncate text-xs font-semibold text-[var(--app-muted)]">
                        {{ themeOption.theme.description }}
                    </p>
                </div>

                <svg
                    v-if="themeOption.key === currentThemeKey"
                    class="h-5 w-5 text-[var(--app-primary)]"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
    </div>
</template>
