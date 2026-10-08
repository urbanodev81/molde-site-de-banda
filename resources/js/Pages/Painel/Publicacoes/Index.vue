<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { ref } from 'vue';
import { ExternalLink, Plus } from 'lucide-vue-next';
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
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    publicacoes: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'publicacoes',
    ids: () => props.publicacoes.map((p) => p.id),
    podeGerenciar: () => true,
    arquivados: () => props.arquivados,
    nome: ['publicação', 'publicações', 'a'],
});

const vazio = () => ({
    tipo: 'clipping', titulo: '', veiculo: '', saiu_em: '', link: '', resumo: '', imagem: null, publicada: false, destaque: false,
});

const form = useForm(vazio());
const editando = ref(null);
const edicao = useForm(vazio());

const campoDeImagem = ref(0);

function criar() {
    form.post(route('painel.publicacoes.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            campoDeImagem.value++;
        },
    });
}

function abrirEdicao(item) {
    editando.value = item.id;
    Object.assign(edicao, {
        tipo: item.tipo, titulo: item.titulo, veiculo: item.veiculo ?? '', saiu_em: item.saiu_em ?? '',
        link: item.link, resumo: item.resumo ?? '', imagem: null, publicada: item.publicada, destaque: item.destaque,
    });
    edicao.clearErrors();
}

function salvar(item) {
    edicao.transform((d) => ({ ...d, _method: 'put' })).post(route('painel.publicacoes.update', item.id), {
        preserveScroll: true, forceFormData: true, onSuccess: () => (editando.value = null),
    });
}

