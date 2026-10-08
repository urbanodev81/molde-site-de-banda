<script setup>
import { ref, watch } from 'vue';
import DangerButton from './DangerButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const props = defineProps({
    quantidade: { type: Number, required: true },

    acoes: { type: Array, default: () => [] },

    nome: { type: Array, default: () => ['item', 'itens'] },
});

const emit = defineEmits(['acao', 'limpar']);

const armada = ref(null);

watch(() => props.quantidade, () => { armada.value = null; });

function acionar(acao) {
    if (acao.confirmar) {
        armada.value = acao;
        return;
    }

    emit('acao', acao);
}

function confirmar() {
    emit('acao', armada.value);
    armada.value = null;
}
</script>

<template>
    <div
        v-if="acoes.length && quantidade"
        role="region"
        aria-label="Ações para os itens selecionados"
        class="mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-line-strong bg-accent-subtle p-3"
    >
        <span class="text-sm font-semibold text-fg tabular-nums" aria-live="polite">
            {{ quantidade }} {{ quantidade === 1 ? `${nome[0]} marcad${nome[2] ?? 'a'}` : `${nome[1]} marcad${nome[2] ?? 'a'}s` }}
        </span>

        <div v-if="!armada" class="flex flex-wrap gap-2">
            <SecondaryButton v-for="acao in acoes" :key="acao.key" type="button" @click="acionar(acao)">
                <component :is="acao.icon" class="h-4 w-4" aria-hidden="true" />{{ acao.label }}
            </SecondaryButton>
        </div>

        <div v-else class="flex flex-wrap items-center gap-2">
            <span class="text-sm text-fg">{{ armada.confirmar }}</span>
            <DangerButton type="button" @click="confirmar">
                <component :is="armada.icon" class="h-4 w-4" aria-hidden="true" />Sim, {{ armada.label.toLowerCase() }}
            </DangerButton>
            <SecondaryButton type="button" @click="armada = null">Cancelar</SecondaryButton>
        </div>

        <button type="button" class="ml-auto min-h-[44px] px-2 text-sm font-semibold text-accent hover:underline" @click="emit('limpar')">
            Limpar seleção
        </button>
    </div>
</template>
