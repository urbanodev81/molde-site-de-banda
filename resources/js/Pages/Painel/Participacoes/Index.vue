<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { Plus, EyeOff } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    participacoes: { type: Array, default: () => [] },
    podeGerenciar: { type: Boolean, default: false },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'participacoes',
    ids: () => props.participacoes.map((p) => p.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['participação', 'participações', 'a'],
});
</script>

<template>
    <Head title="Participações especiais" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Participações especiais"
            descricao="Quem subiu ao palco com a banda sem ser da banda. Aparece na página A banda e na página de cada noite em que tocou — só com autorização de imagem registrada."
        >
            <template #acoes>
                <Link v-if="podeGerenciar" :href="route('painel.participacoes.create')">
                    <PrimaryButton type="button"><Plus class="h-4 w-4" aria-hidden="true" />Nova participação</PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Cadastradas" />

        <div v-if="participacoes.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="pessoa in participacoes" :key="pessoa.uuid" class="min-w-0 rounded-lg border border-line bg-surface p-5">
                <div class="flex items-start gap-4">
                    <CaixaDeLote :lote="lote" :id="pessoa.uuid" :rotulo="pessoa.nome" />
                    <img v-if="pessoa.foto" :src="pessoa.foto" :alt="`Foto de ${pessoa.nome}`" class="h-16 w-16 shrink-0 rounded-full border border-line object-cover" />
                    <span v-else class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-surface-sunken font-display text-2xl font-bold text-fg-subtle">
                        {{ pessoa.nome.charAt(0) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-display text-xl font-bold uppercase text-fg">{{ pessoa.nome }}</h2>
                        <p class="truncate text-sm text-fg-muted">{{ pessoa.funcao || 'Função a definir' }}</p>
                        <p class="font-label text-label uppercase text-fg-subtle">
                            {{ pessoa.shows }} {{ pessoa.shows === 1 ? 'noite' : 'noites' }} com a banda
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Chip
                        :rotulo="pessoa.autorizada ? `Autorizada em ${pessoa.autorizadaEm}` : 'Sem autorização'"
                        :token="pessoa.autorizada ? 'success' : 'danger'"
                        :icone="pessoa.autorizada ? 'shield-check' : 'shield-alert'"
                    />
                    <Chip v-if="!pessoa.publicada" rotulo="Não publicada" token="fg-subtle" icone="circle-slash" />
                </div>

                <div v-if="pessoa.motivos.length" class="mt-3 flex items-start gap-2 rounded-md bg-warning-subtle p-3 text-sm text-warning">
                    <EyeOff class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    <span>Não aparece no site: {{ pessoa.motivos.join('; ') }}.</span>
                </div>

                <div v-if="podeGerenciar" class="mt-4 flex flex-wrap gap-2">
                    <template v-if="!arquivados">
                        <Link :href="route('painel.participacoes.edit', pessoa.uuid)"><SecondaryButton type="button">Editar</SecondaryButton></Link>
                        <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="pessoa.nome" @confirmar="router.delete(route('painel.participacoes.destroy', pessoa.uuid))" />
                    </template>
                    <BotaoRestaurar v-else :lote="lote" :id="pessoa.uuid" />
                </div>
            </article>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="sparkles" titulo="Nenhuma participação cadastrada" descricao="Convidou alguém para uma noite? Cadastre aqui e ligue às noites em que tocou.">
            <Link v-if="podeGerenciar" :href="route('painel.participacoes.create')">
                <PrimaryButton type="button">Cadastrar participação</PrimaryButton>
            </Link>
        </EstadoVazio>
    </AuthenticatedLayout>
</template>
