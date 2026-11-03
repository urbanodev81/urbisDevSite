<script setup>
import { onMounted, ref } from 'vue';

defineProps({ modelValue: { type: [String, Number], default: '' } });
defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        ref="input"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="w-full rounded-md border-line-input bg-surface text-sm text-fg shadow-none
               placeholder:text-fg-subtle
               focus:border-line-strong focus:ring-2 focus:ring-accent-ring
               disabled:cursor-not-allowed disabled:opacity-60"
    />
</template>
