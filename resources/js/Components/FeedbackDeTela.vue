<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { MessageSquareHeart, Star, X } from 'lucide-vue-next';

const pagina = usePage();

const aberto = ref(false);
const nota = ref(0);
const sobrevoo = ref(0);
const comentario = ref('');
const enviando = ref(false);

const CHAVE = 'banda:feedback-tela';
const DIAS_DE_SILENCIO = 30;

const tela = computed(() => route().current() ?? 'desconhecida');

function lerMemoria() {
    try {
        return JSON.parse(localStorage.getItem(CHAVE)) ?? {};
    } catch {
        return {};
    }
}

function calar() {
    try {
        localStorage.setItem(
            CHAVE,
            JSON.stringify({ ...lerMemoria(), [tela.value]: Date.now() }),
        );
    } catch {

    }
}

const jaRespondida = computed(() => {
    const quando = lerMemoria()[tela.value];

    return Boolean(quando) && Date.now() - quando < DIAS_DE_SILENCIO * 86400000;
});

const visivel = computed(() => Boolean(pagina.props.auth?.user) && !jaRespondida.value);

function dispensar() {
    calar();
    aberto.value = false;
}

function enviar() {
    if (!nota.value) return;

    enviando.value = true;

    router.post(
        route('feedback-de-tela'),
        {
            screen: tela.value,
            screen_label: document.title,
            rating: nota.value,

            comment: comentario.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                enviando.value = false;
                calar();
                aberto.value = false;
            },
        },
    );
}
</script>

<template>
    <div v-if="visivel" class="fixed bottom-4 right-4 z-30 print:hidden">
        <button
            v-if="!aberto"
            type="button"
            class="inline-flex items-center gap-2 rounded-full border border-line-input bg-surface px-3 py-2 text-xs font-medium text-fg-muted shadow-sm transition-colors hover:text-fg dark:hover:text-white"
            @click="aberto = true"
        >
            <MessageSquareHeart class="h-4 w-4" aria-hidden="true" />
            O que achou desta tela?
        </button>

        <div
            v-else
            class="w-72 rounded-card border border-line bg-surface p-4 shadow-lg"
        >
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-semibold text-fg">
                    O que achou desta tela?
                </p>
                <button
                    type="button"
                    class="-mr-1 -mt-1 rounded-md p-1 text-fg-subtle transition-colors hover:text-fg dark:hover:text-white"
                    aria-label="Dispensar a avaliação desta tela"
                    @click="dispensar"
                >
                    <X class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            <div class="mt-3 flex gap-1" @mouseleave="sobrevoo = 0">
                <button
                    v-for="n in 5"
                    :key="n"
                    type="button"
                    class="rounded p-0.5 transition-transform hover:scale-110"
                    :aria-label="`${n} de 5`"
                    :aria-pressed="nota === n"
                    @mouseenter="sobrevoo = n"
                    @click="nota = n"
                >
                    <Star
                        class="h-6 w-6"
                        :class="n <= (sobrevoo || nota) ? 'fill-amber-400 text-amber-500' : 'text-fg-muted'"
                        aria-hidden="true"
                    />
                </button>
            </div>

            <label class="mt-3 block">
                <span class="sr-only">Comentário (opcional)</span>
                <textarea
                    v-model="comentario"
                    rows="2"
                    maxlength="2000"
                    placeholder="Quer contar mais? (opcional)"
                    class="block w-full rounded-md border-line-input text-xs shadow-sm focus:border-line-strong focus:ring-accent-ring"
                />
            </label>

            <p class="mt-1 text-[10px] text-fg-subtle">
                Não inclua dados pessoais no comentário.
            </p>

            <button
                type="button"
                class="mt-3 w-full rounded-md bg-accent px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-accent disabled:opacity-50"
                :disabled="!nota || enviando"
                @click="enviar"
            >
                Enviar
            </button>
        </div>
    </div>
</template>
