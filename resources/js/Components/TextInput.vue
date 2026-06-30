<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);

const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({
    focus: () => input.value?.focus(),
});
</script>

<template>
    <input
        ref="input"
        :value="modelValue"
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        @input="updateValue"
    />
</template>
