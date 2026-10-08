<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    show: { type: Object, default: null },
    locais: { type: Array, default: () => [] },
    status: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    podeVerValores: { type: Boolean, default: false },
});

const editando = computed(() => props.show !== null);

const form = useForm({
    local_id: props.show?.local_id ?? '',
    titulo: props.show?.titulo ?? '',
    endereco_livre: props.show?.endereco_livre ?? '',
    mapa_url: props.show?.mapa_url ?? '',
    comeca_em: props.show?.comeca_em ?? '',
    termina_em: props.show?.termina_em ?? '',
    status: props.show?.status ?? 'confirmado',
    tipo: props.show?.tipo ?? 'publico',
    entrada: props.show?.entrada ?? '',
    observacoes_publicas: props.show?.observacoes_publicas ?? '',
    observacoes_internas: props.show?.observacoes_internas ?? '',
    cache: props.show?.cache ?? '',
    destaque: props.show?.destaque ?? false,
    publicado: props.show?.publicado ?? true,
    cartaz: null,
});

function enviar() {

    if (editando.value) {
        form.transform((dados) => ({ ...dados, _method: 'put' }))
            .post(route('painel.shows.update', props.show.uuid), { forceFormData: true });
    } else {
        form.post(route('painel.shows.store'), { forceFormData: true });
    }
}
</script>

<template>
    <Head :title="editando ? 'Editar show' : 'Novo show'" />

    <AuthenticatedLayout>
        <template #cabecalho>
            <p class="font-label text-label uppercase text-fg-subtle">Agenda</p>
        </template>

        <PageHeader
            :titulo="editando ? 'Editar show' : 'Novo show'"
            :voltar-para="route('painel.shows.index')"
            voltar-rotulo="Agenda"
        />

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="enviar">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Onde e quando</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <InputLabel value="Local" />
                            <SelectComBusca rotulo="Local" v-model="form.local_id" :opcoes="locais" opcao-vazia="Nenhuma — é um evento avulso" />
                            <p class="mt-1 text-sm text-fg-subtle">
                                Escolhendo o local, o endereço e o mapa vêm dele — corrigir uma vez corrige todos os shows.
                            </p>
                            <InputError :message="form.errors.local_id" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel value="Título (quando não há local cadastrado)" />
                            <TextInput v-model="form.titulo" placeholder="Aniversário · evento particular" />
                            <InputError :message="form.errors.titulo" />
                        </div>

                        <div>
                            <InputLabel value="Começa em" obrigatorio />
                            <TextInput v-model="form.comeca_em" type="datetime-local" />
                            <InputError :message="form.errors.comeca_em" />
                        </div>

                        <div>
                            <InputLabel value="Termina em" />
                            <TextInput v-model="form.termina_em" type="datetime-local" />
                            <InputError :message="form.errors.termina_em" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel value="Endereço avulso" />
                            <TextInput v-model="form.endereco_livre" placeholder="Rua, número, bairro, cidade" />
                            <InputError :message="form.errors.endereco_livre" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel value="Link do mapa" />
                            <TextInput v-model="form.mapa_url" type="url" placeholder="https://maps.google.com/..." />
                            <InputError :message="form.errors.mapa_url" />
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Detalhes</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Entrada" />
                            <TextInput v-model="form.entrada" placeholder="Entrada franca · Couvert R$ 20" />
                            <InputError :message="form.errors.entrada" />
                        </div>

                        <div v-if="podeVerValores">
                            <InputLabel value="Cachê (R$)" />
                            <TextInput v-model="form.cache" type="number" step="0.01" min="0" />
                            <p class="mt-1 text-sm text-fg-subtle">Interno. Não aparece no site nem para quem não tem permissão de valores.</p>
                            <InputError :message="form.errors.cache" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel value="Observação pública" />
                            <textarea
                                v-model="form.observacoes_publicas"
                                rows="2"
                                class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                                placeholder="Aparece na agenda do site."
                            />
                            <InputError :message="form.errors.observacoes_publicas" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel value="Observação interna" />
                            <textarea
                                v-model="form.observacoes_internas"
                                rows="3"
                                class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                                placeholder="Contato do dia, o que levar, horário de passagem de som. Nunca sai no site."
                            />
                            <InputError :message="form.errors.observacoes_internas" />
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Publicação</h2>

                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Situação" obrigatorio />
                            <select
                                v-model="form.status"
                                class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                            >
                                <option v-for="opcao in status" :key="opcao.valor" :value="opcao.valor">{{ opcao.rotulo }}</option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div>
                            <InputLabel value="Tipo" obrigatorio />
                            <select
                                v-model="form.tipo"
                                class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                            >
                                <option v-for="opcao in tipos" :key="opcao.valor" :value="opcao.valor">{{ opcao.rotulo }}</option>
                            </select>
                            <p class="mt-1 text-sm text-fg-subtle">
                                Evento particular entra na agenda interna e nunca no site — endereço de festa privada não vai para a internet.
                            </p>
                            <InputError :message="form.errors.tipo" />
                        </div>

                        <label class="flex items-start gap-3">
                            <Checkbox v-model:checked="form.publicado" />
                            <span class="text-sm text-fg">Publicado</span>
                        </label>

                        <label class="flex items-start gap-3">
                            <Checkbox v-model:checked="form.destaque" />
                            <span class="text-sm text-fg">Destacar na home</span>
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Cartaz</h2>

                    <img
                        v-if="show?.cartaz"
                        :src="show.cartaz"
                        alt="Cartaz atual do show"
                        class="mb-3 w-full rounded-md border border-line"
                    />

                    <input
                        type="file"
                        accept="image/*"
                        class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent"
                        @change="form.cartaz = $event.target.files[0]"
                    />
                    <InputError :message="form.errors.cartaz" />
                </section>

                <div class="flex flex-wrap gap-2">
                    <PrimaryButton :disabled="form.processing">
                        {{ editando ? 'Salvar' : 'Cadastrar' }}
                    </PrimaryButton>
                    <Link :href="route('painel.shows.index')">
                        <SecondaryButton type="button">Cancelar</SecondaryButton>
                    </Link>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
