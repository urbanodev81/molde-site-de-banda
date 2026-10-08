<script setup>

import { computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import EnvioDeVideo from '@/Components/Painel/EnvioDeVideo.vue';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    form: { type: Object, required: true },
    biblioteca: { type: Array, default: () => [] },
    shows: { type: Array, default: () => [] },
    limites: { type: Object, required: true },

    youtubeAntigo: { type: String, default: null },
});

const modos = computed(() => [
    ...(props.youtubeAntigo ? [{ valor: 'manter', rotulo: 'Manter o YouTube atual' }] : []),
    { valor: 'nenhum', rotulo: 'Sem vídeo' },
    { valor: 'biblioteca', rotulo: 'Já enviado' },
    { valor: 'link', rotulo: 'Colar link' },
    { valor: 'arquivo', rotulo: 'Enviar arquivo' },
]);

const classeDoSelect = 'min-h-[44px] w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <fieldset class="rounded-md border border-line p-4">
        <legend class="px-1 font-label text-label uppercase text-fg-muted">Vídeo da versão que a banda toca</legend>

        <div class="mb-3 flex flex-wrap gap-2" role="radiogroup" aria-label="De onde vem o vídeo">
            <button
                v-for="modo in modos"
                :key="modo.valor"
                type="button"
                role="radio"
                class="min-h-[44px] rounded-md border px-3 text-sm font-semibold transition"
                :class="form.video_modo === modo.valor ? 'border-line-strong bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:bg-surface-sunken'"
                :aria-checked="form.video_modo === modo.valor"
                @click="form.video_modo = modo.valor"
            >{{ modo.rotulo }}</button>
        </div>

        <p v-if="form.video_modo === 'manter'" class="text-sm text-fg-subtle">
            Continua o vídeo do YouTube cadastrado antes: youtu.be/{{ youtubeAntigo }}
        </p>

        <div v-else-if="form.video_modo === 'biblioteca'">
            <SelectComBusca v-model="form.video_id" :opcoes="biblioteca" rotulo="Vídeo da biblioteca" opcao-vazia="Escolha um vídeo…" />
            <p class="mt-1 text-sm text-fg-subtle">Os vídeos da tela de vídeos e os enviados nas páginas dos shows.</p>
            <InputError :message="form.errors.video_id" />
        </div>

        <template v-else-if="form.video_modo === 'link' || form.video_modo === 'arquivo'">
            <div v-if="form.video_modo === 'link'">
                <TextInput v-model="form.video_link" placeholder="YouTube, Vimeo, Instagram ou TikTok" aria-label="Link do vídeo" />
                <p class="mt-1 text-sm text-fg-subtle">Cole do jeito que sai do celular. O vídeo continua no site de origem e não pesa nada aqui.</p>
                <InputError :message="form.errors.video_link" />
            </div>

            <EnvioDeVideo
                v-else
                :limites="limites"
                :erro="form.errors.video_arquivo"
                @arquivo="form.video_arquivo = $event"
                @capa="form.video_capa = $event"
            />

            <div class="mt-3">
                <InputLabel value="Gravado em qual show (opcional)" />
                <SelectComBusca rotulo="Gravado em qual show (opcional)" v-model="form.video_show_id" :opcoes="shows" opcao-vazia="Nenhum — ensaio, estúdio ou avulso" />
                <p class="mt-1 text-sm text-fg-subtle">Com show, o mesmo vídeo aparece também na página daquela noite.</p>
            </div>
        </template>
    </fieldset>
</template>
