<script setup>

import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { EyeOff, ImagePlus, Link2, Unlink } from 'lucide-vue-next';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EnvioDeVideo from '@/Components/Painel/EnvioDeVideo.vue';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    show: { type: Object, required: true },
    midia: { type: Object, required: true },
});

const fila = ref({ total: 0, feitas: 0, erros: [] });
const campoFotos = ref(null);

function enviarFotos(evento) {
    const arquivos = [...evento.target.files];
    fila.value = { total: arquivos.length, feitas: 0, erros: [] };

    const proxima = () => {
        const arquivo = arquivos.shift();

        if (!arquivo) {
            campoFotos.value.value = '';
            return;
        }

        router.post(
            route('painel.fotos.store'),
            { arquivo, show_id: props.midia.showId, publicada: 1 },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onError: (erros) => fila.value.erros.push(`${arquivo.name}: ${erros.arquivo ?? Object.values(erros)[0]}`),
                onFinish: () => {
                    fila.value.feitas++;
                    proxima();
                },
            },
        );
    };

    proxima();
}

const video = useForm({ titulo: '', modo: 'arquivo', link: '', arquivo: null, capa: null });
const escolhaDaBiblioteca = useForm({ video_id: '' });

function enviarVideo() {
    video.post(route('painel.shows.videos.store', props.show.uuid), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => video.reset(),
    });
}

function vincular() {
    escolhaDaBiblioteca.put(route('painel.shows.videos.vincular', props.show.uuid), {
        preserveScroll: true,
        onSuccess: () => escolhaDaBiblioteca.reset(),
    });
}

function desligar(rota, uuid) {
    router.delete(route(rota, [props.show.uuid, uuid]), { preserveScroll: true });
}

