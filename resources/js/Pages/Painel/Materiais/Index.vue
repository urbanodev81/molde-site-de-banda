<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import * as icones from 'lucide-vue-next';
import { reactive } from 'vue';
import { Upload, Download, Globe, Lock, ShieldCheck, X } from 'lucide-vue-next';
import SelectComBusca from '@/Components/SelectComBusca.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    materiais: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },

    alvos: { type: Array, default: () => [] },
    podeGerenciar: { type: Boolean, default: false },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'materiais',
    ids: () => props.materiais.map((m) => m.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['material', 'materiais', 'o'],
    liga: ['Tornar público', 'Deixar só no painel'],
    envia: true,
});

const form = useForm({ arquivo: null, titulo: '', descricao: '', tipo: 'foto_alta', publico: false, ordem: 0 });

const escolha = reactive({});

function ligar(material, alvo) {
    if (!alvo) return;

    router.post(route('painel.materiais.vinculos.store', material.uuid), { alvo }, {
        preserveScroll: true,
        onFinish: () => (escolha[material.uuid] = ''),
    });
}

function desligar(material, vinculo) {
    router.delete(route('painel.materiais.vinculos.destroy', [material.uuid, vinculo.id]), { preserveScroll: true });
}

const iconeDe = (nome) => {
    const chave = (nome ?? 'file').split('-').map((p) => p[0].toUpperCase() + p.slice(1)).join('');

    return icones[chave] ?? icones.File;
};
</script>

<template>
    <Head title="Materiais" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Materiais e press kit"
            descricao="O que empresa grande pede e ninguém acha na hora: logo em vetor, foto em alta, rider técnico, contrato modelo. Cada material pode ser ligado a um show, um local, uma integrante ou um pedido; ligado a pedido, o arquivo fica privado."
        />

        <form v-if="podeGerenciar && !arquivados" class="mb-6 rounded-lg border border-line bg-surface p-5" @submit.prevent="form.post(route('painel.materiais.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() })">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Adicionar material</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <InputLabel value="Arquivo" obrigatorio />
                    <input type="file" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent" @change="form.arquivo = $event.target.files[0]" />
                    <InputError :message="form.errors.arquivo" />
                </div>
                <div><InputLabel value="Título" obrigatorio /><TextInput v-model="form.titulo" /><InputError :message="form.errors.titulo" /></div>
                <div>
                    <InputLabel value="Tipo" obrigatorio />
                    <select v-model="form.tipo" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring">
                        <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <InputLabel value="Descrição" />
                    <TextInput v-model="form.descricao" />
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing"><Upload class="h-4 w-4" aria-hidden="true" />Enviar</PrimaryButton>
                <label class="flex items-center gap-2 text-sm text-fg">
                    <Checkbox v-model:checked="form.publico" />
                    Deixar público na página de imprensa
                </label>
            </div>
        </form>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="No painel" explicacao="Vai o link de cada material público na página de imprensa; os que estão só no painel ficam de fora." />

        <div v-if="materiais.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="material in materiais" :key="material.uuid" class="min-w-0 flex flex-col rounded-lg border border-line bg-surface p-5">
                <div class="flex items-start gap-3">
                    <CaixaDeLote :lote="lote" :id="material.uuid" :rotulo="material.titulo" />
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-md bg-accent-subtle text-accent">
                        <component :is="iconeDe(material.tipoIcone)" class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-semibold text-fg">{{ material.titulo }}</h2>
                        <p class="truncate text-sm text-fg-muted">{{ material.tipoRotulo }}<template v-if="material.tamanho"> · {{ material.tamanho }}</template></p>
                    </div>
                </div>

                <p v-if="material.descricao" class="mt-3 text-sm text-fg-muted">{{ material.descricao }}</p>

                <p class="mt-3 inline-flex items-center gap-1.5 font-label text-label uppercase" :class="material.publico ? 'text-success' : 'text-fg-subtle'">
                    <component :is="material.privado ? ShieldCheck : material.publico ? Globe : Lock" class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ material.privado ? 'Privado: só abre com login' : material.publico ? 'Público na página de imprensa' : 'Só no painel' }}
                </p>

                <ul v-if="material.vinculos.length" class="mt-3 flex flex-wrap gap-2" :aria-label="`Ligações de ${material.titulo}`">
                    <li v-for="vinculo in material.vinculos" :key="vinculo.id" class="inline-flex max-w-full items-center gap-1 rounded-full bg-surface-sunken py-1 pl-3 pr-1 text-sm text-fg">
                        <span class="min-w-0 break-words"><span class="font-label text-label uppercase text-fg-subtle">{{ vinculo.tipo }}</span> {{ vinculo.rotulo }}</span>
                        <button
                            v-if="podeGerenciar && !arquivados" type="button"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-fg-subtle hover:text-fg focus:outline-none focus:ring-2 focus:ring-accent-ring"
                            :aria-label="`Desligar de ${vinculo.rotulo}`" @click="desligar(material, vinculo)"
                        >
                            <X class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </li>
                </ul>

                <div v-if="podeGerenciar && !arquivados && alvos.length" class="mt-3">
                    <SelectComBusca
                        v-model="escolha[material.uuid]" :opcoes="alvos" :rotulo="`Ligar ${material.titulo} a`"
                        opcao-vazia="Ligar a…" placeholder="Show, local, integrante ou pedido"
                        @update:model-value="ligar(material, $event)"
                    />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-4">
                    <a :href="material.url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm font-semibold text-accent hover:underline">
                        <Download class="h-4 w-4" aria-hidden="true" />Baixar
                    </a>
                    <TwoStepConfirm v-if="podeGerenciar && !arquivados" rotulo="Arquivar" verbo="Arquivar" :nome="material.titulo" @confirmar="router.delete(route('painel.materiais.destroy', material.uuid), { preserveScroll: true })" />
                    <BotaoRestaurar v-else-if="podeGerenciar" :lote="lote" :id="material.uuid" />
                </div>
            </article>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="folder-down" titulo="Nenhum material" descricao="Comece pelo logo em vetor — é o que sempre falta na hora do cartaz." />
    </AuthenticatedLayout>
</template>
