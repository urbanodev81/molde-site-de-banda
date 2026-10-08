<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Archive, ArchiveRestore, Eye, EyeOff, Printer, Send, Star, Tags, Upload } from 'lucide-vue-next';
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
import BarraDeAcoesEmLote from '@/Components/BarraDeAcoesEmLote.vue';
import ModalEnviarLista from '@/Components/ModalEnviarLista.vue';
import Paginacao from '@/Components/Paginacao.vue';
import { useSelecaoEmLote } from '@/Composables/useSelecaoEmLote';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    fotos: { type: Object, required: true },
    shows: { type: Array, default: () => [] },
    integrantes: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },

    arquivadas: { type: Boolean, default: false },
    totais: { type: Object, default: () => ({ ativas: 0, arquivadas: 0 }) },
    podeGerenciar: { type: Boolean, default: false },
});

const form = useForm({
    arquivo: null, legenda: '', descricao: '', credito: '', show_id: '', integrantes: [],
    tipo_galeria_id: '', registrada_em: '', ordem: 0, publicada: true, destaque: false,
});

const editando = ref(null);
const edicao = useForm({
    legenda: '', descricao: '', credito: '', show_id: '', integrantes: [],
    tipo_galeria_id: '', registrada_em: '', ordem: 0, publicada: true, destaque: false,
});

function abrirEdicao(foto) {
    editando.value = foto.uuid;
    Object.assign(edicao, {
        legenda: foto.legenda ?? '',
        descricao: foto.descricao ?? '',
        credito: foto.credito ?? '',
        show_id: foto.show_id ?? '',
        integrantes: [...(foto.integrante_ids ?? [])],
        tipo_galeria_id: foto.tipo_galeria_id ?? '',
        registrada_em: foto.registrada_em ?? '',
        ordem: foto.ordem,
        publicada: foto.publicada,
        destaque: foto.destaque,
    });
}

const selecao = useSelecaoEmLote(() => props.fotos.data.map((f) => f.uuid));
const enviando = ref(false);

const acoes = computed(() => {
    const imprimir = { key: 'imprimir', label: 'Imprimir', icon: Printer };

    if (!props.podeGerenciar) return [imprimir];

    if (props.arquivadas) {
        return [{ key: 'restaurar', label: 'Restaurar', icon: ArchiveRestore }, imprimir];
    }

    return [
        { key: 'publicar', label: 'Publicar', icon: Eye },
        { key: 'despublicar', label: 'Tirar do site', icon: EyeOff },
        { key: 'arquivar', label: 'Arquivar', icon: Archive, confirmar: 'Arquivar as fotos marcadas? Elas saem do site e ficam na aba Arquivadas.' },
        imprimir,
        { key: 'enviar', label: 'Enviar por e-mail', icon: Send },
    ];
});

function emLote(acao, ids = selecao.selecionados.value) {
    if (acao === 'imprimir') {
        window.open(route('painel.fotos.imprimir', { ids }), '_blank', 'noopener');
        return;
    }

    if (acao === 'enviar') {
        enviando.value = true;
        return;
    }

    router.post(route('painel.fotos.lote'), { acao, ids: [...ids] }, { preserveScroll: true, onSuccess: selecao.limpar });
}

const abaAtiva = 'bg-accent text-fg-on-accent';
const abaInativa = 'text-fg-muted hover:bg-surface-sunken';

