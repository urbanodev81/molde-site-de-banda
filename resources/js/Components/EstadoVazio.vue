<script setup>
import * as icones from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    icone: { type: String, default: 'inbox' },
    titulo: { type: String, required: true },
    descricao: { type: String, default: null },
});

const Icone = computed(() => {
    const nome = props.icone.split('-').map((p) => p[0].toUpperCase() + p.slice(1)).join('');

    return icones[nome] ?? icones.Inbox;
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-line px-6 py-12 text-center">
        <span class="grid h-12 w-12 place-items-center rounded-full bg-surface-sunken text-fg-subtle">
            <component :is="Icone" class="h-6 w-6" aria-hidden="true" />
        </span>
        <p class="font-display text-xl font-bold uppercase text-fg">{{ titulo }}</p>
        <p v-if="descricao" class="max-w-md text-sm text-fg-muted">{{ descricao }}</p>
        <div class="mt-1"><slot /></div>
    </div>
</template>
