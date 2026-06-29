<script setup>
import { computed } from 'vue';
import { getVisualTheme } from '@/Theme/visualThemes';

const props = defineProps({
    themeName: {
        type: String,
        default: 'Administrador',
    },
});

const theme = computed(() => getVisualTheme(props.themeName));

const cssVariables = computed(() => theme.value.variables);
</script>

<template>
    <div
        :style="cssVariables"
        :data-theme="theme.key"
        class="min-h-screen bg-[var(--app-bg)] text-[var(--app-text)] transition-colors duration-300"
    >
        <slot :theme="theme" />
    </div>
</template>
