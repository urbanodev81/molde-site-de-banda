<script setup>
import * as icones from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    rotulo: { type: String, required: true },
    valor: { type: [Number, String], default: 0 },
    contexto: { type: String, default: null },
    icone: { type: String, default: 'activity' },
    token: { type: String, default: 'accent' },
});

const Icone = computed(() => {
    const nome = props.icone.split('-').map((p) => p[0].toUpperCase() + p.slice(1)).join('');

    return icones[nome] ?? icones.Activity;
});

const tinta = computed(() => ({
    accent: 'bg-accent-subtle text-accent',
    success: 'bg-success-subtle text-success',
    warning: 'bg-warning-subtle text-warning',
    danger: 'bg-danger-subtle text-danger',
    info: 'bg-info-subtle text-info',
}[props.token] ?? 'bg-accent-subtle text-accent'));
</script>

<template>
    <div class="flex items-center gap-4 rounded-lg border border-line bg-surface p-5">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-md" :class="tinta">
            <component :is="Icone" class="h-5 w-5" aria-hidden="true" />
        </span>

        <div class="min-w-0">
            <p class="font-label text-label uppercase text-fg-subtle">{{ rotulo }}</p>
            <p class="font-display text-3xl font-extrabold leading-none text-fg [font-variant-numeric:tabular-nums]">
                {{ valor }}
            </p>
            <p v-if="contexto" class="mt-1 truncate text-sm text-fg-muted">{{ contexto }}</p>
        </div>
    </div>
</template>
