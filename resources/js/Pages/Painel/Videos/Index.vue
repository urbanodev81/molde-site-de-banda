<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { Plus, AlertTriangle, PlayCircle } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    videos: { type: Array, default: () => [] },
    vagas: { type: Object, required: true },
    podeGerenciar: { type: Boolean, default: false },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'videos',
    ids: () => props.videos.map((v) => v.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['vídeo', 'vídeos', 'o'],
    envia: true,
});
</script>

<template>
    <Head title="Vídeos" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Vídeos"
            :descricao="`A aba da home tem ${vagas.limite} vagas. É o que mais pesa na decisão de quem contrata: ninguém fecha show sem ver e ouvir.`"
        >
            <template #acoes>
                <Link v-if="podeGerenciar && vagas.livres > 0" :href="route('painel.videos.create')">
                    <PrimaryButton type="button"><Plus class="h-4 w-4" aria-hidden="true" />Novo vídeo</PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <div
            v-if="vagas.demonstracoes > 0"
            class="mb-6 flex items-start gap-3 rounded-lg border border-warning bg-warning-subtle p-4"
        >
            <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-warning" aria-hidden="true" />
            <div class="text-sm text-warning">
                <p class="font-semibold">
                    {{ vagas.demonstracoes }} de {{ vagas.preenchidas }} vaga(s) preenchida(s) com material de demonstração.
                </p>
                <p class="mt-1">
                    O clipe de demonstração foi gerado por nós, não é a banda tocando — o site marca com selo amarelo.
                    Três vídeos de celular na horizontal, bem gravados, valem mais do que nenhum profissional.
                </p>
            </div>
        </div>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Nas vagas" explicacao="Vai o link de cada vídeo que está no site; os que estão fora do ar ficam de fora." />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="video in videos" :key="video.uuid" class="overflow-hidden rounded-lg border border-line bg-surface">
                <div class="relative aspect-video bg-surface-sunken">

                    <CaixaDeLote :lote="lote" :id="video.uuid" :rotulo="video.titulo" class="absolute left-2 top-2 z-10 rounded-md bg-surface/90" />
                    <img v-if="video.capa" :src="video.capa" :alt="`Capa de ${video.titulo}`" class="h-full w-full object-cover" />
                    <span v-else class="grid h-full place-items-center text-fg-subtle">
                        <PlayCircle class="h-10 w-10" aria-hidden="true" />
                    </span>
                    <span
                        v-if="video.demonstracao"
                        class="absolute left-2 top-2 rounded-full bg-palco-data px-2 py-1 font-label text-label uppercase text-palco-escuro"
                    >Demonstração</span>
                </div>

                <div class="p-4">
                    <h2 class="truncate font-display text-lg font-bold uppercase text-fg">{{ video.titulo }}</h2>
                    <p class="truncate text-sm text-fg-muted">{{ video.ondeFoi || '—' }}<template v-if="video.gravadoEm"> · {{ video.gravadoEm }}</template></p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <Chip :rotulo="video.tipoRotulo" token="info" :icone="video.tipo === 'youtube' ? 'youtube' : (video.tipo === 'link' ? 'link' : 'file-video')" />
                        <Chip v-if="!video.publicado" rotulo="Fora do site" token="fg-subtle" icone="eye-off" />
                        <Chip v-if="!video.reproduzivel" rotulo="Sem arquivo" token="danger" icone="alert-triangle" />
                    </div>

                    <div v-if="podeGerenciar" class="mt-4 flex flex-wrap gap-2">
                        <template v-if="!arquivados">
                            <Link :href="route('painel.videos.edit', video.uuid)"><SecondaryButton type="button">Editar</SecondaryButton></Link>
                            <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="video.titulo" @confirmar="router.delete(route('painel.videos.destroy', video.uuid))" />
                        </template>
                        <BotaoRestaurar v-else :lote="lote" :id="video.uuid" />
                    </div>
                </div>
            </article>

            <Link
                v-for="n in (arquivados ? 0 : vagas.livres)"
                :key="`vaga-${n}`"
                :href="podeGerenciar ? route('painel.videos.create') : '#'"
                class="grid aspect-video place-items-center rounded-lg border-2 border-dashed border-line text-center transition hover:border-line-strong"
            >
                <div>
                    <p class="font-display text-3xl font-black text-fg-subtle [font-variant-numeric:tabular-nums]">
                        {{ vagas.preenchidas + n }}
                    </p>
                    <p class="font-label text-label uppercase text-fg-subtle">Vaga livre</p>
                </div>
            </Link>
        </div>

        <EstadoVazio
            v-if="!videos.length && !arquivados"
            class="mt-6"
            icone="video-off"
            titulo="Nenhum vídeo cadastrado"
            descricao="A aba de vídeos é o que mais converte contratação, e ela está vazia."
        >
            <Link v-if="podeGerenciar" :href="route('painel.videos.create')"><PrimaryButton type="button">Cadastrar vídeo</PrimaryButton></Link>
        </EstadoVazio>
    </AuthenticatedLayout>
</template>
