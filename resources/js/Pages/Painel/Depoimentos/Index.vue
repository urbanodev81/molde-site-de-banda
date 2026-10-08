<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { ref } from 'vue';
import { Plus, Quote } from 'lucide-vue-next';
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
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    depoimentos: { type: Array, default: () => [] },
    shows: { type: Array, default: () => [] },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'depoimentos',
    ids: () => props.depoimentos.map((d) => d.id),
    podeGerenciar: () => true,
    arquivados: () => props.arquivados,
    nome: ['depoimento', 'depoimentos', 'o'],
});

const form = useForm({ autor: '', papel: '', texto: '', show_id: '', ocorrido_em: '', autorizado: false, publicado: false, ordem: 0 });
const editando = ref(null);
const edicao = useForm({ autor: '', papel: '', texto: '', show_id: '', ocorrido_em: '', autorizado: false, publicado: false, ordem: 0 });

function abrirEdicao(item) {
    editando.value = item.id;
    Object.assign(edicao, {
        autor: item.autor, papel: item.papel ?? '', texto: item.texto, show_id: item.show_id ?? '',
        ocorrido_em: item.ocorrido_em ?? '', autorizado: item.autorizado, publicado: item.publicado, ordem: item.ordem,
    });
}
</script>

<template>
    <Head title="Depoimentos" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Depoimentos"
            descricao="Vai para o site só quando as DUAS caixas estiverem marcadas: autorizado por quem falou e publicado por vocês."
        />

        <form v-if="!arquivados" class="mb-6 rounded-lg border border-line bg-surface p-5" @submit.prevent="form.post(route('painel.depoimentos.store'), { preserveScroll: true, onSuccess: () => form.reset() })">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Novo depoimento</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><InputLabel value="Quem falou" obrigatorio /><TextInput v-model="form.autor" /><InputError :message="form.errors.autor" /></div>
                <div><InputLabel value="Quem é" /><TextInput v-model="form.papel" placeholder="dono do Casa da Esquina" /></div>
                <div class="sm:col-span-2">
                    <InputLabel value="O que disse" obrigatorio />
                    <textarea v-model="form.texto" rows="3" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" />
                    <InputError :message="form.errors.texto" />
                </div>
                <div>
                    <InputLabel value="Show relacionado" />
                    <SelectComBusca rotulo="Show relacionado" v-model="form.show_id" :opcoes="shows" opcao-vazia="Nenhum" />
                </div>
                <div><InputLabel value="Quando" /><TextInput v-model="form.ocorrido_em" type="date" /></div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing"><Plus class="h-4 w-4" aria-hidden="true" />Adicionar</PrimaryButton>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.autorizado" />Autorizou o uso</label>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.publicado" />Publicar no site</label>
            </div>
        </form>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Cadastrados" />

        <div v-if="depoimentos.length" class="grid gap-4 lg:grid-cols-2">
            <article v-for="item in depoimentos" :key="item.id" class="rounded-lg border border-line bg-surface p-5">
                <CaixaDeLote :lote="lote" :id="item.id" :rotulo="`depoimento de ${item.autor}`" class="-ml-2 -mt-2" />
                <div v-if="editando !== item.id">
                    <Quote class="mb-2 h-5 w-5 text-accent" aria-hidden="true" />
                    <p class="text-sm italic text-fg">“{{ item.texto }}”</p>
                    <p class="mt-3 font-semibold text-fg">{{ item.autor }}</p>
                    <p v-if="item.papel" class="text-sm text-fg-muted">{{ item.papel }}</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <Chip :rotulo="item.noSite ? 'No site' : 'Fora do site'" :token="item.noSite ? 'success' : 'fg-subtle'" :icone="item.noSite ? 'eye' : 'eye-off'" />
                        <Chip v-if="!item.autorizado" rotulo="Sem autorização" token="warning" icone="shield-alert" />
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <template v-if="!arquivados">
                            <SecondaryButton type="button" @click="abrirEdicao(item)">Editar</SecondaryButton>
                            <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="item.autor" @confirmar="router.delete(route('painel.depoimentos.destroy', item.id), { preserveScroll: true })" />
                        </template>
                        <BotaoRestaurar v-else :lote="lote" :id="item.id" />
                    </div>
                </div>

                <form v-else class="space-y-3" @submit.prevent="edicao.put(route('painel.depoimentos.update', item.id), { preserveScroll: true, onSuccess: () => (editando = null) })">
                    <TextInput v-model="edicao.autor" aria-label="Quem falou" />
                    <TextInput v-model="edicao.papel" aria-label="Quem é" />
                    <textarea v-model="edicao.texto" rows="3" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" aria-label="Texto" />
                    <div class="flex flex-wrap items-center gap-3">
                        <PrimaryButton :disabled="edicao.processing">Salvar</PrimaryButton>
                        <SecondaryButton type="button" @click="editando = null">Cancelar</SecondaryButton>
                        <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.autorizado" />Autorizou</label>
                        <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.publicado" />Publicar</label>
                    </div>
                </form>
            </article>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="quote" titulo="Nenhum depoimento" descricao="Uma frase do dono do bar convence mais do que um parágrafo escrito pela banda." />
    </AuthenticatedLayout>
</template>
