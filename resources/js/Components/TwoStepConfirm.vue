<script setup>
import { ref, watch } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';
import DangerButton from './DangerButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const props = defineProps({
    nome: { type: String, default: null },
    rotulo: { type: String, default: 'Remover' },

    verbo: { type: String, default: 'Remover' },
});

const emit = defineEmits(['confirmar']);

const armado = ref(false);
let relogio = null;

watch(armado, (valor) => {
    clearTimeout(relogio);

    if (valor) {
        relogio = setTimeout(() => (armado.value = false), 5000);
    }
});
</script>

<template>
    <div class="inline-flex items-center gap-2">
        <SecondaryButton v-if="!armado" @click="armado = true">
            <AlertTriangle class="h-4 w-4" aria-hidden="true" />
            {{ rotulo }}
        </SecondaryButton>

        <template v-else>
            <span class="text-sm text-fg-muted">
                {{ verbo }}<template v-if="nome"> <strong class="text-fg">{{ nome }}</strong></template>?
            </span>
            <DangerButton @click="emit('confirmar'); armado = false">Sim, {{ verbo.toLowerCase() }}</DangerButton>
            <SecondaryButton @click="armado = false">Cancelar</SecondaryButton>
        </template>
    </div>
</template>
