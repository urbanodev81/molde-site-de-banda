<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({

    links: { type: Array, default: () => [] },
});
</script>

<template>
    <nav v-if="links.length > 3" class="mt-6 flex flex-wrap gap-1" aria-label="Paginação">
        <component
            :is="link.url ? Link : 'span'"
            v-for="link in links"
            :key="link.label"
            :href="link.url"
            class="min-h-[44px] rounded-md px-3 py-2 text-sm"
            :class="link.active
                ? 'bg-accent text-fg-on-accent'
                : link.url ? 'text-fg-muted hover:bg-surface-sunken' : 'text-fg-subtle opacity-50'"
            :aria-current="link.active ? 'page' : undefined"
            v-html="link.label"
        />
    </nav>
</template>
