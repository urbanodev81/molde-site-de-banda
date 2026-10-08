<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Send } from 'lucide-vue-next';
import Modal from './Modal.vue';
import InputLabel from './InputLabel.vue';
import InputError from './InputError.vue';
import TextInput from './TextInput.vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    ids: { type: Array, default: () => [] },
    rota: { type: String, required: true },

    nome: { type: Array, default: () => ['item', 'itens'] },

    explicacao: { type: String, default: '' },
});

const emit = defineEmits(['close', 'enviado']);

const envio = useForm({ ids: [], email: '', mensagem: '' });

watch(() => props.show, (aberto) => { if (aberto) envio.ids = [...props.ids]; });

function fechar() {
    envio.reset();
    envio.clearErrors();
    emit('close');
}

function enviar() {
    envio.post(props.rota, { preserveScroll: true, onSuccess: () => { emit('enviado'); fechar(); } });
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="fechar">
        <form class="grid gap-4 bg-surface p-6" @submit.prevent="enviar">
            <div>
                <h2 class="font-display text-xl font-bold uppercase text-fg">Enviar por e-mail</h2>
                <p class="mt-1 text-sm text-fg-muted">
                    {{ ids.length === 1 ? `1 ${nome[0]} marcad${nome[2] ?? 'a'}` : `${ids.length} ${nome[1]} marcad${nome[2] ?? 'a'}s` }}. {{ explicacao }}
                </p>
                <InputError :message="envio.errors.ids" />
            </div>

            <div>
                <InputLabel for="envio-email" value="E-mail de quem recebe" obrigatorio />
                <TextInput id="envio-email" v-model="envio.email" type="email" autocomplete="off" aria-required="true" />
                <InputError :message="envio.errors.email" />
            </div>

            <div>
                <InputLabel for="envio-mensagem" value="Recado (opcional)" />
                <textarea
                    id="envio-mensagem" v-model="envio.mensagem" rows="3" maxlength="500"
                    class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                />
                <InputError :message="envio.errors.mensagem" />
            </div>

            <div class="flex justify-end gap-2">
                <SecondaryButton type="button" @click="fechar">Cancelar</SecondaryButton>
                <PrimaryButton :disabled="envio.processing"><Send class="h-4 w-4" aria-hidden="true" />{{ envio.processing ? 'Enviando…' : 'Enviar' }}</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
