<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { Bug, Send } from 'lucide-vue-next';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const pagina = usePage();

const autenticado = computed(() => Boolean(pagina.props.auth?.user));

const aberto = ref(false);
const enviado = ref(false);

const formulario = useForm({
    title: '',
    description: '',
    severity: 'medium',
    page_url: '',
});

const gravidades = [
    { valor: 'low', rotulo: 'Incomoda', ajuda: 'Dá para trabalhar assim' },
    { valor: 'medium', rotulo: 'Atrapalha', ajuda: 'Dá trabalho contornar' },
    { valor: 'high', rotulo: 'Trava', ajuda: 'Não consigo seguir nesta tela' },
    { valor: 'critical', rotulo: 'Para tudo', ajuda: 'O sistema ficou inutilizável' },
];

function abrir() {

    formulario.page_url = window.location.href;
    enviado.value = false;
    aberto.value = true;
}

function fechar() {
    aberto.value = false;
    formulario.clearErrors();
}

function enviar() {
    formulario.post(route('reportar-erro'), {
        preserveScroll: true,
        onSuccess: () => {

            if (pagina.props.flash?.erro) return;

            enviado.value = true;
            formulario.reset('title', 'description');
            formulario.severity = 'medium';
        },
    });
}
</script>

<template>
    <template v-if="autenticado">
        <button
            type="button"
            aria-label="Relatar um problema nesta tela"
            class="fixed bottom-4 right-4 z-50 grid h-12 w-12 place-items-center rounded-full border border-line bg-surface text-fg-muted shadow-lg transition hover:bg-bg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring focus-visible:ring-offset-2 dark:ring-offset-gray-950"
            @click="abrir"
        >
            <Bug class="h-5 w-5" aria-hidden="true" />
        </button>

        <Modal :show="aberto" max-width="lg" @close="fechar">
            <div class="p-6">
                <h2 class="text-lg font-bold text-fg">Relatar um problema</h2>
                <p class="mt-1 text-sm text-fg-subtle">
                    Vai direto para a fila da equipe. O endereço desta tela e o seu nome vão junto — não
                    precisa escrever.
                </p>

                <div
                    v-if="enviado"
                    class="mt-5 rounded-xl border border-sla-seguro/30 bg-sla-seguro/10 p-4 text-sm text-fg-muted"
                >
                    <p class="font-bold">Relato enviado. Obrigado.</p>
                    <p class="mt-1">
                        A equipe já recebeu, com a tela e o horário. Se acontecer de novo em outro lugar,
                        relate de lá também — cada tela ajuda.
                    </p>
                    <div class="mt-4 flex justify-end gap-3">
                        <SecondaryButton @click="fechar">Fechar</SecondaryButton>
                        <PrimaryButton @click="abrir">Relatar outro</PrimaryButton>
                    </div>
                </div>

                <form v-else class="mt-5 space-y-4" @submit.prevent="enviar">
                    <div v-if="pagina.props.flash?.erro" class="rounded-xl border border-sla-estourado/30 bg-sla-estourado/10 p-3 text-sm text-fg-muted">
                        {{ pagina.props.flash.erro }}
                    </div>

                    <div>
                        <InputLabel for="relato-titulo" value="O que aconteceu?" />
                        <TextInput
                            id="relato-titulo"
                            v-model="formulario.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ex.: o botão de salvar não faz nada"
                            required
                            autofocus
                        />
                        <InputError :message="formulario.errors.title" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="relato-descricao" value="O que você estava fazendo (opcional)" />
                        <textarea
                            id="relato-descricao"
                            v-model="formulario.description"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-line-input shadow-sm focus:border-line-strong focus:ring-accent-ring"
                            placeholder="Preenchi os campos, cliquei em salvar e a tela ficou parada."
                        ></textarea>
                        <p class="mt-1 text-xs text-fg-subtle">
                            O passo a passo é o que mais ajuda: o que você fez antes de dar errado.
                        </p>
                        <InputError :message="formulario.errors.description" class="mt-1" />
                    </div>

                    <fieldset>
                        <legend class="text-sm font-medium text-fg-muted">
                            O quanto atrapalha?
                        </legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="gravidade in gravidades"
                                :key="gravidade.valor"
                                class="flex cursor-pointer items-start gap-2 rounded-xl border p-2.5 transition"
                                :class="formulario.severity === gravidade.valor ? 'border-line-strong bg-accent-subtle' : 'border-line hover:bg-bg'"
                            >
                                <input
                                    v-model="formulario.severity"
                                    type="radio"
                                    :value="gravidade.valor"
                                    class="mt-0.5 border-line-input text-accent focus:ring-accent-ring"
                                />
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-fg">
                                        {{ gravidade.rotulo }}
                                    </span>
                                    <span class="block text-xs text-fg-subtle">
                                        {{ gravidade.ajuda }}
                                    </span>
                                </span>
                            </label>
                        </div>
                        <InputError :message="formulario.errors.severity" class="mt-1" />
                    </fieldset>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <SecondaryButton type="button" @click="fechar">Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="formulario.processing || ! formulario.title">
                            <Send class="mr-2 h-4 w-4" aria-hidden="true" />
                            {{ formulario.processing ? 'Enviando…' : 'Enviar relato' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </template>
</template>
