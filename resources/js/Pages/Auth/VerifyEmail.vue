<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: String },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const linkEnviado = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar e-mail" />

        <h1 class="text-lg font-semibold text-fg">Confirme seu e-mail</h1>
        <p class="mt-1 text-sm text-fg-subtle">
            Antes de continuar, confirme seu endereço clicando no link que acabamos de enviar. Se
            não recebeu, podemos enviar outro.
        </p>

        <div
            v-if="linkEnviado"
            class="mt-4 rounded-md border border-sla-seguro/30 bg-sla-seguro/10 px-3 py-2 text-sm font-medium text-sla-seguro"
        >
            Um novo link de confirmação foi enviado para o seu e-mail.
        </div>

        <form class="mt-6" @submit.prevent="submit">
            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Reenviar e-mail de confirmação
            </PrimaryButton>
        </form>

        <div class="mt-4 text-center">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="rounded-md text-sm text-fg-muted underline hover:text-fg dark:"
            >
                Sair
            </Link>
        </div>
    </GuestLayout>
</template>
