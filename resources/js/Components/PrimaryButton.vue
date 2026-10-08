<script setup>

defineProps({ type: { type: String, default: 'submit' } });
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2 rounded-md bg-accent px-4 py-2
               text-sm font-semibold text-fg-on-accent transition
               hover:bg-accent-hover focus-visible:outline focus-visible:outline-2
               focus-visible:outline-offset-2 focus-visible:outline-accent-ring
               disabled:cursor-not-allowed disabled:opacity-50
               min-h-[44px]"
    >
        <slot />
    </button>
</template>
