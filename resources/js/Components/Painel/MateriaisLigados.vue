<script setup>

import { Link } from '@inertiajs/vue3';
import { Download, Globe, Lock, ShieldCheck } from 'lucide-vue-next';

defineProps({
    materiais: { type: Array, default: () => [] },
    titulo: { type: String, default: 'Materiais ligados' },
});
</script>

<template>
    <section v-if="materiais.length" class="rounded-lg border border-line bg-surface p-5">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-display text-xl font-bold uppercase text-fg">{{ titulo }}</h2>
            <Link :href="route('painel.materiais.index')" class="inline-flex min-h-[44px] items-center text-sm font-semibold text-accent hover:underline">Abrir Materiais</Link>
        </div>
        <ul class="divide-y divide-line">
            <li v-for="material in materiais" :key="material.uuid" class="flex flex-wrap items-center gap-x-4 gap-y-1 py-2">
                <div class="min-w-0 flex-1">
                    <p class="break-words font-semibold text-fg">{{ material.titulo }}</p>
                    <p class="text-sm text-fg-muted">{{ material.tipoRotulo }}<template v-if="material.tamanho"> · {{ material.tamanho }}</template></p>
                </div>
                <span class="inline-flex items-center gap-1.5 font-label text-label uppercase" :class="material.noSite ? 'text-success' : 'text-fg-subtle'">
                    <component :is="material.privado ? ShieldCheck : material.noSite ? Globe : Lock" class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ material.privado ? 'Privado' : material.noSite ? 'Público' : 'Só no painel' }}
                </span>
                <a :href="material.url" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-accent hover:underline">
                    <Download class="h-4 w-4" aria-hidden="true" />Baixar
                </a>
            </li>
        </ul>
    </section>
</template>
