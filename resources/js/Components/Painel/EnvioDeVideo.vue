<script setup>

import { onBeforeUnmount, ref } from 'vue';
import { Film } from 'lucide-vue-next';
import InputError from '@/Components/InputError.vue';
import { proximoId } from '@/Components/ids.js';

const props = defineProps({
    limites: { type: Object, required: true },
    erro: { type: String, default: null },
    rotulo: { type: String, default: 'Arquivo do vídeo (mp4)' },
    jaEnviado: { type: String, default: null },
});

const emit = defineEmits(['arquivo', 'capa']);

const aviso = ref(null);
const resumo = ref(null);
const previa = ref(null);
const campo = ref(null);
const id = proximoId().replace('abas', 'video');

let urlDaPrevia = null;

function limpar() {
    if (urlDaPrevia) {
        URL.revokeObjectURL(urlDaPrevia);
        urlDaPrevia = null;
    }
    previa.value = null;
}

onBeforeUnmount(limpar);

function recusar(mensagem) {
    aviso.value = mensagem;
    resumo.value = null;
    limpar();
    campo.value.value = '';
    emit('arquivo', null);
    emit('capa', null);
}

const mb = (bytes) => (bytes / 1024 / 1024).toFixed(1).replace('.', ',');

function lerMetadados(url) {
    return new Promise((resolver, rejeitar) => {
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        video.onloadedmetadata = () => resolver(video);
        video.onerror = () => rejeitar();
        video.src = url;
    });
}

function tirarQuadro(video) {
    return new Promise((resolver) => {

        video.currentTime = Math.min(1, video.duration / 3);
        video.onseeked = () => {
            const largura = Math.min(video.videoWidth, 960);
            const altura = Math.round(video.videoHeight * (largura / video.videoWidth));
            const tela = document.createElement('canvas');
            tela.width = largura;
            tela.height = altura;
            tela.getContext('2d').drawImage(video, 0, 0, largura, altura);
            tela.toBlob((blob) => resolver(blob ? new File([blob], 'capa.jpg', { type: 'image/jpeg' }) : null), 'image/jpeg', 0.82);
        };
    });
}

async function escolher(evento) {
    const arquivo = evento.target.files[0];
    aviso.value = null;
    limpar();

    if (!arquivo) {
        resumo.value = null;
        emit('arquivo', null);
        return;
    }

    if (arquivo.type && arquivo.type !== 'video/mp4') {
        recusar('O vídeo precisa ser mp4 — é o formato que toca em todo celular. O WhatsApp já exporta assim.');
        return;
    }

    if (arquivo.size > props.limites.mb * 1024 * 1024) {
        recusar(`Este vídeo tem ${mb(arquivo.size)} MB e o limite é ${props.limites.mb} MB. Corte um trecho mais curto, ou suba no YouTube e cole o link.`);
        return;
    }

    urlDaPrevia = URL.createObjectURL(arquivo);

    try {
        const video = await lerMetadados(urlDaPrevia);

        if (video.duration > props.limites.segundos) {
            recusar(`Este vídeo tem ${Math.round(video.duration)} segundos e o limite é ${props.limites.segundos}. Corte o melhor trecho, ou suba no YouTube e cole o link.`);
            return;
        }

        resumo.value = `${Math.round(video.duration)} s · ${mb(arquivo.size)} MB`;
        previa.value = urlDaPrevia;
        emit('arquivo', arquivo);
        emit('capa', await tirarQuadro(video));
    } catch {

        resumo.value = `${mb(arquivo.size)} MB`;
        emit('arquivo', arquivo);
    }
}
</script>

<template>
    <div>
        <label :for="id" class="mb-1 block font-label text-label uppercase text-fg-muted">{{ rotulo }}</label>
        <input
            :id="id"
            ref="campo"
            type="file"
            accept="video/mp4"
            class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent"
            @change="escolher"
        />
        <p class="mt-1 text-xs text-fg-subtle">
            Até {{ limites.mb }} MB e {{ limites.segundos }} segundos, em mp4.
            <template v-if="jaEnviado">Já há um arquivo enviado ({{ jaEnviado }}); escolha outro só para trocar.</template>
        </p>

        <p v-if="resumo" class="mt-2 flex items-center gap-2 text-sm text-fg">
            <Film class="h-4 w-4 text-accent" aria-hidden="true" />
            Pronto para enviar: {{ resumo }}. A capa sai de um quadro do vídeo.
        </p>
        <video v-if="previa" :src="previa" class="mt-2 w-64 rounded-md border border-line" controls muted playsinline />

        <InputError :message="aviso || erro" />
    </div>
</template>
