<script setup>
import { Trash2 } from 'lucide-vue-next';

defineProps({
    type: { type: String, default: 'button' },
    icone: { type: Boolean, default: true },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2 rounded-md bg-danger px-4 py-2
               text-sm font-semibold text-surface transition
               hover:opacity-90 focus-visible:outline focus-visible:outline-2
               focus-visible:outline-offset-2 focus-visible:outline-danger
               disabled:cursor-not-allowed disabled:opacity-50
               min-h-[44px]"
    >
        <Trash2 v-if="icone" class="h-4 w-4" aria-hidden="true" />
        <slot />
    </button>
</template>
