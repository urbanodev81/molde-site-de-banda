<script setup>
import { nextTick, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { pausarAnimacoes } from '@/Composables/useAcessibilidade';
import { CATALOGO, alternar, paradas } from '@/Composables/useAnimacoesDoSite';

const props = defineProps({
    aberto: { type: Boolean, default: false },
});

const emit = defineEmits(['fechar']);

const dialogo = ref(null);

watch(
    () => props.aberto,
    async (aberto) => {
        await nextTick();

        if (aberto && !dialogo.value?.open) {
            dialogo.value?.showModal();
        } else if (!aberto && dialogo.value?.open) {
            dialogo.value.close();
        }
    },
);

function alternarTodas() {
    pausarAnimacoes.value = !pausarAnimacoes.value;
}

function cliqueNoVeu(evento) {
    if (evento.target === dialogo.value) {
        dialogo.value.close();
    }
}
</script>

<template>
    <dialog
        ref="dialogo"
        aria-labelledby="animacoes-titulo"
        aria-describedby="animacoes-ajuda"
        class="m-auto max-h-[85vh] w-[calc(100vw-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-fg shadow-xl backdrop:bg-black/75"
        @close="emit('fechar')"
        @click="cliqueNoVeu"
    >
        <div class="p-5">
            <div class="flex items-start justify-between gap-3">
                <h2 id="animacoes-titulo" class="text-lg font-bold">Quais animações parar</h2>
                <button
                    type="button"
                    aria-label="Fechar"
                    class="grid h-9 w-9 flex-none place-items-center rounded-lg text-fg-subtle hover:text-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                    @click="dialogo.close()"
                >
                    <X class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>
            <p id="animacoes-ajuda" class="mt-1 text-sm text-fg-subtle">
                A escolha fica guardada neste navegador e vale para o site inteiro.
            </p>

            <label
                class="mt-4 flex cursor-pointer items-center justify-between gap-3 rounded-xl border-2 border-accent bg-accent-subtle p-3.5"
            >
                <span>
                    <span class="block text-base font-bold">Parar todas as animações</span>
                    <span class="block text-xs text-fg-subtle">Nada no site se mexe sozinho.</span>
                </span>
                <input
                    type="checkbox"
                    :checked="pausarAnimacoes"
                    class="h-5 w-5 flex-none cursor-pointer accent-accent"
                    @change="alternarTodas"
                />
            </label>

            <p class="mt-4 text-xs font-bold uppercase tracking-wide text-fg-subtle">Ou escolha uma a uma</p>
            <p v-if="pausarAnimacoes" class="mt-1 text-xs text-fg-subtle">
                Com "parar todas" ligado, todas estão paradas. Desligue para escolher uma a uma.
            </p>

            <ul class="mt-2 flex list-none flex-col gap-1 pl-0">
                <li v-for="item in CATALOGO" :key="item.chave">
                    <label
                        class="flex items-start justify-between gap-3 rounded-xl p-2.5"
                        :class="pausarAnimacoes ? 'opacity-60' : 'cursor-pointer hover:bg-accent-subtle'"
                    >
                        <span>
                            <span class="block text-sm font-bold">{{ item.nome }}</span>
                            <span class="block text-xs text-fg-subtle">{{ item.descricao }}</span>
                        </span>
                        <input
                            type="checkbox"
                            :checked="pausarAnimacoes || paradas.includes(item.chave)"
                            :disabled="pausarAnimacoes"
                            class="mt-0.5 h-5 w-5 flex-none cursor-pointer accent-accent disabled:cursor-not-allowed"
                            @change="alternar(item.chave)"
                        />
                    </label>
                </li>
            </ul>

            <button
                type="button"
                class="mt-4 w-full rounded-xl bg-accent px-4 py-3 text-sm font-bold text-fg-on-accent hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring focus-visible:ring-offset-2"
                @click="dialogo.close()"
            >
                Pronto
            </button>
        </div>
    </dialog>
</template>
