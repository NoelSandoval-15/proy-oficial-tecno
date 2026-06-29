<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { getVisualTheme, normalizeThemeName } from '@/Theme/visualThemes';

const page = usePage();

const rawThemes = computed(() => page.props.visualThemes ?? []);
const user = computed(() => page.props.auth.user);

const currentThemeKey = computed(() => {
    return normalizeThemeName(user.value?.theme?.name ?? 'Administrador');
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
            });
        }
    });

    const existingKeys = Array.from(map.keys());

    if (!existingKeys.includes('administrador')) {
        map.set('administrador', {
            id: null,
            key: 'administrador',
            name: 'Administrador',
        });
    }

    if (!existingKeys.includes('adultos')) {
        map.set('adultos', {
            id: null,
            key: 'adultos',
            name: 'Adultos',
        });
    }

    if (!existingKeys.includes('nino')) {
        map.set('nino', {
            id: null,
            key: 'nino',
            name: 'Niño',
        });
    }

    return [
        map.get('administrador'),
        map.get('adultos'),
        map.get('nino'),
    ].filter(Boolean);
});

const selectedKey = computed(() => currentThemeKey.value);

const form = useForm({
    themes_id: null,
});

watch(
    () => selectedKey.value,
    () => {
        const selectedOption = themeOptions.value.find((theme) => theme.key === selectedKey.value);
        form.themes_id = selectedOption?.id ?? null;
    },
    { immediate: true }
);

const selectTheme = (event) => {
    const key = event.target.value;
    const selectedOption = themeOptions.value.find((theme) => theme.key === key);

    if (!selectedOption?.id) {
        alert('Este tema no existe todavía en la base de datos. Revisa la tabla themes.');
        return;
    }

    if (normalizeThemeName(user.value?.theme?.name) === selectedOption.key) {
        return;
    }

    form.themes_id = selectedOption.id;

    form.post(route('themes.select'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                    Tema visual
                </p>

                <p class="mt-1 text-sm font-black text-[var(--app-text)]">
                    {{ getVisualTheme(user?.theme?.name).displayName }}
                </p>
            </div>

            <div class="rounded-xl bg-[var(--app-card)] p-2 text-[var(--app-primary)]">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M12 3l2.5 5.5L20 11l-5.5 2.5L12 19l-2.5-5.5L4 11l5.5-2.5L12 3z"
                    />
                </svg>
            </div>
        </div>

        <select
            :value="selectedKey"
            class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-card)] text-sm font-black text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
            @change="selectTheme"
        >
            <option
                v-for="theme in themeOptions"
                :key="theme.key"
                :value="theme.key"
            >
                {{ theme.name }}
            </option>
        </select>
    </div>
</template>
