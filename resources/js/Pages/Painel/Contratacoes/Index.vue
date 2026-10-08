<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { Plus, Phone, Mail } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    pedidos: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    status: { type: Array, default: () => [] },
    resumo: { type: Object, required: true },
    podeVerValores: { type: Boolean, default: false },
    podeGerenciar: { type: Boolean, default: false },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'contratacoes',
    ids: () => props.pedidos.data.map((p) => p.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['pedido', 'pedidos', 'o'],
    liga: null,
});

function filtrar(valor) {
    router.get(route('painel.contratacoes.index'), { status: valor }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Pedidos de contratação" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Contratação</p></template>

        <PageHeader
            titulo="Pedidos"
            descricao="A lista é uma fila de espera, do mais antigo para o mais novo. Quem pergunta na terça sobre o sábado contrata outra banda na quinta."
        >
            <template #acoes>
                <Link v-if="podeGerenciar" :href="route('painel.contratacoes.create')">
                    <PrimaryButton type="button"><Plus class="h-4 w-4" aria-hidden="true" />Registrar pedido</PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <StatCard rotulo="Sem resposta" :valor="resumo.novos" :contexto="resumo.novos === 0 ? 'tudo respondido' : 'aguardando contato'" icone="inbox" :token="resumo.novos > 0 ? 'danger' : 'success'" />
            <StatCard rotulo="Em aberto" :valor="resumo.abertos" contexto="ainda no funil" icone="message-circle" token="info" />
            <StatCard rotulo="Fechados no ano" :valor="resumo.fechadosNoAno" contexto="viraram show" icone="circle-check" token="success" />
        </div>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Na fila" />

        <div v-if="!arquivados" class="mb-5 flex flex-wrap gap-2">
            <button
                v-for="opcao in [{ valor: 'abertos', rotulo: 'Em aberto' }, ...status, { valor: 'todos', rotulo: 'Todos' }]"
                :key="opcao.valor"
                type="button"
                class="min-h-[44px] rounded-md border px-4 text-sm font-semibold transition"
                :class="(filtros.status ?? 'abertos') === opcao.valor ? 'border-line-strong bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:bg-surface-sunken'"
                :aria-pressed="(filtros.status ?? 'abertos') === opcao.valor"
                @click="filtrar(opcao.valor)"
            >{{ opcao.rotulo }}</button>
        </div>

        <div v-if="pedidos.data.length" class="overflow-hidden rounded-lg border border-line bg-surface">
            <ul class="divide-y divide-line">
                <li v-for="pedido in pedidos.data" :key="pedido.uuid" class="flex items-center gap-1 pl-2">
                    <CaixaDeLote :lote="lote" :id="pedido.uuid" :rotulo="`pedido de ${pedido.nome}`" />

                    <component
                        :is="arquivados ? 'div' : Link"
                        :href="arquivados ? undefined : route('painel.contratacoes.show', pedido.uuid)"
                        class="flex min-w-0 flex-1 flex-wrap items-center gap-4 py-4 pl-2 pr-4 transition sm:pr-5"
                        :class="arquivados ? '' : 'hover:bg-surface-sunken'"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-fg">{{ pedido.nome }}</p>
                            <p class="truncate text-sm text-fg-muted">
                                {{ pedido.tipoEvento }}
                                <template v-if="pedido.dataPretendida"> · {{ pedido.dataPretendida }}</template>
                                <template v-if="pedido.cidade"> · {{ pedido.cidade }}</template>
                            </p>
                            <p class="mt-1 flex flex-wrap items-center gap-3 text-xs text-fg-subtle">
                                <span v-if="pedido.telefone" class="inline-flex items-center gap-1"><Phone class="h-3.5 w-3.5" aria-hidden="true" />{{ pedido.telefone }}</span>
                                <span v-if="pedido.email" class="inline-flex items-center gap-1"><Mail class="h-3.5 w-3.5" aria-hidden="true" />{{ pedido.email }}</span>
                                <span>via {{ pedido.origem }}</span>
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <Chip :rotulo="pedido.statusRotulo" :token="pedido.statusToken" :icone="pedido.statusIcone" />
                            <p class="mt-1 text-xs text-fg-subtle [font-variant-numeric:tabular-nums]">
                                {{ pedido.diasEsperando === 0 ? 'chegou hoje' : `há ${pedido.diasEsperando} dia(s)` }}
                            </p>
                            <p v-if="podeVerValores && pedido.valor" class="text-sm font-semibold text-fg [font-variant-numeric:tabular-nums]">
                                R$ {{ pedido.valor }}
                            </p>
                        </div>
                    </component>
                    <BotaoRestaurar v-if="arquivados && podeGerenciar" :lote="lote" :id="pedido.uuid" class="mr-4 shrink-0" />
                </li>
            </ul>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="inbox" titulo="Nenhum pedido nesta lista" descricao="Quando alguém preencher o formulário do site, ele aparece aqui — e o WhatsApp que chegar no direct pode ser registrado à mão.">
            <Link v-if="podeGerenciar" :href="route('painel.contratacoes.create')"><PrimaryButton type="button">Registrar pedido</PrimaryButton></Link>
        </EstadoVazio>

        <nav v-if="pedidos.links.length > 3" class="mt-6 flex flex-wrap gap-1" aria-label="Paginação">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in pedidos.links"
                :key="link.label"
                :href="link.url"
                class="min-h-[44px] rounded-md px-3 py-2 text-sm"
                :class="link.active ? 'bg-accent text-fg-on-accent' : link.url ? 'text-fg-muted hover:bg-surface-sunken' : 'text-fg-subtle opacity-50'"
                v-html="link.label"
            />
        </nav>
    </AuthenticatedLayout>
</template>