const classeCampo = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head title="Na mídia" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Na mídia"
            descricao="O que saiu sobre a banda: matéria, nota, entrevista. Cada uma leva ao link de quem publicou e aparece na página de imprensa."
        />

        <form v-if="!arquivados" class="mb-6 rounded-lg border border-line bg-surface p-5" @submit.prevent="criar">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Nova publicação</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel for="pub-titulo" value="Título da matéria" obrigatorio />
                    <TextInput id="pub-titulo" v-model="form.titulo" />
                    <InputError :message="form.errors.titulo" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel for="pub-link" value="Link" obrigatorio />
                    <TextInput id="pub-link" v-model="form.link" type="url" inputmode="url" placeholder="https://" />
                    <InputError :message="form.errors.link" />
                </div>
                <div>
                    <InputLabel for="pub-tipo" value="Tipo" obrigatorio />
                    <select id="pub-tipo" v-model="form.tipo" :class="classeCampo">
                        <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                    </select>
                    <InputError :message="form.errors.tipo" />
                </div>
                <div>
                    <InputLabel for="pub-veiculo" value="Quem publicou" />
                    <TextInput id="pub-veiculo" v-model="form.veiculo" placeholder="jornal, portal, rádio, canal" />
                    <InputError :message="form.errors.veiculo" />
                </div>
                <div>
                    <InputLabel for="pub-data" value="Quando saiu" />
                    <TextInput id="pub-data" v-model="form.saiu_em" type="date" />
                    <InputError :message="form.errors.saiu_em" />
                </div>
                <div>
                    <InputLabel for="pub-imagem" value="Imagem" />
                    <input id="pub-imagem" :key="campoDeImagem" type="file" accept="image/*" class="block w-full text-sm text-fg-muted" @input="form.imagem = $event.target.files[0] ?? null" />
                    <InputError :message="form.errors.imagem" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel for="pub-resumo" value="Resumo" />
                    <textarea id="pub-resumo" v-model="form.resumo" rows="3" maxlength="600" :class="classeCampo" />
                    <p class="mt-1 text-xs text-fg-subtle">Uma ou duas frases, até 600 caracteres. O texto inteiro fica no link.</p>
                    <InputError :message="form.errors.resumo" />
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing"><Plus class="h-4 w-4" aria-hidden="true" />Adicionar</PrimaryButton>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.publicada" />Publicar no site</label>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.destaque" />Destaque (vem primeiro)</label>
            </div>
        </form>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Cadastradas" />

        <div v-if="publicacoes.length" class="grid gap-4 lg:grid-cols-2">
            <article v-for="item in publicacoes" :key="item.id" class="min-w-0 rounded-lg border border-line bg-surface p-5">
                <CaixaDeLote :lote="lote" :id="item.id" :rotulo="`publicação ${item.titulo}`" class="-ml-2 -mt-2" />
                <div v-if="editando !== item.id">
                    <img v-if="item.imagem" :src="item.imagem" alt="" class="mb-3 h-32 w-full rounded-md object-cover" loading="lazy" />
                    <p class="font-label text-label uppercase text-fg-subtle">{{ item.tipoRotulo }}<template v-if="item.origem"> · {{ item.origem }}</template></p>
                    <p class="mt-1 break-words font-semibold text-fg">{{ item.titulo }}</p>
                    <p v-if="item.resumo" class="mt-2 break-words text-sm text-fg-muted">{{ item.resumo }}</p>
                    <a :href="item.link" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex min-h-[44px] items-center gap-1 break-all text-sm text-accent underline">
                        <ExternalLink class="h-4 w-4 shrink-0" aria-hidden="true" />Abrir a matéria
                    </a>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <Chip :rotulo="item.publicada ? 'No site' : 'Fora do site'" :token="item.publicada ? 'success' : 'fg-subtle'" :icone="item.publicada ? 'eye' : 'eye-off'" />
                        <Chip v-if="item.destaque" rotulo="Destaque" token="accent" icone="star" />
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <template v-if="!arquivados">
                            <SecondaryButton type="button" @click="abrirEdicao(item)">Editar</SecondaryButton>
                            <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="item.titulo" @confirmar="router.delete(route('painel.publicacoes.destroy', item.id), { preserveScroll: true })" />
                        </template>
                        <BotaoRestaurar v-else :lote="lote" :id="item.id" />
                    </div>
                </div>

                <form v-else class="space-y-3" @submit.prevent="salvar(item)">
                    <TextInput v-model="edicao.titulo" aria-label="Título da matéria" />
                    <InputError :message="edicao.errors.titulo" />
                    <TextInput v-model="edicao.link" type="url" inputmode="url" aria-label="Link" />
                    <InputError :message="edicao.errors.link" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select v-model="edicao.tipo" :class="classeCampo" aria-label="Tipo">
                            <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                        </select>
                        <TextInput v-model="edicao.saiu_em" type="date" aria-label="Quando saiu" />
                    </div>
                    <InputError :message="edicao.errors.saiu_em" />
                    <TextInput v-model="edicao.veiculo" aria-label="Quem publicou" />
                    <textarea v-model="edicao.resumo" rows="3" maxlength="600" :class="classeCampo" aria-label="Resumo" />
                    <InputError :message="edicao.errors.resumo" />
                    <div>
                        <InputLabel :for="`pub-imagem-${item.id}`" :value="item.imagem ? 'Trocar a imagem' : 'Imagem'" />
                        <input :id="`pub-imagem-${item.id}`" type="file" accept="image/*" class="block w-full text-sm text-fg-muted" @input="edicao.imagem = $event.target.files[0] ?? null" />
                        <InputError :message="edicao.errors.imagem" />
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <PrimaryButton :disabled="edicao.processing">Salvar</PrimaryButton>
                        <SecondaryButton type="button" @click="editando = null">Cancelar</SecondaryButton>
                        <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.publicada" />Publicar</label>
                        <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.destaque" />Destaque</label>
                    </div>
                </form>
            </article>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="newspaper" titulo="Nenhuma publicação" descricao="Saiu uma nota no jornal do bairro ou uma entrevista na rádio? Cole o link aqui: é o que o contratante procura antes de ligar." />
    </AuthenticatedLayout>
</template>
