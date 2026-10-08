<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import MateriaisLigados from '@/Components/Painel/MateriaisLigados.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    local: { type: Object, default: null },

    tipos: { type: Array, default: () => [] },
    materiais: { type: Array, default: () => [] },
});
const editando = computed(() => props.local !== null);

const form = useForm({
    nome: props.local?.nome ?? '',
    tipo_id: props.local?.tipo_id ?? '',
    cidade: props.local?.cidade ?? '',
    uf: props.local?.uf ?? '',
    endereco: props.local?.endereco ?? '',
    bairro: props.local?.bairro ?? '',
    cep: props.local?.cep ?? '',
    mapa_url: props.local?.mapa_url ?? '',
    site_url: props.local?.site_url ?? '',
    instagram: props.local?.instagram ?? '',
    contato_nome: props.local?.contato_nome ?? '',
    contato_telefone: props.local?.contato_telefone ?? '',
    observacoes: props.local?.observacoes ?? '',
    ativa: props.local?.ativa ?? true,
    logo: null,
});

function enviar() {
    editando.value
        ? form.transform((d) => ({ ...d, _method: 'put' })).post(route('painel.locais.update', props.local.uuid), { forceFormData: true })
        : form.post(route('painel.locais.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="editando ? 'Editar local' : 'Novo local'" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Agenda</p></template>

        <PageHeader :titulo="editando ? 'Editar local' : 'Novo local'" :voltar-para="route('painel.locais.index')" voltar-rotulo="Locais" />

        <form class="max-w-3xl space-y-6" @submit.prevent="enviar">
            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Identificação</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel value="Nome" obrigatorio />
                        <TextInput v-model="form.nome" />
                        <InputError :message="form.errors.nome" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="local-tipo" value="Tipo de espaço" />
                        <select id="local-tipo" v-model="form.tipo_id" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring">
                            <option value="">Sem tipo</option>
                            <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                        </select>
                        <p class="mt-1 text-xs text-fg-subtle">
                            Aparece no site, ao lado do endereço do show.
                            <Link :href="route('painel.tipos-espaco.index')" class="text-accent underline">Editar a lista de tipos</Link>
                        </p>
                        <InputError :message="form.errors.tipo_id" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Endereço" />
                        <TextInput v-model="form.endereco" placeholder="Rua das Flores, 100" />
                        <InputError :message="form.errors.endereco" />
                    </div>
                    <div><InputLabel value="Bairro" /><TextInput v-model="form.bairro" /><InputError :message="form.errors.bairro" /></div>
                    <div><InputLabel value="CEP" /><TextInput v-model="form.cep" /><InputError :message="form.errors.cep" /></div>
                    <div><InputLabel value="Cidade" /><TextInput v-model="form.cidade" /><InputError :message="form.errors.cidade" /></div>
                    <div><InputLabel value="UF" /><TextInput v-model="form.uf" maxlength="2" /><InputError :message="form.errors.uf" /></div>
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Links e contato</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel value="Link do mapa" />
                        <TextInput v-model="form.mapa_url" type="url" />
                        <p class="mt-1 text-sm text-fg-subtle">Sem isto, o site monta a busca pelo endereço — o que erra em local sem número.</p>
                        <InputError :message="form.errors.mapa_url" />
                    </div>
                    <div><InputLabel value="Site" /><TextInput v-model="form.site_url" type="url" /><InputError :message="form.errors.site_url" /></div>
                    <div><InputLabel value="Instagram" /><TextInput v-model="form.instagram" type="url" /><InputError :message="form.errors.instagram" /></div>
                    <div><InputLabel value="Contato" /><TextInput v-model="form.contato_nome" /><InputError :message="form.errors.contato_nome" /></div>
                    <div><InputLabel value="Telefone do contato" /><TextInput v-model="form.contato_telefone" /><InputError :message="form.errors.contato_telefone" /></div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Observações internas" />
                        <textarea v-model="form.observacoes" rows="3" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" placeholder="Cachê praticado, se tem PA, horário de fechamento." />
                        <InputError :message="form.errors.observacoes" />
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Logo</h2>
                <img v-if="local?.logo" :src="local.logo" alt="Logo atual do local" class="mb-3 h-20 rounded-md border border-line object-contain" />
                <input type="file" accept="image/*" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent" @change="form.logo = $event.target.files[0]" />
                <p class="mt-2 text-sm text-fg-subtle">
                    Usar a arte do local no cartaz daquele show tudo bem. Como peça institucional permanente da banda, não.
                </p>
                <InputError :message="form.errors.logo" />

                <label class="mt-4 flex items-center gap-3">
                    <Checkbox v-model:checked="form.ativa" />
                    <span class="text-sm text-fg">Local ativo (aparece na hora de cadastrar show)</span>
                </label>
            </section>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton :disabled="form.processing">{{ editando ? 'Salvar' : 'Cadastrar' }}</PrimaryButton>
                <Link :href="route('painel.locais.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
            </div>

            <MateriaisLigados :materiais="materiais" titulo="Materiais deste local" />
        </form>
    </AuthenticatedLayout>
</template>
