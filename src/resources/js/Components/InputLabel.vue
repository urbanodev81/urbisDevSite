<script setup>
defineProps({ value: { type: String, default: null }, obrigatorio: Boolean });
</script>

<template>
    <label class="mb-1 block font-label text-label uppercase text-fg-muted">
        <slot>{{ value }}</slot>
        <span v-if="obrigatorio" class="text-accent" aria-hidden="true">*</span>
    </label>
</template>
