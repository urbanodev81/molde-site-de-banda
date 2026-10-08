<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { Plus, Map, ExternalLink } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import Paginacao from '@/Components/Paginacao.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    locais: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    podeGerenciar: { type: Boolean, default: false },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'locais',
    ids: () => props.locais.data.map((l) => l.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['local', 'locais', 'o'],
    liga: ['Ativar', 'Desativar'],
});
</script>

<template>
    <Head title="Locais" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Agenda</p></template>

        <PageHeader
            titulo="Locais"
            descricao="Cadastrar o local uma vez evita redigitar endereço a cada show — e corrigi-lo corrige toda a agenda."
        >
            <template #acoes>
                <Link v-if="podeGerenciar" :href="route('painel.locais.create')">
                    <PrimaryButton type="button"><Plus class="h-4 w-4" aria-hidden="true" />Novo local</PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Cadastrados" />

        <div v-if="locais.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="local in locais.data" :key="local.uuid" class="min-w-0 flex flex-col rounded-lg border border-line bg-surface p-5">
                <div class="flex items-start gap-3">
                    <CaixaDeLote :lote="lote" :id="local.uuid" :rotulo="local.nome" />
                    <img
                        v-if="local.logo"
                        :src="local.logo"
                        :alt="`Logo do ${local.nome}`"
                        class="h-12 w-12 shrink-0 rounded-md object-contain"
                    />
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-display text-xl font-bold uppercase text-fg">{{ local.nome }}</h2>
                        <p v-if="local.tipo" class="font-label text-label uppercase text-fg-subtle">{{ local.tipo }}</p>
                        <p class="truncate text-sm text-fg-muted">{{ local.endereco || 'Endereço não preenchido' }}</p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <Chip :rotulo="`${local.shows} show(s)`" token="info" icone="calendar-days" />
                    <Chip v-if="!local.ativa" rotulo="Inativa" token="fg-subtle" icone="circle-slash" />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-line pt-4 text-sm">
                    <a v-if="local.mapa" :href="local.mapa" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-accent hover:underline">
                        <Map class="h-4 w-4" aria-hidden="true" />Mapa
                    </a>
                    <a v-if="local.site || local.instagram" :href="local.site || local.instagram" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-accent hover:underline">
                        <ExternalLink class="h-4 w-4" aria-hidden="true" />Página do local
                    </a>
                </div>

                <div v-if="podeGerenciar" class="mt-4 flex flex-wrap gap-2">
                    <template v-if="!arquivados">
                        <Link :href="route('painel.locais.edit', local.uuid)">
                            <SecondaryButton type="button">Editar</SecondaryButton>
                        </Link>
                        <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="local.nome" @confirmar="router.delete(route('painel.locais.destroy', local.uuid))" />
                    </template>
                    <BotaoRestaurar v-else :lote="lote" :id="local.uuid" />
                </div>
            </article>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="map-pin" titulo="Nenhum local cadastrado" descricao="Comece pelo local onde a banda mais toca.">
            <Link v-if="podeGerenciar" :href="route('painel.locais.create')">
                <PrimaryButton type="button">Cadastrar local</PrimaryButton>
            </Link>
        </EstadoVazio>

        <Paginacao :links="locais.links" />
    </AuthenticatedLayout>
</template>
