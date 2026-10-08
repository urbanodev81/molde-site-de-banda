<script setup>

import { computed, nextTick, ref, watch } from 'vue';
import { proximoId } from './ids';

const uid = proximoId();

const props = defineProps({
    tabs: { type: Array, required: true },
    modelValue: { type: String, default: null },

    errors: { type: Object, default: () => ({}) },

    ariaLabel: { type: String, default: 'Seções do formulário' },
});

const emit = defineEmits(['update:modelValue']);

const interna = ref(props.modelValue ?? props.tabs[0]?.key);

const ativa = computed({
    get: () => props.modelValue ?? interna.value,
    set: (valor) => {
        interna.value = valor;
        emit('update:modelValue', valor);
    },
});

const chavesComErro = computed(() => Object.keys(props.errors ?? {}));

const abaTemErro = (tab) => {
    if (! chavesComErro.value.length) return false;

    const campos = tab.fields ?? [];
    const prefixos = tab.prefix ? [].concat(tab.prefix) : [];

    return chavesComErro.value.some(
        (chave) => campos.includes(chave)
            || prefixos.some((p) => chave === p || chave.startsWith(`${p}.`)),
    );
};

watch(chavesComErro, (chaves) => {
    if (! chaves.length) return;

    const aberta = props.tabs.find((t) => t.key === ativa.value);
    if (aberta && abaTemErro(aberta)) return;

    const comErro = props.tabs.find(abaTemErro);
    if (comErro) ativa.value = comErro.key;
});

const focarAba = (indice) => {
    const total = props.tabs.length;
    const alvo = ((indice % total) + total) % total;

    ativa.value = props.tabs[alvo].key;
    nextTick(() => document.getElementById(`${uid}-aba-${props.tabs[alvo].key}`)?.focus());
};

const aoTeclar = (evento, indice) => {
    const destinos = {
        ArrowRight: indice + 1,
        ArrowLeft: indice - 1,
        Home: 0,
        End: props.tabs.length - 1,
    };

    if (! (evento.key in destinos)) return;

    evento.preventDefault();
    focarAba(destinos[evento.key]);
};

const iconeEhClasse = (icone) => typeof icone === 'string';
</script>

<template>
    <div class="bg-surface rounded-card border border-line overflow-hidden">
        <div
            role="tablist"
            :aria-label="ariaLabel"
            class="flex flex-wrap gap-1 p-1 bg-bg border-b border-line"
        >
            <button
                v-for="(tab, indice) in tabs"
                :id="`${uid}-aba-${tab.key}`"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="ativa === tab.key"
                :aria-controls="`${uid}-painel-${tab.key}`"
                :tabindex="ativa === tab.key ? 0 : -1"
                class="flex items-center gap-2 px-4 min-h-[44px] text-xs font-bold uppercase tracking-wide whitespace-nowrap rounded-xl transition-all cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                :class="ativa === tab.key ? 'bg-surface text-accent border border-line' : 'text-fg-muted dark:text-fg-subtle hover:text-fg hover:bg-surface/60'"
                @click="ativa = tab.key"
                @keydown="aoTeclar($event, indice)"
            >
                <template v-if="tab.icon">
                    <i
                        v-if="iconeEhClasse(tab.icon)"
                        class="mdi text-lg"
                        :class="[tab.icon, ativa === tab.key ? 'text-accent' : 'text-fg-subtle']"
                        aria-hidden="true"
                    ></i>
                    <component
                        :is="tab.icon"
                        v-else
                        class="h-4 w-4 shrink-0"
                        :class="ativa === tab.key ? 'text-accent' : 'text-fg-subtle'"
                        aria-hidden="true"
                    />
                </template>

                {{ tab.label }}

                <span
                    v-if="abaTemErro(tab)"
                    class="w-4 h-4 bg-red-600 rounded-full flex items-center justify-center shrink-0"
                >
                    <svg class="w-2.5 h-2.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 7v6" /><path d="M12 17h.01" />
                    </svg>
                    <span class="sr-only">contém campo com erro</span>
                </span>
            </button>
        </div>

        <template v-for="tab in tabs" :key="tab.key">
            <div
                v-if="ativa === tab.key"
                :id="`${uid}-painel-${tab.key}`"
                role="tabpanel"
                :aria-labelledby="`${uid}-aba-${tab.key}`"
                tabindex="0"
                class="p-6 focus:outline-none"
            >
                <slot :name="tab.key" />
            </div>
        </template>
    </div>
</template>
