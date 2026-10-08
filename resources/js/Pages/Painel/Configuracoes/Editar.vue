<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    grupos: { type: Object, required: true },
    rotulos: { type: Object, required: true },
});

const valores = {};

for (const campos of Object.values(props.grupos)) {
    for (const campo of campos) {

        valores[campo.chave] = campo.tipo === 'booleano' ? campo.valor === '1' : (campo.valor ?? '');
    }
}

const form = useForm({ valores });
const abaAtiva = ref(Object.keys(props.grupos)[0]);
</script>

<template>
    <Head title="Configuração do site" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Administração</p></template>

        <PageHeader
            titulo="Configuração do site"
            descricao="É o que o site mostra em contato, redes, contratação e busca. Salvou, já está no ar."
        />

        <div class="mb-5 flex flex-wrap gap-2" role="tablist">
            <button
                v-for="(campos, grupo) in grupos"
                :key="grupo"
                type="button"
                role="tab"
                :aria-selected="abaAtiva === grupo"
                class="min-h-[44px] rounded-md border px-4 text-sm font-semibold transition"
                :class="abaAtiva === grupo ? 'border-line-strong bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:bg-surface-sunken'"
                @click="abaAtiva = grupo"
            >{{ rotulos[grupo] ?? grupo }}</button>
        </div>

        <form class="max-w-3xl" @submit.prevent="form.put(route('painel.configuracoes.update'), { preserveScroll: true })">
            <section
                v-for="(campos, grupo) in grupos"
                v-show="abaAtiva === grupo"
                :key="grupo"
                class="space-y-5 rounded-lg border border-line bg-surface p-5"
            >
                <div v-for="campo in campos" :key="campo.chave">
                    <label v-if="campo.tipo === 'booleano'" class="flex min-h-[44px] cursor-pointer items-center gap-3">
                        <input
                            v-model="form.valores[campo.chave]"
                            type="checkbox"
                            class="h-5 w-5 rounded border-line-input text-accent focus:ring-2 focus:ring-accent-ring"
                        >
                        <span class="text-sm font-semibold text-fg">{{ campo.rotulo }}</span>
                    </label>
                    <InputLabel v-else :value="campo.rotulo" />

                    <textarea
                        v-if="campo.tipo === 'texto_longo'"
                        v-model="form.valores[campo.chave]"
                        rows="4"
                        class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                    />
                    <TextInput
                        v-else-if="campo.tipo !== 'booleano'"
                        v-model="form.valores[campo.chave]"
                        :type="campo.tipo === 'email' ? 'email' : campo.tipo === 'url' ? 'url' : 'text'"
                    />

                    <p v-if="campo.ajuda" class="mt-1 text-sm text-fg-subtle">{{ campo.ajuda }}</p>
                    <InputError :message="form.errors[`valores.${campo.chave}`]" />
                </div>
            </section>

            <div class="mt-6"><PrimaryButton :disabled="form.processing">Salvar configuração</PrimaryButton></div>
        </form>
    </AuthenticatedLayout>
</template>
