<script setup>

import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { configCaptcha } from '@/captcha';

const emit = defineEmits(['update:modelValue']);

defineProps({
    modelValue: { type: String, default: '' },
});

const config = configCaptcha();
const el = ref(null);
const estado = ref('carregando');
let ultimoReset = 0;

const rotulo = computed(() => ({
    carregando: 'Verificando que você não é um robô…',
    verificando: 'Verificando que você não é um robô…',
    verificado: 'Verificação concluída.',
    erro: 'A verificação falhou. Recarregue a página para tentar de novo.',
}[estado.value]));

onMounted(async () => {
    if (config.driver === 'altcha') {

        await import('altcha');

        await import('altcha/i18n/pt-br');

        el.value?.addEventListener('statechange', (evento) => {
            const s = evento.detail?.state;

            if (s === 'verified') {
                estado.value = 'verificado';
                emit('update:modelValue', evento.detail.payload ?? '');

                return;
            }

            estado.value = s === 'error' ? 'erro' : 'verificando';
            emit('update:modelValue', '');
        });

        return;
    }

});

function reset() {

    if (Date.now() - ultimoReset < 1000) {
        return;
    }
    ultimoReset = Date.now();

    emit('update:modelValue', '');

    if (config.driver === 'altcha') {
        estado.value = 'verificando';
        el.value?.reset?.();

        el.value?.verify?.();

        return;
    }

}

defineExpose({ reset });

onBeforeUnmount(router.on('finish', (evento) => {
    if (evento.detail?.visit?.method !== 'get') {
        reset();
    }
}));

</script>

<template>
    <div v-if="config.driver !== 'null'">

        <altcha-widget
            v-if="config.driver === 'altcha'"
            ref="el"
            :challenge="config.challenge_url"
            auto="onload"
            language="pt-br"
            hidefooter
            hidelogo
        />

        <p
            class="mt-2 text-xs"
            :class="estado === 'erro' ? 'text-red-700' : 'text-gray-600'"
            role="status"
            aria-live="polite"
        >
            {{ rotulo }}
        </p>
    </div>
</template>
