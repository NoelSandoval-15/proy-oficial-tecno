<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { getVisualTheme } from '@/Theme/visualThemes';

const page = usePage();

const themes = computed(() => page.props.visualThemes ?? []);
const user = computed(() => page.props.auth.user);
const selectedThemeId = computed(() => user.value?.themes_id);

const form = useForm({
    themes_id: selectedThemeId.value,
});

watch(selectedThemeId, (value) => {
    form.themes_id = value;
});

const selectTheme = () => {
    if (!form.themes_id || Number(form.themes_id) === Number(selectedThemeId.value)) {
        return;
    }

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
                    {{ user?.theme?.name ?? 'Sin tema' }}
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
            v-model="form.themes_id"
            class="w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-card)] text-sm font-black text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
            @change="selectTheme"
        >
            <option
                v-for="theme in themes"
                :key="theme.id"
                :value="theme.id"
            >
                {{ getVisualTheme(theme.name).displayName }}
            </option>
        </select>
    </div>
</template>
