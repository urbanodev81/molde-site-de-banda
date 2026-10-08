<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>

    <div class="dark flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-10">
        <Link :href="route('site.home')" class="mb-8 text-center">
            <p class="font-display text-4xl font-black uppercase leading-none text-fg">
                A melhor banda
            </p>
            <p class="mt-2 font-label text-label uppercase tracking-[0.24em] text-accent">
                Painel de manutenção
            </p>
        </Link>

        <div class="w-full max-w-md rounded-lg border border-line bg-surface p-6 shadow-lg sm:p-8">
            <slot />
        </div>

        <p class="mt-8 text-center text-sm text-fg-subtle">
            <Link :href="route('site.home')" class="underline transition hover:text-accent">
                Voltar para o site
            </Link>
        </p>
    </div>
</template>
