<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { ref } from 'vue';
import { Plus, EyeOff } from 'lucide-vue-next';
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
    perguntas: { type: Array, default: () => [] },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'perguntas',
    ids: () => props.perguntas.map((p) => p.id),
    podeGerenciar: () => true,
    arquivados: () => props.arquivados,
    nome: ['dúvida', 'dúvidas', 'a'],
});

const form = useForm({ pergunta: '', resposta: '', ordem: 0, publicada: true });
const editando = ref(null);
const edicao = useForm({ pergunta: '', resposta: '', ordem: 0, publicada: true });

function abrirEdicao(item) {
    editando.value = item.id;
    Object.assign(edicao, { pergunta: item.pergunta, resposta: item.resposta, ordem: item.ordem, publicada: item.publicada });
}
</script>

<template>
    <Head title="Dúvidas frequentes" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Dúvidas frequentes"
            descricao="Não é enchimento: é o formato que o Google transforma em resultado rico e o que motor generativo mais cita."
        />

        <form v-if="!arquivados" class="mb-6 rounded-lg border border-line bg-surface p-5" @submit.prevent="form.post(route('painel.perguntas.store'), { preserveScroll: true, onSuccess: () => form.reset() })">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Nova dúvida</h2>
            <div class="space-y-4">
                <div><InputLabel value="Pergunta" obrigatorio /><TextInput v-model="form.pergunta" /><InputError :message="form.errors.pergunta" /></div>
                <div>
                    <InputLabel value="Resposta" obrigatorio />
                    <textarea v-model="form.resposta" rows="3" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" />
                    <InputError :message="form.errors.resposta" />
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing"><Plus class="h-4 w-4" aria-hidden="true" />Adicionar</PrimaryButton>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.publicada" />Publicada</label>
            </div>
        </form>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Cadastradas" />

        <div v-if="perguntas.length" class="space-y-3">
            <article v-for="item in perguntas" :key="item.id" class="rounded-lg border border-line bg-surface p-5">
                <div v-if="editando !== item.id">
                    <div class="flex items-start gap-3">
                        <CaixaDeLote :lote="lote" :id="item.id" :rotulo="item.pergunta" />
                        <h2 class="min-w-0 flex-1 font-display text-lg font-bold uppercase text-fg">{{ item.pergunta }}</h2>
                        <EyeOff v-if="!item.publicada" class="mt-1 h-4 w-4 shrink-0 text-fg-subtle" aria-label="Não publicada" />
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm text-fg-muted">{{ item.resposta }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <template v-if="!arquivados">
                            <SecondaryButton type="button" @click="abrirEdicao(item)">Editar</SecondaryButton>
                            <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="item.pergunta" @confirmar="router.delete(route('painel.perguntas.destroy', item.id), { preserveScroll: true })" />
                        </template>
                        <BotaoRestaurar v-else :lote="lote" :id="item.id" />
                    </div>
                </div>

                <form v-else class="space-y-3" @submit.prevent="edicao.put(route('painel.perguntas.update', item.id), { preserveScroll: true, onSuccess: () => (editando = null) })">
                    <TextInput v-model="edicao.pergunta" aria-label="Pergunta" />
                    <textarea v-model="edicao.resposta" rows="3" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" aria-label="Resposta" />
                    <div class="flex flex-wrap items-center gap-3">
                        <PrimaryButton :disabled="edicao.processing">Salvar</PrimaryButton>
                        <SecondaryButton type="button" @click="editando = null">Cancelar</SecondaryButton>
                        <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.publicada" />Publicada</label>
                    </div>
                </form>
            </article>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="help-circle" titulo="Nenhuma dúvida cadastrada" descricao="As perguntas que mais chegam no WhatsApp são as que devem estar aqui." />
    </AuthenticatedLayout>
</template>
