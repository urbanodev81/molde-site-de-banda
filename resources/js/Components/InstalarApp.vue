<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Download, RefreshCw, X } from 'lucide-vue-next';
import { aplicarVersaoNova, dispensarConvite, estadoPwa, instalar } from '@/pwa';

const pagina = usePage();

const autenticado = computed(() => Boolean(pagina.props.auth?.user));
</script>

<template>

    <div
        v-if="autenticado && (estadoPwa.podeInstalar || estadoPwa.temVersaoNova)"
        aria-live="polite"
        class="fixed inset-x-4 bottom-20 z-40 sm:inset-x-auto sm:bottom-5 sm:right-5 sm:w-80"
    >
        <div
            v-if="estadoPwa.temVersaoNova"
            class="rounded-card border border-line bg-surface p-4 shadow-lg"
        >
            <div class="flex items-start gap-3">
                <RefreshCw
                    class="mt-0.5 h-5 w-5 shrink-0 text-accent"
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-fg">
                        Versão nova disponível
                    </p>
                    <p class="mt-0.5 text-xs text-fg-subtle">
                        A tela que você está vendo é a anterior.
                    </p>
                    <button
                        type="button"
                        class="mt-3 w-full rounded-md bg-accent px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-accent"
                        @click="aplicarVersaoNova"
                    >
                        Atualizar agora
                    </button>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-card border border-line bg-surface p-4 shadow-lg"
        >
            <div class="flex items-start gap-3">
                <Download
                    class="mt-0.5 h-5 w-5 shrink-0 text-accent"
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-fg">
                        Instalar o painel
                    </p>
                    <p class="mt-0.5 text-xs text-fg-subtle">
                        Abre direto da tela inicial, sem passar pelo navegador.
                    </p>
                    <div class="mt-3 flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-md bg-accent px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-accent"
                            @click="instalar"
                        >
                            Instalar
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-2 text-xs font-semibold text-fg-subtle transition-colors hover:bg-surface-sunken"
                            @click="dispensarConvite"
                        >
                            Agora não
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="-mr-1 -mt-1 rounded-md p-1 text-fg-subtle transition-colors hover:text-fg dark:hover:text-white"
                    aria-label="Dispensar o convite de instalação"
                    @click="dispensarConvite"
                >
                    <X class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>
</template>
