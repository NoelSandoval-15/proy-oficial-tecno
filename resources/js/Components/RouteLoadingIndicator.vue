<script setup>
import { onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const loading = ref(false);

onMounted(() => {
    router.on('start', () => {
        loading.value = true;
    });

    router.on('finish', () => {
        setTimeout(() => {
            loading.value = false;
        }, 250);
    });
});
</script>

<template>
    <div v-if="loading" class="fixed inset-x-0 top-0 z-[9999]">
        <div class="h-1 w-full overflow-hidden bg-[var(--app-primary-soft)]">
            <div class="h-full w-1/2 animate-[loadingBar_1s_ease-in-out_infinite] rounded-full bg-[var(--app-primary)]"></div>
        </div>

        <div class="fixed right-5 top-5 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 text-sm font-black text-[var(--app-text)] shadow-xl">
            Cargando vista...
        </div>
    </div>
</template>