const campoSelect = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head title="Fotos" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader titulo="Galeria" descricao="Foto de show é conteúdo, não decoração — por isso toda imagem leva legenda e crédito.">
            <template #acoes>
                <Link :href="route('painel.tipos-galeria.index')" class="inline-flex items-center gap-2 text-sm font-semibold text-accent hover:underline">
                    <Tags class="h-4 w-4" aria-hidden="true" />Tipos da galeria
                </Link>
            </template>
        </PageHeader>

        <div class="mb-6 rounded-lg border border-line bg-surface-sunken p-4 text-sm text-fg-muted">
            <p>
                <strong class="text-fg">Foto de show</strong> aparece no site quando o show aparece —
                evento particular e show cancelado nunca vão.
                <strong class="text-fg">Foto sem show</strong> (ensaio, gravação, bastidor) precisa de um
                <Link :href="route('painel.tipos-galeria.index')" class="text-accent hover:underline">tipo publicado</Link>.
                <strong class="text-fg">Destaque</strong> põe a foto na frente da faixa da home.
            </p>
        </div>

        <form v-if="podeGerenciar && !arquivadas" class="mb-6 rounded-lg border border-line bg-surface p-5" @submit.prevent="form.post(route('painel.fotos.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() })">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Enviar foto</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <InputLabel value="Arquivo" obrigatorio />
                    <input type="file" accept="image/*" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent" @change="form.arquivo = $event.target.files[0]" />
                    <InputError :message="form.errors.arquivo" />
                </div>
                <div>
                    <InputLabel value="Título" />
                    <TextInput v-model="form.legenda" maxlength="120" />
                    <p class="mt-1 text-xs text-fg-subtle">Vai escrito sobre a foto na grade do site.</p>
                    <InputError :message="form.errors.legenda" />
                </div>
                <div>
                    <InputLabel value="Crédito" />
                    <TextInput v-model="form.credito" placeholder="Quem fotografou" />
                    <p class="mt-1 text-xs text-fg-subtle">É o que permite usar a foto de novo sem constrangimento.</p>
                </div>
                <div>
                    <InputLabel value="Show" />
                    <SelectComBusca rotulo="Show" v-model="form.show_id" :opcoes="shows" opcao-vazia="Nenhum" />
                </div>
                <div>
                    <InputLabel value="Tipo da galeria" />
                    <select v-model="form.tipo_galeria_id" :class="campoSelect">

                        <option value="">Nenhum (herda do show)</option>
                        <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Data" />
                    <TextInput v-model="form.registrada_em" type="date" />
                    <p class="mt-1 text-xs text-fg-subtle">Só para foto sem show. Vazio: a data do envio.</p>
                </div>
                <fieldset>
                    <legend class="mb-1 text-sm font-medium text-fg">Quem aparece</legend>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        <label v-for="i in integrantes" :key="i.valor" class="flex min-h-[44px] items-center gap-2 text-sm text-fg">
                            <input v-model="form.integrantes" type="checkbox" :value="i.valor" class="rounded border-line-input text-accent focus:ring-accent-ring" />{{ i.rotulo }}
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-fg-subtle">A foto entra na página "A banda", no bloco de cada uma.</p>
                </fieldset>
                <div class="sm:col-span-2 lg:col-span-4">
                    <InputLabel value="Descrição" />
                    <textarea v-model="form.descricao" rows="2" maxlength="2000" :class="campoSelect" />
                    <p class="mt-1 text-xs text-fg-subtle">Aparece quando a foto abre grande no site.</p>
                    <InputError :message="form.errors.descricao" />
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing"><Upload class="h-4 w-4" aria-hidden="true" />Enviar</PrimaryButton>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.publicada" />Publicada</label>
                <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.destaque" />Destaque na home</label>
            </div>
        </form>

        <nav class="mb-4 flex flex-wrap gap-1" aria-label="Fotos na galeria ou arquivadas">
            <Link :href="route('painel.fotos.index')" class="flex min-h-[44px] items-center gap-2 rounded-md px-4 text-sm font-semibold" :class="arquivadas ? abaInativa : abaAtiva" :aria-current="arquivadas ? undefined : 'page'">
                Na galeria ({{ totais.ativas }})
            </Link>
            <Link :href="route('painel.fotos.index', { arquivadas: 1 })" class="flex min-h-[44px] items-center gap-2 rounded-md px-4 text-sm font-semibold" :class="arquivadas ? abaAtiva : abaInativa" :aria-current="arquivadas ? 'page' : undefined">
                <Archive class="h-4 w-4" aria-hidden="true" />Arquivadas ({{ totais.arquivadas }})
            </Link>
        </nav>

        <label v-if="fotos.data.length" class="mb-3 inline-flex min-h-[44px] items-center gap-2 text-sm text-fg">
            <Checkbox :checked="selecao.todosMarcados.value" @update:checked="selecao.alternarTodos()" />Marcar as {{ fotos.data.length }} desta página
        </label>

        <BarraDeAcoesEmLote :quantidade="selecao.selecionados.value.length" :acoes="acoes" :nome="['foto', 'fotos']" @acao="emLote($event.key)" @limpar="selecao.limpar()" />

        <div v-if="fotos.data.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <figure v-for="foto in fotos.data" :key="foto.uuid" class="relative overflow-hidden rounded-lg border bg-surface" :class="selecao.marcado(foto.uuid) ? 'border-line-strong ring-2 ring-accent-ring' : 'border-line'">

                <label class="absolute left-2 top-2 flex h-11 w-11 items-center justify-center rounded-md bg-surface/90">
                    <Checkbox :checked="selecao.marcado(foto.uuid)" @update:checked="selecao.alternar(foto.uuid)" />
                    <span class="sr-only">Marcar {{ foto.legenda || 'esta foto' }}</span>
                </label>
                <img :src="foto.url" :alt="foto.alt" class="aspect-[4/3] w-full object-cover" loading="lazy" />

                <figcaption v-if="editando !== foto.uuid" class="p-4">
                    <p class="truncate text-sm font-semibold text-fg">{{ foto.legenda || 'Sem título' }}</p>
                    <p class="truncate text-xs text-fg-subtle">
                        <template v-if="foto.credito">Foto: {{ foto.credito }}</template>
                        <template v-if="foto.show"> · {{ foto.show }}</template>
                        <template v-if="foto.tipo"> · {{ foto.tipo }}</template>
                        <template v-if="foto.integrantes?.length"> · {{ foto.integrantes.join(', ') }}</template>
                    </p>
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <Star v-if="foto.destaque" class="h-4 w-4 text-accent" aria-label="Destaque na home" />
                            <EyeOff v-if="!foto.publicada" class="h-4 w-4 text-fg-subtle" aria-label="Não publicada" />
                        </div>
                        <div v-if="podeGerenciar && !foto.arquivada" class="flex gap-2">
                            <SecondaryButton type="button" @click="abrirEdicao(foto)">Editar</SecondaryButton>
                            <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="foto.legenda || 'esta foto'" @confirmar="router.delete(route('painel.fotos.destroy', foto.uuid), { preserveScroll: true })" />
                        </div>
                        <SecondaryButton v-else-if="podeGerenciar" type="button" @click="emLote('restaurar', [foto.uuid])">
                            <ArchiveRestore class="h-4 w-4" aria-hidden="true" />Restaurar
                        </SecondaryButton>
                    </div>
                </figcaption>

                <form v-else class="grid gap-3 p-4" @submit.prevent="edicao.put(route('painel.fotos.update', foto.uuid), { preserveScroll: true, onSuccess: () => (editando = null) })">
                    <TextInput v-model="edicao.legenda" aria-label="Título" placeholder="Título (sobre a foto)" maxlength="120" />
                    <textarea v-model="edicao.descricao" rows="3" maxlength="2000" :class="campoSelect" aria-label="Descrição" placeholder="Descrição (no visualizador)" />
                    <TextInput v-model="edicao.credito" aria-label="Crédito" placeholder="Quem fotografou" />
                    <SelectComBusca v-model="edicao.show_id" :opcoes="shows" rotulo="Show" opcao-vazia="Sem show" />
                    <SelectComBusca v-model="edicao.tipo_galeria_id" :opcoes="tipos" rotulo="Tipo da galeria" opcao-vazia="Nenhum (herda do show)" />
                    <TextInput v-model="edicao.registrada_em" type="date" aria-label="Data" />
                    <fieldset>
                        <legend class="mb-1 text-xs text-fg-subtle">Quem aparece</legend>
                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                            <label v-for="i in integrantes" :key="i.valor" class="flex min-h-[44px] items-center gap-2 text-sm text-fg">
                                <input v-model="edicao.integrantes" type="checkbox" :value="i.valor" class="rounded border-line-input text-accent focus:ring-accent-ring" />{{ i.rotulo }}
                            </label>
                        </div>
                    </fieldset>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.publicada" />Publicada</label>
                        <label class="flex items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="edicao.destaque" />Destaque</label>
                    </div>
                    <InputError :message="edicao.errors.legenda || edicao.errors.descricao || edicao.errors.tipo_galeria_id || edicao.errors.registrada_em" />
                    <div class="flex gap-2">
                        <PrimaryButton :disabled="edicao.processing">Salvar</PrimaryButton>
                        <SecondaryButton type="button" @click="editando = null">Cancelar</SecondaryButton>
                    </div>
                </form>
            </figure>
        </div>

        <EstadoVazio v-else-if="arquivadas" icone="image" titulo="Nenhuma foto arquivada" descricao="Foto arquivada sai da galeria e do site, e fica guardada aqui até alguém restaurar." />
        <EstadoVazio v-else icone="image" titulo="Galeria vazia" descricao="Uma foto de sala cheia diz mais a quem contrata do que qualquer texto." />

        <Paginacao :links="fotos.links" />

        <ModalEnviarLista
            :show="enviando"
            :ids="selecao.selecionados.value"
            :rota="route('painel.fotos.lote.enviar')"
            :nome="['foto', 'fotos']"
            explicacao="Vai o link de cada foto que já está no site; as que não estão ficam de fora."
            @enviado="selecao.limpar()"
            @close="enviando = false"
        />
    </AuthenticatedLayout>
</template>
