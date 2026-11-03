<script setup>
defineProps({ type: { type: String, default: 'button' } });
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2 rounded-md border border-line-input
               bg-surface px-4 py-2 text-sm font-semibold text-fg transition
               hover:bg-surface-sunken focus-visible:outline focus-visible:outline-2
               focus-visible:outline-offset-2 focus-visible:outline-accent-ring
               disabled:cursor-not-allowed disabled:opacity-50
               min-h-[44px]"
    >
        <slot />
    </button>
</template>
