<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ShieldAlert } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    participacao: { type: Object, default: null },
    shows: { type: Array, default: () => [] },
});
const editando = computed(() => props.participacao !== null);

const form = useForm({
    nome: props.participacao?.nome ?? '',
    funcao: props.participacao?.funcao ?? '',
    descricao: props.participacao?.descricao ?? '',
    instagram: props.participacao?.instagram ?? '',
    facebook: props.participacao?.facebook ?? '',
    tiktok: props.participacao?.tiktok ?? '',
    youtube: props.participacao?.youtube ?? '',
    site_url: props.participacao?.site_url ?? '',
    ordem: props.participacao?.ordem ?? 0,
    publicada: props.participacao?.publicada ?? true,
    shows: props.participacao?.shows ?? [],
    autorizacao_imagem_em: props.participacao?.autorizacao_imagem_em ?? '',
    autorizacao_documento: null,
    foto: null,
});

function enviar() {
    editando.value
        ? form.transform((d) => ({ ...d, _method: 'put' })).post(route('painel.participacoes.update', props.participacao.uuid), { forceFormData: true })
        : form.post(route('painel.participacoes.store'), { forceFormData: true });
}

const campo = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head :title="editando ? 'Editar participação' : 'Nova participação'" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            :titulo="editando ? 'Editar participação especial' : 'Nova participação especial'"
            :voltar-para="route('painel.participacoes.index')"
            voltar-rotulo="Participações especiais"
        />

        <form class="max-w-3xl space-y-6" @submit.prevent="enviar">
            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Quem é</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><InputLabel value="Nome" obrigatorio /><TextInput v-model="form.nome" /><InputError :message="form.errors.nome" /></div>
                    <div><InputLabel value="Função" /><TextInput v-model="form.funcao" placeholder="Guitarra, sax, voz…" /><InputError :message="form.errors.funcao" /></div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Descrição" />
                        <textarea v-model="form.descricao" rows="4" maxlength="2000" :class="campo" />
                        <InputError :message="form.errors.descricao" />
                    </div>
                    <div><InputLabel value="Instagram" /><TextInput v-model="form.instagram" type="url" /><InputError :message="form.errors.instagram" /></div>
                    <div><InputLabel value="Facebook" /><TextInput v-model="form.facebook" type="url" /><InputError :message="form.errors.facebook" /></div>
                    <div><InputLabel value="TikTok" /><TextInput v-model="form.tiktok" type="url" /><InputError :message="form.errors.tiktok" /></div>
                    <div><InputLabel value="YouTube" /><TextInput v-model="form.youtube" type="url" /><InputError :message="form.errors.youtube" /></div>
                    <div><InputLabel value="Site" /><TextInput v-model="form.site_url" type="url" /><InputError :message="form.errors.site_url" /></div>
                    <div><InputLabel value="Ordem" /><TextInput v-model="form.ordem" type="number" min="0" /><InputError :message="form.errors.ordem" /></div>
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-1 font-display text-xl font-bold uppercase text-fg">Noites com a banda</h2>
                <p class="mb-4 text-sm text-fg-muted">A participação aparece na página de cada noite marcada.</p>
                <div v-if="shows.length" class="grid max-h-72 gap-1 overflow-y-auto sm:grid-cols-2">
                    <label v-for="s in shows" :key="s.valor" class="flex min-h-[44px] items-center gap-2 text-sm text-fg">
                        <input v-model="form.shows" type="checkbox" :value="s.valor" class="rounded border-line-input text-accent focus:ring-accent-ring" />{{ s.rotulo }}
                    </label>
                </div>
                <p v-else class="text-sm text-fg-subtle">Nenhum show cadastrado ainda.</p>
                <InputError :message="form.errors.shows" />
            </section>

            <section class="rounded-lg border border-warning bg-warning-subtle p-5">
                <div class="mb-4 flex items-start gap-3">
                    <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0 text-warning" aria-hidden="true" />
                    <div>
                        <h2 class="font-display text-xl font-bold uppercase text-warning">Autorização de uso de imagem</h2>
                        <p class="mt-1 text-sm text-warning">
                            Nome e rosto de convidado são dado pessoal de terceiro. Sem a data preenchida aqui, a participação
                            <strong>não aparece no site</strong> — nem o nome, nem a foto.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Autorizou em" />
                        <TextInput v-model="form.autorizacao_imagem_em" type="date" />
                        <InputError :message="form.errors.autorizacao_imagem_em" />
                    </div>
                    <div>
                        <InputLabel value="Documento (opcional)" />
                        <input type="file" accept="image/*,application/pdf" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-surface file:px-4 file:py-2 file:text-sm file:font-semibold file:text-fg" @change="form.autorizacao_documento = $event.target.files[0]" />
                        <p class="mt-1 text-xs text-warning">
                            Guardado em área privada, nunca servido por URL pública.
                            <template v-if="participacao?.temDocumento"> Já há um documento arquivado.</template>
                        </p>
                        <InputError :message="form.errors.autorizacao_documento" />
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Foto e publicação</h2>
                <img v-if="participacao?.foto" :src="participacao.foto" alt="Foto atual" class="mb-2 h-32 rounded-md border border-line object-cover" />
                <input type="file" accept="image/*" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent" @change="form.foto = $event.target.files[0]" />
                <InputError :message="form.errors.foto" />

                <label class="mt-4 flex items-center gap-3">
                    <Checkbox v-model:checked="form.publicada" />
                    <span class="text-sm text-fg">Publicada no site</span>
                </label>
            </section>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton :disabled="form.processing">{{ editando ? 'Salvar' : 'Cadastrar' }}</PrimaryButton>
                <Link :href="route('painel.participacoes.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
