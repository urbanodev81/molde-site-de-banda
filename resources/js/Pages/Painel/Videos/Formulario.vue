<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EnvioDeVideo from '@/Components/Painel/EnvioDeVideo.vue';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    video: { type: Object, default: null },
    locais: { type: Array, default: () => [] },
    shows: { type: Array, default: () => [] },
    tiposDeGaleria: { type: Array, default: () => [] },
    integrantes: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
    limite: { type: Number, default: 8 },
});

const editando = computed(() => props.video !== null);

const form = useForm({
    titulo: props.video?.titulo ?? '',
    local_id: props.video?.local_id ?? '',
    show_id: props.video?.show_id ?? '',
    tipo_galeria_id: props.video?.tipo_galeria_id ?? '',
    integrantes: props.video?.integrantes ?? [],
    local_nome: props.video?.local_nome ?? '',
    gravado_em: props.video?.gravado_em ?? '',
    tipo: props.video?.tipo ?? 'arquivo',
    link: props.video?.link ?? '',
    demonstracao: props.video?.demonstracao ?? false,
    ordem: props.video?.ordem ?? 0,
    publicado: props.video?.publicado ?? true,
    capa: null,
    mp4: null,
});

const capaManual = ref(false);

function capaDoVideo(arquivo) {
    if (!capaManual.value) {
        form.capa = arquivo;
    }
}

function escolherCapa(evento) {
    form.capa = evento.target.files[0] ?? null;
    capaManual.value = form.capa !== null;
}

const tipos = [
    { valor: 'arquivo', rotulo: 'Arquivo curto (mp4)' },
    { valor: 'link', rotulo: 'Link (YouTube, Vimeo, Instagram, TikTok)' },
];

function enviar() {
    editando.value
        ? form.transform((d) => ({ ...d, _method: 'put' })).post(route('painel.videos.update', props.video.uuid), { forceFormData: true })
        : form.post(route('painel.videos.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="editando ? 'Editar vídeo' : 'Novo vídeo'" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader :titulo="editando ? 'Editar vídeo' : 'Novo vídeo'" :voltar-para="route('painel.videos.index')" voltar-rotulo="Vídeos" />

        <form class="max-w-3xl space-y-6" @submit.prevent="enviar">
            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Identificação</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><InputLabel value="Título" obrigatorio /><TextInput v-model="form.titulo" placeholder="Casa da Esquina · set de sábado" /><InputError :message="form.errors.titulo" /></div>
                    <div>
                        <InputLabel value="Local cadastrado" />
                        <SelectComBusca rotulo="Local cadastrado" v-model="form.local_id" :opcoes="locais" opcao-vazia="Nenhuma" />
                        <InputError :message="form.errors.local_id" />
                    </div>
                    <div><InputLabel value="Ou onde foi (texto)" /><TextInput v-model="form.local_nome" placeholder="Estúdio · São Paulo" /><InputError :message="form.errors.local_nome" /></div>
                    <div><InputLabel value="Gravado em" /><TextInput v-model="form.gravado_em" type="date" /><InputError :message="form.errors.gravado_em" /></div>

                    <div class="sm:col-span-2">
                        <InputLabel value="De que show é este vídeo" />
                        <SelectComBusca rotulo="De que show é este vídeo" v-model="form.show_id" :opcoes="shows" opcao-vazia="Nenhum — vídeo de estúdio ou avulso" />
                        <p class="mt-1 text-sm text-fg-subtle">Ligado a um show, o vídeo entra na página daquela noite, junto das fotos.</p>
                        <InputError :message="form.errors.show_id" />
                    </div>

                    <div>
                        <InputLabel value="Tipo da galeria" />
                        <SelectComBusca rotulo="Tipo da galeria" v-model="form.tipo_galeria_id" :opcoes="tiposDeGaleria" opcao-vazia="Nenhum (herda do show)" />
                        <InputError :message="form.errors.tipo_galeria_id" />
                    </div>
                    <fieldset v-if="integrantes.length" class="sm:col-span-2">
                        <legend class="mb-1 text-sm font-medium text-fg">Quem aparece</legend>
                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                            <label v-for="i in integrantes" :key="i.valor" class="flex min-h-[44px] items-center gap-2 text-sm text-fg">
                                <input v-model="form.integrantes" type="checkbox" :value="i.valor" class="rounded border-line-input text-accent focus:ring-accent-ring" />{{ i.rotulo }}
                            </label>
                        </div>
                        <p class="mt-1 text-sm text-fg-subtle">O vídeo entra na página "A banda", no bloco de cada uma.</p>
                        <InputError :message="form.errors.integrantes" />
                    </fieldset>
                    <div><InputLabel value="Ordem na aba" /><TextInput v-model="form.ordem" type="number" min="0" /><InputError :message="form.errors.ordem" /></div>
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">De onde toca</h2>

                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="opcao in tipos"
                        :key="opcao.valor"
                        type="button"
                        class="min-h-[44px] rounded-md border px-4 text-sm font-semibold transition"
                        :class="form.tipo === opcao.valor ? 'border-line-strong bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:bg-surface-sunken'"
                        :aria-pressed="form.tipo === opcao.valor"
                        @click="form.tipo = opcao.valor"
                    >{{ opcao.rotulo }}</button>
                </div>

                <div v-if="form.tipo === 'link'">
                    <InputLabel value="Link do vídeo" obrigatorio />
                    <TextInput v-model="form.link" placeholder="https://youtu.be/...  ·  https://www.instagram.com/reel/..." />
                    <p class="mt-1 text-sm text-fg-subtle">
                        Cole o link do jeito que ele sai do celular. Não pesa nada no site: o vídeo continua no YouTube,
                        Vimeo, Instagram ou TikTok e só carrega depois do clique.
                    </p>
                    <InputError :message="form.errors.link" />
                </div>

                <EnvioDeVideo
                    v-else
                    :limites="limites"
                    :erro="form.errors.mp4"
                    :ja-enviado="video?.temMp4 ? (video.duracao ?? 'mp4') : null"
                    @arquivo="form.mp4 = $event"
                    @capa="capaDoVideo"
                />
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Capa e publicação</h2>
                <img v-if="video?.capa" :src="video.capa" alt="Capa atual" class="mb-3 w-64 rounded-md border border-line" />
                <input type="file" accept="image/*" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent" @change="escolherCapa" />
                <p class="mt-1 text-xs text-fg-subtle">Opcional. Sem capa, o vídeo enviado usa um quadro dele mesmo, e o do YouTube usa a do YouTube.</p>
                <InputError :message="form.errors.capa" />

                <div class="mt-4 space-y-3">
                    <label class="flex items-start gap-3">
                        <Checkbox v-model:checked="form.publicado" />
                        <span class="text-sm text-fg">Publicado na aba da home</span>
                    </label>
                    <label class="flex items-start gap-3">
                        <Checkbox v-model:checked="form.demonstracao" />
                        <span class="text-sm text-fg">
                            É material de demonstração, não a banda tocando
                            <span class="block text-fg-subtle">O site marca com selo amarelo, e o painel conta quantas vagas ainda são falsas.</span>
                        </span>
                    </label>
                </div>
            </section>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton :disabled="form.processing">{{ editando ? 'Salvar' : 'Cadastrar' }}</PrimaryButton>
                <Link :href="route('painel.videos.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
