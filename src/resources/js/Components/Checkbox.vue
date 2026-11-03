<script setup>
defineProps({ checked: { type: [Array, Boolean], default: false }, value: { default: null } });
defineEmits(['update:checked']);
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        :checked="Array.isArray(checked) ? checked.includes(value) : checked"
        @change="$emit('update:checked', Array.isArray(checked)
            ? ($event.target.checked
                ? [...checked, value]
                : checked.filter((v) => v !== value))
            : $event.target.checked)"
        class="h-5 w-5 rounded border-line-input bg-surface text-accent
               focus:ring-2 focus:ring-accent-ring"
    />
</template>
