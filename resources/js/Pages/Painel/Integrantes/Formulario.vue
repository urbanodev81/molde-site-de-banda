<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ShieldAlert } from 'lucide-vue-next';
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
    integrante: { type: Object, default: null },
    materiais: { type: Array, default: () => [] },
});
const editando = computed(() => props.integrante !== null);

const form = useForm({
    nome: props.integrante?.nome ?? '',
    nome_artistico: props.integrante?.nome_artistico ?? '',
    instrumento: props.integrante?.instrumento ?? '',
    bio: props.integrante?.bio ?? '',
    instagram: props.integrante?.instagram ?? '',
    facebook: props.integrante?.facebook ?? '',
    tiktok: props.integrante?.tiktok ?? '',
    ordem: props.integrante?.ordem ?? 0,
    ativa: props.integrante?.ativa ?? true,
    palco_esquerda: props.integrante?.palco_esquerda ?? 20,
    palco_largura: props.integrante?.palco_largura ?? 24,
    palco_base: props.integrante?.palco_base ?? 0,
    autorizacao_imagem_em: props.integrante?.autorizacao_imagem_em ?? '',
    autorizacao_documento: null,
    foto: null,
    recorte: null,
});

function enviar() {
    editando.value
        ? form.transform((d) => ({ ...d, _method: 'put' })).post(route('painel.integrantes.update', props.integrante.uuid), { forceFormData: true })
        : form.post(route('painel.integrantes.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="editando ? 'Editar integrante' : 'Nova integrante'" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            :titulo="editando ? 'Editar integrante' : 'Nova integrante'"
            :voltar-para="route('painel.integrantes.index')"
            voltar-rotulo="Integrantes"
        />

        <form class="max-w-3xl space-y-6" @submit.prevent="enviar">
            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Quem é</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><InputLabel value="Nome" obrigatorio /><TextInput v-model="form.nome" /><InputError :message="form.errors.nome" /></div>
                    <div><InputLabel value="Como aparece no site" /><TextInput v-model="form.nome_artistico" /><InputError :message="form.errors.nome_artistico" /></div>
                    <div><InputLabel value="Função na banda" /><TextInput v-model="form.instrumento" placeholder="Voz e violão" /><InputError :message="form.errors.instrumento" /></div>
                    <div><InputLabel value="Ordem de entrada no palco" /><TextInput v-model="form.ordem" type="number" min="0" /><InputError :message="form.errors.ordem" /></div>
                    <div><InputLabel value="Instagram" /><TextInput v-model="form.instagram" type="url" /><InputError :message="form.errors.instagram" /></div>
                    <div><InputLabel value="Facebook" /><TextInput v-model="form.facebook" type="url" /><InputError :message="form.errors.facebook" /></div>
                    <div class="sm:col-span-2"><InputLabel value="TikTok" /><TextInput v-model="form.tiktok" type="url" /><InputError :message="form.errors.tiktok" /></div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Descrição" />
                        <p class="mb-1 text-xs text-fg-subtle">Aparece no cartão do palco e na página "A banda". As fotos e os vídeos dela se marcam na galeria e em cada vídeo.</p>
                        <textarea v-model="form.bio" rows="5" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" />
                        <InputError :message="form.errors.bio" />
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-warning bg-warning-subtle p-5">
                <div class="mb-4 flex items-start gap-3">
                    <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0 text-warning" aria-hidden="true" />
                    <div>
                        <h2 class="font-display text-xl font-bold uppercase text-warning">Autorização de uso de imagem</h2>
                        <p class="mt-1 text-sm text-warning">
                            Nome e rosto são dado pessoal. Sem a data preenchida aqui, esta integrante <strong>não aparece no site</strong> —
                            e isso é a trava, não um esquecimento do sistema.
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
                            <template v-if="integrante?.temDocumento"> Já há um documento arquivado.</template>
                        </p>
                        <InputError :message="form.errors.autorizacao_documento" />
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Fotos</h2>
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Foto normal" />
                        <img v-if="integrante?.foto" :src="integrante.foto" alt="Foto atual" class="mb-2 h-32 rounded-md border border-line object-cover" />
                        <input type="file" accept="image/*" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent" @change="form.foto = $event.target.files[0]" />
                        <InputError :message="form.errors.foto" />
                    </div>
                    <div>
                        <InputLabel value="Recorte sem fundo (PNG)" />
                        <img v-if="integrante?.recorte" :src="integrante.recorte" alt="Recorte atual" class="mb-2 h-32 rounded-md border border-line object-contain" />
                        <input type="file" accept="image/png,image/webp" class="w-full text-sm text-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-accent-subtle file:px-4 file:py-2 file:text-sm file:font-semibold file:text-accent" @change="form.recorte = $event.target.files[0]" />
                        <p class="mt-1 text-xs text-fg-subtle">É o que entra voando no palco da home. JPG não guarda transparência.</p>
                        <InputError :message="form.errors.recorte" />
                    </div>
                </div>

                <label class="mt-4 flex items-center gap-3">
                    <Checkbox v-model:checked="form.ativa" />
                    <span class="text-sm text-fg">Integrante ativa na banda</span>
                </label>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-1 font-display text-xl font-bold uppercase text-fg">Lugar no palco</h2>
                <p class="mb-4 text-sm text-fg-muted">
                    Em porcentagem do palco da home. Mexa aos poucos e confira no site — os valores que vieram
                    de fábrica são os da arte aprovada com a banda.
                </p>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel value="Da esquerda (%)" />
                        <TextInput v-model="form.palco_esquerda" type="number" min="0" max="100" />
                        <InputError :message="form.errors.palco_esquerda" />
                    </div>
                    <div>
                        <InputLabel value="Largura (%)" />
                        <TextInput v-model="form.palco_largura" type="number" min="1" max="100" />
                        <InputError :message="form.errors.palco_largura" />
                    </div>
                    <div>
                        <InputLabel value="Altura do chão (%)" />
                        <TextInput v-model="form.palco_base" type="number" min="0" max="100" />
                        <InputError :message="form.errors.palco_base" />
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton :disabled="form.processing">{{ editando ? 'Salvar' : 'Cadastrar' }}</PrimaryButton>
                <Link :href="route('painel.integrantes.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
            </div>

            <MateriaisLigados :materiais="materiais" />
        </form>
    </AuthenticatedLayout>
</template>
