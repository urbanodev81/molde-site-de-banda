<script setup>

import { Head, Link, router, useForm } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { computed, ref } from 'vue';
import { Plus, EyeOff, Info } from 'lucide-vue-next';
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

const props = defineProps({
    tipos: { type: Array, default: () => [] },
    podeGerenciar: { type: Boolean, default: false },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
    grupo: { type: String, default: 'galeria' },

    outroGrupo: { type: Boolean, default: false },
});

const deEspaco = computed(() => props.grupo === 'espaco');

const rotas = {
    galeria: {
        index: () => route('painel.tipos-galeria.index'),
        store: () => route('painel.tipos-galeria.store'),
        update: (id) => route('painel.tipos-galeria.update', id),
        destroy: (id) => route('painel.tipos-galeria.destroy', id),
    },
    espaco: {
        index: () => route('painel.tipos-espaco.index'),
        store: () => route('painel.tipos-espaco.store'),
        update: (id) => route('painel.tipos-espaco.update', id),
        destroy: (id) => route('painel.tipos-espaco.destroy', id),
    },
};
const rota = computed(() => rotas[props.grupo] ?? rotas.galeria);

const texto = computed(() => (deEspaco.value
    ? {
        titulo: 'Tipos de espaço',
        descricao: 'O que cada local é: bar, casa de show, teatro, praça. Escolhido no cadastro do local.',
        exemplo: 'Cervejaria',
        exemploDescricao: 'Uma anotação para vocês; não vai ao site.',
        ajudaDescricao: 'Fica só no painel.',
        publicado: 'Mostrar no site',
        vazio: 'Sem tipo, o local aparece só com o nome e o endereço.',
    }
    : {
        titulo: 'Tipos da galeria',
        descricao: 'As abas de /galeria. Cada uma vira uma página com endereço próprio — show, ensaio, gravação, o que a banda precisar.',
        exemplo: 'Festival',
        exemploDescricao: 'Canja, festival, palco de outra banda.',
        ajudaDescricao: 'Aparece embaixo do título da aba, no site.',
        publicado: 'Publicado',
        vazio: 'Sem tipo, a galeria mostra só o que tem show — ensaio e gravação ficam invisíveis no site.',
    }));

const lote = useListaEmLote({
    slug: props.grupo === 'espaco' ? 'tipos-espaco' : 'tipos-galeria',
    ids: () => props.tipos.map((t) => t.id),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['tipo', 'tipos', 'o'],
});

const form = useForm({ nome: '', descricao: '', ordem: 0, publicado: true });

const editando = ref(null);
const edicao = useForm({ nome: '', descricao: '', ordem: 0, publicado: true });

function abrirEdicao(tipo) {
    editando.value = tipo.id;
    Object.assign(edicao, {
        nome: tipo.nome,
        descricao: tipo.descricao ?? '',
        ordem: tipo.ordem,
        publicado: tipo.publicado,
    });
}
</script>

