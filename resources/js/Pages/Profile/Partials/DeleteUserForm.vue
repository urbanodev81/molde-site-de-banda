<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmandoExclusao = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmarExclusao = () => {
    confirmandoExclusao.value = true;

    nextTick(() => passwordInput.value.focus());
};

const excluirConta = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => fecharModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const fecharModal = () => {
    confirmandoExclusao.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-fg">Excluir conta</h2>

            <p class="mt-1 text-sm text-fg-subtle">
                Excluir a conta apaga seu acesso em definitivo. O que você registrou no painel
                (shows, fotos, pedidos) continua com a banda; o que você perde é o acesso.
            </p>
        </header>

        <DangerButton @click="confirmarExclusao">Excluir minha conta</DangerButton>

        <Modal :show="confirmandoExclusao" @close="fecharModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-fg">
                    Tem certeza que quer excluir sua conta?
                </h2>

                <p class="mt-1 text-sm text-fg-subtle">
                    A exclusão é definitiva e não tem desfazer. Digite sua senha para confirmar.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Senha" class="sr-only" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Senha"
                        @keyup.enter="excluirConta"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="fecharModal">Cancelar</SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="excluirConta"
                    >
                        Excluir conta
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