const classeDoSelect = 'min-h-[44px] w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <section class="rounded-lg border border-line bg-surface p-5">
        <h2 class="mb-1 font-display text-xl font-bold uppercase text-fg">Fotos e vídeos desta noite</h2>
        <p class="mb-5 text-sm text-fg-muted">
            Aparecem na página deste show no site depois que ele acontece. Tudo o que sobe aqui fica também nas telas de
            fotos e de vídeos.
        </p>

        <h3 class="mb-2 font-label text-label uppercase text-fg-muted">Fotos ({{ midia.fotos.length }})</h3>

        <ul v-if="midia.fotos.length" class="mb-4 grid grid-cols-3 gap-2 sm:grid-cols-4">
            <li v-for="foto in midia.fotos" :key="foto.uuid" class="relative">
                <img :src="foto.url" :alt="foto.alt" class="aspect-square w-full rounded-md border border-line object-cover" loading="lazy" />
                <span v-if="!foto.publicada" class="absolute left-1 top-1 rounded bg-surface px-1 text-xs text-fg-subtle">
                    <EyeOff class="inline h-3 w-3" aria-hidden="true" /> fora do ar
                </span>
                <button
                    v-if="midia.podeFotos"
                    type="button"
                    class="absolute right-1 top-1 grid h-11 w-11 place-items-center rounded-md bg-surface text-fg-muted transition hover:text-danger"
                    :aria-label="`Tirar esta foto da noite: ${foto.alt}`"
                    @click="desligar('painel.shows.fotos.desligar', foto.uuid)"
                >
                    <Unlink class="h-4 w-4" aria-hidden="true" />
                </button>
            </li>
        </ul>
        <p v-else class="mb-4 text-sm text-fg-subtle">Nenhuma foto desta noite ainda.</p>

        <div v-if="midia.podeFotos" class="mb-6">
            <label class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-md border border-line px-4 text-sm font-semibold text-fg transition hover:bg-surface-sunken focus-within:ring-2 focus-within:ring-accent-ring">
                <ImagePlus class="h-4 w-4" aria-hidden="true" />
                Enviar fotos
                <input ref="campoFotos" type="file" accept="image/*" multiple class="sr-only" @change="enviarFotos" />
            </label>
            <p v-if="fila.total" class="mt-2 text-sm text-fg" aria-live="polite">
                {{ fila.feitas < fila.total ? `Enviando ${fila.feitas + 1} de ${fila.total}…` : `${fila.total - fila.erros.length} de ${fila.total} enviada(s).` }}
            </p>
            <InputError v-for="erro in fila.erros" :key="erro" :message="erro" />
        </div>

        <h3 class="mb-2 font-label text-label uppercase text-fg-muted">Vídeos ({{ midia.videos.length }})</h3>

        <ul v-if="midia.videos.length" class="mb-4 space-y-2">
            <li v-for="v in midia.videos" :key="v.uuid" class="flex items-center gap-3 rounded-md border border-line p-2">
                <img v-if="v.capa" :src="v.capa" alt="" class="h-12 w-20 rounded object-cover" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-fg">{{ v.titulo }}</p>
                    <p class="text-xs text-fg-subtle">
                        {{ v.tipo }}<template v-if="v.duracao"> · {{ v.duracao }}</template><template v-if="!v.publicado"> · fora do ar</template>
                    </p>
                </div>
                <button
                    v-if="midia.podeVideos"
                    type="button"
                    class="grid h-11 w-11 place-items-center rounded-md text-fg-muted transition hover:text-danger"
                    :aria-label="`Tirar o vídeo ${v.titulo} desta noite`"
                    @click="desligar('painel.shows.videos.desligar', v.uuid)"
                >
                    <Unlink class="h-4 w-4" aria-hidden="true" />
                </button>
            </li>
        </ul>
        <p v-else class="mb-4 text-sm text-fg-subtle">Nenhum vídeo desta noite ainda.</p>

        <form v-if="midia.podeVideos" class="space-y-3 rounded-md border border-line p-4" @submit.prevent="enviarVideo">
            <div>
                <InputLabel value="Título do vídeo" obrigatorio />
                <TextInput v-model="video.titulo" placeholder="Titanium · Sia e David Guetta" />
                <InputError :message="video.errors.titulo" />
            </div>

            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="De onde vem o vídeo">
                <button
                    v-for="modo in [{ valor: 'arquivo', rotulo: 'Enviar arquivo curto' }, { valor: 'link', rotulo: 'Colar link' }]"
                    :key="modo.valor"
                    type="button"
                    role="radio"
                    class="min-h-[44px] rounded-md border px-3 text-sm font-semibold transition"
                    :class="video.modo === modo.valor ? 'border-line-strong bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:bg-surface-sunken'"
                    :aria-checked="video.modo === modo.valor"
                    @click="video.modo = modo.valor"
                >{{ modo.rotulo }}</button>
            </div>

            <EnvioDeVideo
                v-if="video.modo === 'arquivo'"
                :limites="midia.limites"
                :erro="video.errors.arquivo"
                @arquivo="video.arquivo = $event"
                @capa="video.capa = $event"
            />
            <div v-else>
                <TextInput v-model="video.link" placeholder="YouTube, Vimeo, Instagram ou TikTok" aria-label="Link do vídeo" />
                <InputError :message="video.errors.link" />
            </div>

            <PrimaryButton :disabled="video.processing">
                {{ video.progress ? `Enviando ${video.progress.percentage}%` : 'Adicionar vídeo' }}
            </PrimaryButton>
        </form>

        <form v-if="midia.podeVideos && midia.biblioteca.length" class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="vincular">
            <div class="min-w-0 flex-1">
                <InputLabel value="Ou ligue um vídeo que já está na biblioteca" />
                <SelectComBusca rotulo="Ou ligue um vídeo que já está na biblioteca" v-model="escolhaDaBiblioteca.video_id" :opcoes="midia.biblioteca" opcao-vazia="Vídeos sem show…" />
                <InputError :message="escolhaDaBiblioteca.errors.video_id" />
            </div>
            <SecondaryButton type="submit" :disabled="!escolhaDaBiblioteca.video_id || escolhaDaBiblioteca.processing">
                <Link2 class="h-4 w-4" aria-hidden="true" />
                Ligar
            </SecondaryButton>
        </form>
    </section>
</template>
