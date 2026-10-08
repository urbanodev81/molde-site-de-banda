<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Video, Inbox, History, AlertTriangle, Plus, ArrowRight } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import Chip from '@/Components/Chip.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    proximoShow: { type: Object, default: null },
    kpis: { type: Object, required: true },
    pedidosAbertos: { type: Array, default: () => [] },
    pendencias: { type: Array, default: () => [] },
});

const tokenDaGravidade = { alta: 'danger', media: 'warning', baixa: 'info' };
const rotuloDaGravidade = { alta: 'Trava a publicação', media: 'Importante', baixa: 'Quando der' };
</script>

<template>
    <Head title="Painel" />

    <AuthenticatedLayout>
        <template #cabecalho>
            <p class="font-label text-label uppercase text-fg-subtle">Painel</p>
        </template>

        <PageHeader
            titulo="Onde a banda está"
            descricao="A agenda, quem está esperando resposta e o que ainda falta para o site ficar completo."
        >
            <template #acoes>
                <Link :href="route('painel.shows.create')">
                    <PrimaryButton type="button">
                        <Plus class="h-4 w-4" aria-hidden="true" />
                        Novo show
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <section
            v-if="proximoShow"
            class="mb-6 overflow-hidden rounded-lg border border-line-strong bg-surface"
        >
            <div class="grid gap-6 p-6 sm:grid-cols-[auto_1fr] sm:items-center">
                <div class="text-center sm:text-left">
                    <p class="font-label text-label uppercase text-accent">Próximo show</p>
                    <p class="mt-1 font-display text-5xl font-black uppercase leading-none text-fg [font-variant-numeric:tabular-nums]">
                        {{ proximoShow.quandoLegivel }}
                    </p>
                </div>

                <div class="border-t border-line pt-4 sm:border-l sm:border-t-0 sm:pl-6 sm:pt-0">
                    <p class="font-display text-2xl font-bold uppercase text-fg">{{ proximoShow.nome }}</p>
                    <p v-if="proximoShow.endereco" class="mt-1 font-label text-label uppercase text-fg-muted">
                        {{ proximoShow.endereco }}
                    </p>
                    <Link
                        :href="route('painel.shows.show', proximoShow.uuid)"
                        class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-accent hover:underline"
                    >
                        Abrir o show
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </section>

        <EstadoVazio
            v-else
            class="mb-6"
            icone="calendar-off"
            titulo="Nenhum show marcado"
            descricao="É a informação que mais gente procura no site. Enquanto não houver data, a home mostra a agenda passada."
        >
            <Link :href="route('painel.shows.create')">
                <PrimaryButton type="button">Cadastrar o próximo show</PrimaryButton>
            </Link>
        </EstadoVazio>

        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                rotulo="Shows marcados"
                :valor="kpis.showsFuturos"
                :contexto="kpis.showsFuturos === 0 ? 'nenhum ainda' : 'nas próximas datas'"
                icone="calendar-days"
                :token="kpis.showsFuturos === 0 ? 'danger' : 'success'"
            />
            <StatCard
                rotulo="Shows no ano"
                :valor="kpis.showsNoAno"
                contexto="já realizados"
                icone="history"
                token="info"
            />
            <StatCard
                rotulo="Vagas de vídeo"
                :valor="kpis.vagasDeVideo"
                contexto="livres na aba da home"
                icone="video"
                :token="kpis.vagasDeVideo > 0 ? 'warning' : 'success'"
            />
            <StatCard
                v-if="kpis.pedidosEsperando !== null"
                rotulo="Sem resposta"
                :valor="kpis.pedidosEsperando"
                :contexto="kpis.pedidosEsperando === 0 ? 'tudo respondido' : 'pedidos aguardando'"
                icone="inbox"
                :token="kpis.pedidosEsperando > 0 ? 'danger' : 'success'"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            <section v-if="pedidosAbertos.length || kpis.pedidosEsperando !== null" class="rounded-lg border border-line bg-surface">
                <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 class="font-display text-xl font-bold uppercase text-fg">Esperando resposta</h2>
                    <Link :href="route('painel.contratacoes.index')" class="text-sm font-semibold text-accent hover:underline">
                        Ver todos
                    </Link>
                </header>

                <ul v-if="pedidosAbertos.length" class="divide-y divide-line">
                    <li v-for="pedido in pedidosAbertos" :key="pedido.uuid">
                        <Link
                            :href="route('painel.contratacoes.show', pedido.uuid)"
                            class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-surface-sunken"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-fg">{{ pedido.nome }}</p>
                                <p class="truncate text-sm text-fg-muted">
                                    {{ pedido.tipoEvento }}
                                    <template v-if="pedido.dataPretendida"> · {{ pedido.dataPretendida }}</template>
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <Chip :rotulo="pedido.statusRotulo" token="info" />
                                <p class="mt-1 text-xs text-fg-subtle [font-variant-numeric:tabular-nums]">
                                    {{ pedido.diasEsperando === 0 ? 'hoje' : `há ${pedido.diasEsperando} dia(s)` }}
                                </p>
                            </div>
                        </Link>
                    </li>
                </ul>

                <p v-else class="px-5 py-8 text-center text-sm text-fg-muted">
                    Nenhum pedido esperando. Quando alguém preencher o formulário do site, ele aparece aqui.
                </p>
            </section>

            <section class="rounded-lg border border-line bg-surface">
                <header class="border-b border-line px-5 py-4">
                    <h2 class="font-display text-xl font-bold uppercase text-fg">O que falta</h2>
                    <p class="mt-1 text-sm text-fg-muted">
                        Calculado do que está cadastrado — não é uma lista que alguém precisa manter.
                    </p>
                </header>

                <ul v-if="pendencias.length" class="divide-y divide-line">
                    <li v-for="item in pendencias" :key="item.chave" class="px-5 py-4">
                        <div class="flex items-start gap-3">
                            <AlertTriangle
                                class="mt-0.5 h-5 w-5 shrink-0"
                                :class="{
                                    alta: 'text-danger',
                                    media: 'text-warning',
                                    baixa: 'text-info',
                                }[item.gravidade]"
                                aria-hidden="true"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-fg">{{ item.titulo }}</p>
                                    <Chip
                                        :rotulo="rotuloDaGravidade[item.gravidade]"
                                        :token="tokenDaGravidade[item.gravidade]"
                                    />
                                </div>
                                <p class="mt-1 text-sm text-fg-muted">{{ item.detalhe }}</p>
                                <Link
                                    v-if="item.rota"
                                    :href="route(item.rota)"
                                    class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-accent hover:underline"
                                >
                                    Resolver
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Link>
                            </div>
                        </div>
                    </li>
                </ul>

                <div v-else class="px-5 py-8 text-center">
                    <p class="font-display text-2xl font-bold uppercase text-success">Nada pendente</p>
                    <p class="mt-1 text-sm text-fg-muted">
                        Agenda, integrantes, vídeos, repertório e configuração: tudo preenchido.
                    </p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
