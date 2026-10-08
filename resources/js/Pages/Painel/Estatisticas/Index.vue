<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import { ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
    dias: { type: Number, required: true },
    janelas: { type: Array, required: true },
    kpis: { type: Object, required: true },
    serie: { type: Array, default: () => [] },
    paginas: { type: Array, default: () => [] },
    shows: { type: Array, default: () => [] },
});

function trocarJanela(dias) {
    router.get(route('painel.estatisticas.index'), { dias }, { preserveState: true, replace: true });
}

const pico = computed(() => Math.max(...props.serie.map((d) => d.visitas), 0) || 1);

const temVisita = computed(() => props.kpis.totalDesdeOInicio > 0);

const variacao = computed(() => {
    const v = props.kpis.variacao;

    if (v === null) return { texto: 'sem base de comparação', token: 'info' };
    if (v > 0) return { texto: `${v}% a mais que no período anterior`, token: 'success' };
    if (v < 0) return { texto: `${Math.abs(v)}% a menos que no período anterior`, token: 'warning' };

    return { texto: 'igual ao período anterior', token: 'info' };
});

const resumoDaSerie = computed(() => {
    if (!props.serie.length) return 'Sem dados no período.';

    const maior = props.serie.reduce((a, b) => (b.visitas > a.visitas ? b : a));

    return `Visitas por dia nos últimos ${props.dias} dias. `
        + `Total de ${props.kpis.totalPeriodo}. `
        + `O dia de maior movimento foi ${maior.rotulo}, com ${maior.visitas}.`;
});
</script>

