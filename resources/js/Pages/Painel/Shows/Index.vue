<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { ref, watch } from 'vue';
import { Plus, EyeOff, Search } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Chip from '@/Components/Chip.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    shows: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    status: { type: Array, default: () => [] },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
    podeGerenciar: { type: Boolean, default: false },
});

const lote = useListaEmLote({
    slug: 'shows',
    ids: () => props.shows.data.map((s) => s.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['show', 'shows', 'o'],
    envia: true,
});

const busca = ref(props.filtros.busca ?? '');
let relogio = null;

watch(busca, (valor) => {
    clearTimeout(relogio);
    relogio = setTimeout(() => {
        router.get(route('painel.shows.index'), { ...props.filtros, busca: valor }, {
            preserveState: true,
            replace: true,
        });
    }, 350);
});

function filtrar(campo, valor) {
    router.get(route('painel.shows.index'), { ...props.filtros, [campo]: valor }, {
        preserveState: true,
        replace: true,
    });
}

const abas = [
    { valor: 'futuros', rotulo: 'Próximos' },
    { valor: 'passados', rotulo: 'Já aconteceram' },
    { valor: 'todos', rotulo: 'Todos' },
];
</script>

<template>
    <Head title="Shows" />

    <AuthenticatedLayout>
        <template #cabecalho>
            <p class="font-label text-label uppercase text-fg-subtle">Agenda</p>
        </template>

        <PageHeader
            titulo="Agenda de shows"
            descricao="O que está no site é o que está aqui: confirmado, público e ainda por acontecer."
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

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Na agenda" explicacao="Vai o link da página de cada show que está no site; os que estão fora do site ficam de fora." />

        <div v-if="!arquivados" class="mb-5 flex flex-wrap items-center gap-3">
            <div class="flex rounded-md border border-line p-1">
                <button
                    v-for="aba in abas"
                    :key="aba.valor"
                    type="button"
                    class="min-h-[44px] rounded px-4 text-sm font-semibold transition"
                    :class="(filtros.quando ?? 'futuros') === aba.valor
                        ? 'bg-accent-subtle text-accent'
                        : 'text-fg-muted hover:text-fg'"
                    :aria-pressed="(filtros.quando ?? 'futuros') === aba.valor"
                    @click="filtrar('quando', aba.valor)"
                >
                    {{ aba.rotulo }}
                </button>
            </div>

            <label class="relative flex-1 sm:max-w-xs">
                <span class="sr-only">Buscar por local ou título</span>
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle" aria-hidden="true" />
                <TextInput v-model="busca" class="pl-9" placeholder="Buscar local ou título" />
            </label>

            <select
                class="min-h-[44px] rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                :value="filtros.status ?? ''"
                aria-label="Filtrar por situação"
                @change="filtrar('status', $event.target.value)"
            >
                <option value="">Qualquer situação</option>
                <option v-for="opcao in status" :key="opcao.valor" :value="opcao.valor">{{ opcao.rotulo }}</option>
            </select>
        </div>

        <div v-if="shows.data.length" class="overflow-hidden rounded-lg border border-line bg-surface">
            <ul class="divide-y divide-line">
                <li v-for="show in shows.data" :key="show.uuid" class="flex items-center gap-1 pl-2">
                    <CaixaDeLote :lote="lote" :id="show.uuid" :rotulo="`${show.nome}, ${show.dia} ${show.mes} ${show.ano}`" />

                    <component
                        :is="arquivados ? 'div' : Link"
                        :href="arquivados ? undefined : route('painel.shows.show', show.uuid)"
                        class="flex min-w-0 flex-1 items-center gap-4 py-4 pl-2 pr-4 transition sm:pr-5"
                        :class="arquivados ? '' : 'hover:bg-surface-sunken'"
                    >

                        <div class="w-16 shrink-0 text-center">
                            <p class="font-display text-3xl font-black leading-none text-fg [font-variant-numeric:tabular-nums]">
                                {{ show.dia }}
                            </p>
                            <p class="font-label text-label uppercase text-accent">{{ show.mes }}</p>
                            <p class="font-label text-[0.65rem] uppercase text-fg-subtle">{{ show.ano }}</p>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-display text-xl font-bold uppercase text-fg">{{ show.nome }}</p>
                            <p class="truncate text-sm text-fg-muted">
                                {{ show.hora }}<template v-if="show.endereco"> · {{ show.endereco }}</template>
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <Chip :rotulo="show.statusRotulo" :token="show.statusToken" :icone="show.statusIcone" />
                                <Chip v-if="show.tipo === 'particular'" rotulo="Particular" token="fg-subtle" icone="lock" />
                                <span v-if="!show.noSite" class="inline-flex items-center gap-1 text-xs text-fg-subtle">
                                    <EyeOff class="h-3.5 w-3.5" aria-hidden="true" />
                                    fora do site
                                </span>
                            </div>
                        </div>
                    </component>
                    <BotaoRestaurar v-if="arquivados && podeGerenciar" :lote="lote" :id="show.uuid" class="mr-4 shrink-0" />
                </li>
            </ul>
        </div>

        <EstadoVazio
            v-else-if="!arquivados"
            icone="calendar-off"
            titulo="Nenhum show por aqui"
            :descricao="filtros.busca
                ? 'Nada encontrado com esse termo. Tente o nome do local.'
                : 'Cadastre a próxima data — é o que mais gente procura no site.'"
        >
            <Link :href="route('painel.shows.create')">
                <PrimaryButton type="button">Cadastrar show</PrimaryButton>
            </Link>
        </EstadoVazio>

        <nav v-if="shows.links.length > 3" class="mt-6 flex flex-wrap gap-1" aria-label="Paginação">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in shows.links"
                :key="link.label"
                :href="link.url"
                class="min-h-[44px] rounded-md px-3 py-2 text-sm"
                :class="link.active
                    ? 'bg-accent text-fg-on-accent'
                    : link.url ? 'text-fg-muted hover:bg-surface-sunken' : 'text-fg-subtle opacity-50'"
                v-html="link.label"
            />
        </nav>
    </AuthenticatedLayout>
</template>
