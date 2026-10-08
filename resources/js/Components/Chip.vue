<script setup>
import * as icones from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    rotulo: { type: String, required: true },
    token: { type: String, default: 'accent' },
    icone: { type: String, default: null },
});

const Icone = computed(() => {
    if (!props.icone) return null;

    const nome = props.icone.split('-').map((p) => p[0].toUpperCase() + p.slice(1)).join('');

    return icones[nome] ?? null;
});

const tinta = computed(() => ({
    accent: 'bg-accent-subtle text-accent',
    success: 'bg-success-subtle text-success',
    warning: 'bg-warning-subtle text-warning',
    danger: 'bg-danger-subtle text-danger',
    info: 'bg-info-subtle text-info',
    'fg-subtle': 'bg-surface-sunken text-fg-subtle',
}[props.token] ?? 'bg-surface-sunken text-fg-subtle'));
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-label text-label uppercase"
        :class="tinta"
    >
        <component :is="Icone" v-if="Icone" class="h-3.5 w-3.5" aria-hidden="true" />
        {{ rotulo }}
    </span>
</template>