<template>
    <Head title="Estatísticas" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Audiência</p></template>

        <PageHeader
            titulo="Estatísticas"
            descricao="Quantas vezes cada página do site foi aberta. Conta só visitante de fora — quem está logado no painel não entra na conta."
        />

        <EstadoVazio
            v-if="!temVisita"
            titulo="Nenhuma visita registrada ainda"
            descricao="A contagem começa na primeira vez que alguém de fora abrir o site. Se ele acabou de entrar no ar, volte aqui amanhã."
            icone="bar-chart-3"
        />

        <template v-else>

            <div class="mb-5 flex flex-wrap items-center gap-2" role="group" aria-label="Período">
                <button
                    v-for="janela in janelas"
                    :key="janela"
                    type="button"
                    class="min-h-[44px] rounded-md border px-4 text-sm font-semibold transition
                           focus:outline-none focus:ring-2 focus:ring-accent-ring"
                    :class="janela === dias
                        ? 'border-line-strong bg-accent text-fg-on-accent'
                        : 'border-line-input bg-surface text-fg-muted hover:text-fg'"
                    :aria-pressed="janela === dias"
                    @click="trocarJanela(janela)"
                >
                    {{ janela }} dias
                </button>
            </div>

            <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    rotulo="Visitas no período"
                    :valor="kpis.totalPeriodo"
                    :contexto="variacao.texto"
                    icone="eye"
                    :token="variacao.token"
                />
                <StatCard
                    rotulo="Média por dia"
                    :valor="kpis.mediaPorDia"
                    :contexto="kpis.mediaPorDia === 0 ? 'nenhuma ainda' : 'páginas abertas por dia'"
                    icone="activity"
                    token="accent"
                />
                <StatCard
                    rotulo="Página mais vista"
                    :valor="paginas.length ? paginas[0].visitas : 0"
                    :contexto="paginas.length ? paginas[0].titulo : 'nenhuma ainda'"
                    icone="file-text"
                    token="info"
                />
                <StatCard
                    rotulo="Desde o início"
                    :valor="kpis.totalDesdeOInicio"
                    :contexto="kpis.desde ? `contando desde ${kpis.desde}` : 'nenhuma ainda'"
                    icone="history"
                    token="accent"
                />
            </div>

            <section class="mb-8 rounded-lg border border-line bg-surface p-5">
                <h2 class="font-display text-h3 font-bold uppercase text-fg">Visitas por dia</h2>
                <p class="mt-1 text-sm text-fg-muted">
                    Cada barra é um dia. Dia sem barra é dia sem visita — e isso é informação, não falha.
                </p>

                <div
                    class="mt-5 flex h-40 gap-px"
                    role="img"
                    :aria-label="resumoDaSerie"
                >
                    <div
                        v-for="ponto in serie"
                        :key="ponto.dia"
                        class="flex h-full flex-1 flex-col justify-end rounded-t-sm bg-surface-sunken"
                        :title="`${ponto.rotulo}: ${ponto.visitas} visita(s)`"
                    >
                        <div
                            class="w-full rounded-t-sm bg-accent"
                            :style="{ height: `${Math.max(ponto.visitas > 0 ? 2 : 0, Math.round((ponto.visitas / pico) * 100))}%` }"
                        />
                    </div>
                </div>

                <div class="mt-2 flex justify-between font-label text-label uppercase text-fg-subtle">
                    <span>{{ serie[0]?.rotulo }}</span>
                    <span>{{ serie[serie.length - 1]?.rotulo }}</span>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">

                <section class="overflow-hidden rounded-lg border border-line bg-surface">
                    <h2 class="border-b border-line px-5 py-4 font-display text-h3 font-bold uppercase text-fg">
                        Páginas mais abertas
                    </h2>

                    <table v-if="paginas.length" class="w-full text-sm">
                        <thead class="border-b border-line">
                            <tr class="text-left font-label text-label uppercase text-fg-subtle">
                                <th class="px-5 py-3">Página</th>
                                <th class="px-5 py-3 text-right">Visitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="pagina in paginas" :key="pagina.caminho">
                                <td class="px-5 py-3">
                                    <p class="text-fg">{{ pagina.titulo }}</p>
                                    <p class="text-xs text-fg-subtle">{{ pagina.caminho }}</p>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-fg [font-variant-numeric:tabular-nums]">
                                    {{ pagina.visitas }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p v-else class="px-5 py-8 text-center text-sm text-fg-muted">
                        Nenhuma visita neste período.
                    </p>
                </section>

                <section class="overflow-hidden rounded-lg border border-line bg-surface">
                    <h2 class="border-b border-line px-5 py-4 font-display text-h3 font-bold uppercase text-fg">
                        Shows mais procurados
                    </h2>

                    <table v-if="shows.length" class="w-full text-sm">
                        <thead class="border-b border-line">
                            <tr class="text-left font-label text-label uppercase text-fg-subtle">
                                <th class="px-5 py-3">Show</th>
                                <th class="px-5 py-3 text-right">Visitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="show in shows" :key="show.caminho">
                                <td class="px-5 py-3 text-fg">{{ show.titulo }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-fg [font-variant-numeric:tabular-nums]">
                                    {{ show.visitas }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p v-else class="px-5 py-8 text-center text-sm text-fg-muted">
                        Nenhuma página de show foi aberta neste período.
                    </p>
                </section>
            </div>
        </template>

        <section class="mt-8 flex gap-4 rounded-lg border border-line bg-surface-sunken p-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-md bg-success-subtle text-success">
                <ShieldCheck class="h-5 w-5" aria-hidden="true" />
            </span>
            <div class="text-sm text-fg-muted">
                <p class="font-semibold text-fg">Esta contagem não rastreia ninguém.</p>
                <p class="mt-1">
                    O site não guarda IP, cookie de rastreio nem identificação de quem visita — só soma
                    quantas vezes cada endereço foi aberto, por dia. É por isso que aqui não há
                    “visitantes únicos”, “de onde vieram” nem “quanto tempo ficaram”: esses números só
                    existem para quem guarda algo que identifique a pessoa, e a
                    <strong class="text-fg">página de privacidade do site promete que não guardamos</strong>.
                </p>
                <p class="mt-1">
                    Pré-visualização de link do WhatsApp e do Instagram também não entra na conta — senão
                    todo dia de divulgação pareceria um sucesso de público.
                </p>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