<template>
    <Head :title="texto.titulo" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">{{ deEspaco ? 'Agenda' : 'O site' }}</p></template>

        <PageHeader :titulo="texto.titulo" :descricao="texto.descricao" />

        <nav v-if="outroGrupo" class="mb-6 flex flex-wrap gap-2" aria-label="Grupos de tipo">
            <Link
                v-for="aba in [{ chave: 'galeria', rotulo: 'Galeria' }, { chave: 'espaco', rotulo: 'Espaço' }]"
                :key="aba.chave"
                :href="rotas[aba.chave].index()"
                class="inline-flex min-h-[44px] items-center rounded-full border px-4 font-label text-label uppercase"
                :class="aba.chave === grupo ? 'border-line-strong bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:text-fg'"
                :aria-current="aba.chave === grupo ? 'page' : undefined"
            >
                {{ aba.rotulo }}
            </Link>
        </nav>

        <div v-if="deEspaco" class="mb-6 flex gap-3 rounded-lg border border-line bg-surface-sunken p-4 text-sm text-fg-muted">
            <Info class="mt-0.5 h-4 w-4 shrink-0 text-accent" aria-hidden="true" />
            <p>
                O tipo aparece no site ao lado do endereço do show ("Bar · Rua tal"). Tipo com
                <strong>Mostrar no site</strong> desmarcado continua organizando os locais aqui no
                painel, sem aparecer para quem visita.
            </p>
        </div>

        <div v-if="!deEspaco" class="mb-6 flex gap-3 rounded-lg border border-line bg-surface-sunken p-4 text-sm text-fg-muted">
            <Info class="mt-0.5 h-4 w-4 shrink-0 text-accent" aria-hidden="true" />
            <p>
                Tipo <strong>fora do ar</strong> some do site inteiro, com as fotos e vídeos que só
                estavam nele — foto de ensaio, gravação ou bastidor aparece na galeria porque o tipo
                dela está publicado. Foto de show não depende disto: quem manda nela continua sendo o
                show.
            </p>
        </div>

        <form
            v-if="podeGerenciar && !arquivados"
            class="mb-6 rounded-lg border border-line bg-surface p-5"
            @submit.prevent="form.post(rota.store(), { preserveScroll: true, onSuccess: () => form.reset() })"
        >
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Novo tipo</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><InputLabel value="Nome" obrigatorio /><TextInput v-model="form.nome" :placeholder="texto.exemplo" /><InputError :message="form.errors.nome" /></div>
                <div class="lg:col-span-2">
                    <InputLabel value="Descrição" />
                    <TextInput v-model="form.descricao" :placeholder="texto.exemploDescricao" />
                    <p class="mt-1 text-xs text-fg-subtle">{{ texto.ajudaDescricao }}</p>
                    <InputError :message="form.errors.descricao" />
                </div>
                <div><InputLabel value="Ordem" /><TextInput v-model="form.ordem" type="number" min="0" /></div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing"><Plus class="h-4 w-4" aria-hidden="true" />Criar</PrimaryButton>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.publicado" />{{ texto.publicado }}</label>
            </div>
        </form>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Em uso" />

        <div v-if="tipos.length" class="overflow-hidden rounded-lg border border-line bg-surface">
            <ul class="divide-y divide-line">
                <li v-for="tipo in tipos" :key="tipo.id" class="px-4 py-3 sm:px-5">
                    <div v-if="editando !== tipo.id" class="flex flex-wrap items-center gap-3">
                        <CaixaDeLote :lote="lote" :id="tipo.id" :rotulo="tipo.nome" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-fg">
                                {{ tipo.nome }}
                                <span v-if="!deEspaco" class="font-mono text-xs font-normal text-fg-subtle">/galeria/{{ tipo.slug }}</span>
                            </p>
                            <p class="truncate text-sm text-fg-subtle">

                                <template v-if="deEspaco">{{ tipo.locais }} {{ tipo.locais === 1 ? 'local' : 'locais' }}</template>
                                <template v-else-if="tipo.ehDeShows">Herda as fotos e vídeos de todos os shows</template>
                                <template v-else>{{ tipo.fotos }} foto(s) · {{ tipo.videos }} vídeo(s)</template>
                                <template v-if="tipo.descricao"> · {{ tipo.descricao }}</template>
                            </p>
                        </div>
                        <EyeOff v-if="!tipo.publicado" class="h-4 w-4 text-fg-subtle" :aria-label="deEspaco ? 'Só no painel' : 'Fora do ar'" />
                        <div v-if="podeGerenciar" class="flex gap-2">
                            <template v-if="!arquivados">
                                <SecondaryButton type="button" @click="abrirEdicao(tipo)">Editar</SecondaryButton>
                                <TwoStepConfirm
                                    rotulo="Arquivar" verbo="Arquivar"
                                    :nome="tipo.nome"
                                    @confirmar="router.delete(rota.destroy(tipo.id), { preserveScroll: true })"
                                />
                            </template>
                            <BotaoRestaurar v-else :lote="lote" :id="tipo.id" />
                        </div>
                    </div>

                    <form
                        v-else
                        class="grid gap-3 sm:grid-cols-6"
                        @submit.prevent="edicao.put(rota.update(tipo.id), { preserveScroll: true, onSuccess: () => (editando = null) })"
                    >
                        <TextInput v-model="edicao.nome" class="sm:col-span-2" aria-label="Nome" />
                        <TextInput v-model="edicao.descricao" class="sm:col-span-3" aria-label="Descrição" />
                        <TextInput v-model="edicao.ordem" class="sm:col-span-1" type="number" min="0" aria-label="Ordem" />
                        <InputError class="sm:col-span-6" :message="edicao.errors.nome" />

                        <p v-if="!deEspaco" class="text-xs text-fg-subtle sm:col-span-6">
                            O endereço <span class="font-mono">/galeria/{{ tipo.slug }}</span> não muda ao renomear.
                        </p>
                        <div class="flex flex-wrap items-center gap-4 sm:col-span-4">
                            <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.publicado" />{{ texto.publicado }}</label>
                        </div>
                        <div class="flex gap-2 sm:col-span-2">
                            <PrimaryButton :disabled="edicao.processing">Salvar</PrimaryButton>
                            <SecondaryButton type="button" @click="editando = null">Cancelar</SecondaryButton>
                        </div>
                    </form>
                </li>
            </ul>
        </div>

        <EstadoVazio
            v-else-if="!arquivados"
            :icone="deEspaco ? 'map-pin' : 'images'"
            titulo="Nenhum tipo cadastrado"
            :descricao="texto.vazio"
        />
    </AuthenticatedLayout>
</template>
