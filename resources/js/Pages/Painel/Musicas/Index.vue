<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { ref } from 'vue';
import { Plus, Star, EyeOff } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import VersaoDaMusica from '@/Components/Painel/VersaoDaMusica.vue';

const props = defineProps({
    musicas: { type: Array, default: () => [] },
    publicadas: { type: Number, default: 0 },
    filtros: { type: Object, default: () => ({}) },
    podeGerenciar: { type: Boolean, default: false },
    biblioteca: { type: Array, default: () => [] },
    shows: { type: Array, default: () => [] },
    limites: { type: Object, default: () => ({ mb: 20, segundos: 90 }) },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'musicas',
    ids: () => props.musicas.map((m) => m.id),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['música', 'músicas', 'a'],
    envia: true,
});

const semVideo = { video_modo: 'nenhum', video_id: '', video_link: '', video_arquivo: null, video_capa: null, video_show_id: '' };

const form = useForm({ titulo: '', artista: '', estilo: '', tom: '', ano: '', ordem: 0, publicada: true, destaque: false, ...semVideo });

function adicionar() {
    form.post(route('painel.musicas.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
}

const editando = ref(null);
const edicao = useForm({ titulo: '', artista: '', estilo: '', tom: '', ano: '', ordem: 0, publicada: true, destaque: false, ...semVideo });
const youtubeAntigo = ref(null);

function abrirEdicao(musica) {
    editando.value = musica.id;
    Object.assign(edicao, {
        titulo: musica.titulo, artista: musica.artista ?? '', estilo: musica.estilo ?? '',
        tom: musica.tom ?? '', ano: musica.ano ?? '', ordem: musica.ordem,
        publicada: musica.publicada, destaque: musica.destaque,
        ...semVideo,
        video_modo: musica.video_id ? 'biblioteca' : (musica.youtube_id && !musica.video_id ? 'manter' : 'nenhum'),
        video_id: musica.video_id ?? '',
    });
    youtubeAntigo.value = musica.video_id ? null : musica.youtube_id;
}

function salvarEdicao(musica) {
    edicao
        .transform((d) => ({ ...d, _method: 'put' }))
        .post(route('painel.musicas.update', musica.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => (editando.value = null),
        });
}
</script>

<template>
    <Head title="Repertório" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Repertório"
            :descricao="`${publicadas} música(s) publicada(s). A análise pede de 15 a 20 — é a segunda pergunta de todo contratante, logo depois da data.`"
        />

        <form v-if="podeGerenciar && !arquivados" class="mb-6 rounded-lg border border-line bg-surface p-5" @submit.prevent="adicionar">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Adicionar música</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2"><InputLabel value="Título" obrigatorio /><TextInput v-model="form.titulo" /><InputError :message="form.errors.titulo" /></div>
                <div class="lg:col-span-2"><InputLabel value="Artista original" /><TextInput v-model="form.artista" /><InputError :message="form.errors.artista" /></div>
                <div><InputLabel value="Estilo" /><TextInput v-model="form.estilo" placeholder="rock nacional" /></div>
            </div>

            <div class="mt-4">
                <VersaoDaMusica :form="form" :biblioteca="biblioteca" :shows="shows" :limites="limites" />
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing"><Plus class="h-4 w-4" aria-hidden="true" />Adicionar</PrimaryButton>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.publicada" />Publicada</label>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.destaque" />Destaque na home</label>
            </div>
        </form>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="No repertório" explicacao="Vão as músicas publicadas no repertório do site; as que estão fora ficam de fora." />

        <div v-if="musicas.length" class="overflow-hidden rounded-lg border border-line bg-surface">
            <ul class="divide-y divide-line">
                <li v-for="musica in musicas" :key="musica.id" class="px-4 py-3 sm:px-5">
                    <div v-if="editando !== musica.id" class="flex flex-wrap items-center gap-3">
                        <CaixaDeLote :lote="lote" :id="musica.id" :rotulo="musica.titulo" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-fg">
                                {{ musica.titulo }}
                                <span v-if="musica.artista" class="font-normal text-fg-muted">— {{ musica.artista }}</span>
                            </p>
                            <p class="truncate text-sm text-fg-subtle">
                                <template v-if="musica.estilo">{{ musica.estilo }}</template>
                                <template v-if="musica.tom"> · tom {{ musica.tom }}</template>
                                <template v-if="musica.video"> · vídeo: {{ musica.video }}</template>
                                <template v-else-if="musica.youtube_id"> · com vídeo do YouTube</template>
                            </p>
                        </div>
                        <Star v-if="musica.destaque" class="h-4 w-4 text-accent" aria-label="Destaque na home" />
                        <EyeOff v-if="!musica.publicada" class="h-4 w-4 text-fg-subtle" aria-label="Não publicada" />
                        <div v-if="podeGerenciar" class="flex gap-2">
                            <template v-if="!arquivados">
                                <SecondaryButton type="button" @click="abrirEdicao(musica)">Editar</SecondaryButton>
                                <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="musica.titulo" @confirmar="router.delete(route('painel.musicas.destroy', musica.id), { preserveScroll: true })" />
                            </template>
                            <BotaoRestaurar v-else :lote="lote" :id="musica.id" />
                        </div>
                    </div>

                    <form v-else class="grid gap-3 sm:grid-cols-6" @submit.prevent="salvarEdicao(musica)">
                        <TextInput v-model="edicao.titulo" class="sm:col-span-2" aria-label="Título" />
                        <TextInput v-model="edicao.artista" class="sm:col-span-2" aria-label="Artista" />
                        <TextInput v-model="edicao.tom" class="sm:col-span-1" aria-label="Tom" placeholder="Tom" />
                        <div class="sm:col-span-6">
                            <VersaoDaMusica :form="edicao" :biblioteca="biblioteca" :shows="shows" :limites="limites" :youtube-antigo="youtubeAntigo" />
                        </div>
                        <div class="flex gap-2 sm:col-span-6">
                            <PrimaryButton :disabled="edicao.processing">Salvar</PrimaryButton>
                            <SecondaryButton type="button" @click="editando = null">Cancelar</SecondaryButton>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 sm:col-span-6">
                            <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.publicada" />Publicada</label>
                            <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.destaque" />Destaque</label>
                        </div>
                    </form>
                </li>
            </ul>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="music-4" titulo="Repertório em branco" descricao="Sem a lista, a página do repertório não existe para quem contrata." />
    </AuthenticatedLayout>
</template>
