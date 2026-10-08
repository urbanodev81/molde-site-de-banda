<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { EyeOff, Map, Pencil, Plus, X } from 'lucide-vue-next';
import MateriaisLigados from '@/Components/Painel/MateriaisLigados.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Chip from '@/Components/Chip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import MidiaDaNoite from '@/Components/Painel/MidiaDaNoite.vue';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    show: { type: Object, required: true },
    cache: { type: [String, Number], default: null },
    repertorio: { type: Array, default: () => [] },
    motivosParaNaoAparecer: { type: Array, default: () => [] },
    midia: { type: Object, required: true },
    podeGerenciar: { type: Boolean, default: false },
    materiais: { type: Array, default: () => [] },
});

const setlist = ref([...props.show.setlist]);
const escolhida = ref('');

function adicionar() {
    const musica = props.repertorio.find((m) => String(m.id) === String(escolhida.value));

    if (musica && !setlist.value.some((s) => s.id === musica.id)) {
        setlist.value.push({ id: musica.id, linha: musica.linha, bloco: null });
    }

    escolhida.value = '';
}

function salvarSetlist() {
    router.put(route('painel.shows.setlist', props.show.uuid), {
        musicas: setlist.value.map((m) => ({ id: m.id, bloco: m.bloco })),
    });
}
</script>

<template>
    <Head :title="show.nome" />

    <AuthenticatedLayout>
        <template #cabecalho>
            <p class="font-label text-label uppercase text-fg-subtle">Agenda</p>
        </template>

        <PageHeader
            :titulo="show.nome"
            :voltar-para="route('painel.shows.index')"
            voltar-rotulo="Agenda"
        >
            <template #acoes>
                <Link v-if="podeGerenciar" :href="route('painel.shows.edit', show.uuid)">
                    <SecondaryButton type="button">
                        <Pencil class="h-4 w-4" aria-hidden="true" />
                        Editar
                    </SecondaryButton>
                </Link>
                <TwoStepConfirm
                    v-if="podeGerenciar"
                    :nome="show.nome"
                    @confirmar="router.delete(route('painel.shows.destroy', show.uuid))"
                />
            </template>
        </PageHeader>

        <div
            v-if="motivosParaNaoAparecer.length"
            class="mb-6 flex items-start gap-3 rounded-lg border border-warning bg-warning-subtle p-4"
        >
            <EyeOff class="mt-0.5 h-5 w-5 shrink-0 text-warning" aria-hidden="true" />
            <div>
                <p class="font-semibold text-warning">Este show não aparece no site</p>
                <ul class="mt-1 list-inside list-disc text-sm text-warning">
                    <li v-for="motivo in motivosParaNaoAparecer" :key="motivo">{{ motivo }}</li>
                </ul>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border border-line bg-surface p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="font-display text-4xl font-black uppercase leading-none text-fg [font-variant-numeric:tabular-nums]">
                                {{ show.dia }} {{ show.mes }} {{ show.ano }}
                            </p>
                            <p class="mt-2 font-label text-label uppercase text-fg-muted">
                                {{ show.hora }}<template v-if="show.endereco"> · {{ show.endereco }}</template>
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Chip :rotulo="show.statusRotulo" :token="show.statusToken" :icone="show.statusIcone" />
                            <Chip :rotulo="show.tipoRotulo" token="fg-subtle" />
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-4 border-t border-line pt-5 sm:grid-cols-2">
                        <div v-if="show.entrada">
                            <dt class="font-label text-label uppercase text-fg-subtle">Entrada</dt>
                            <dd class="text-sm text-fg">{{ show.entrada }}</dd>
                        </div>
                        <div v-if="cache !== null">
                            <dt class="font-label text-label uppercase text-fg-subtle">Cachê</dt>
                            <dd class="text-sm text-fg [font-variant-numeric:tabular-nums]">R$ {{ cache }}</dd>
                        </div>
                        <div v-if="show.observacoesPublicas" class="sm:col-span-2">
                            <dt class="font-label text-label uppercase text-fg-subtle">Observação pública</dt>
                            <dd class="text-sm text-fg">{{ show.observacoesPublicas }}</dd>
                        </div>
                        <div v-if="show.observacoesInternas" class="sm:col-span-2">
                            <dt class="font-label text-label uppercase text-fg-subtle">Observação interna</dt>
                            <dd class="whitespace-pre-line text-sm text-fg">{{ show.observacoesInternas }}</dd>
                        </div>
                    </dl>

                    <a
                        v-if="show.mapa"
                        :href="show.mapa"
                        target="_blank"
                        rel="noopener"
                        class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-accent hover:underline"
                    >
                        <Map class="h-4 w-4" aria-hidden="true" />
                        Como chegar
                    </a>
                </section>

                <section class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-1 font-display text-xl font-bold uppercase text-fg">Setlist</h2>
                    <p class="mb-4 text-sm text-fg-muted">
                        A ordem é a da lista. Serve para não repetir a mesma abertura três semanas seguidas no mesmo local.
                    </p>

                    <ol v-if="setlist.length" class="mb-4 space-y-2">
                        <li
                            v-for="(musica, indice) in setlist"
                            :key="musica.id"
                            class="flex items-center gap-3 rounded-md border border-line px-3 py-2"
                        >
                            <span class="w-6 text-center font-display text-lg font-bold text-fg-subtle [font-variant-numeric:tabular-nums]">
                                {{ indice + 1 }}
                            </span>
                            <span class="min-w-0 flex-1 truncate text-sm text-fg">{{ musica.linha }}</span>
                            <input
                                v-model="musica.bloco"
                                class="w-24 rounded-md border-line-input bg-surface text-xs text-fg"
                                placeholder="1º set"
                                aria-label="Bloco"
                            />
                            <button
                                v-if="podeGerenciar"
                                type="button"
                                class="grid h-11 w-11 place-items-center rounded-md text-fg-subtle transition hover:text-danger"
                                :aria-label="`Tirar ${musica.linha} do setlist`"
                                @click="setlist.splice(indice, 1)"
                            >
                                <X class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </li>
                    </ol>

                    <p v-else class="mb-4 text-sm text-fg-subtle">Nenhuma música escolhida ainda.</p>

                    <div v-if="podeGerenciar" class="flex flex-wrap items-center gap-2">
                        <SelectComBusca v-model="escolhida" :opcoes="repertorio" campo-valor="id" campo-rotulo="linha" rotulo="Escolher música do repertório" opcao-vazia="Escolha uma música do repertório…" class="min-w-0 flex-1" />
                        <SecondaryButton type="button" :disabled="!escolhida" @click="adicionar">
                            <Plus class="h-4 w-4" aria-hidden="true" />
                            Adicionar
                        </SecondaryButton>
                        <PrimaryButton type="button" @click="salvarSetlist">Salvar setlist</PrimaryButton>
                    </div>
                </section>

                <MidiaDaNoite :show="show" :midia="midia" />

                <MateriaisLigados :materiais="materiais" titulo="Materiais desta noite" />
            </div>

            <div class="space-y-6">
                <section v-if="show.cartaz" class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-3 font-display text-xl font-bold uppercase text-fg">Cartaz</h2>
                    <img :src="show.cartaz" :alt="`Cartaz do show em ${show.nome}`" class="w-full rounded-md border border-line" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
