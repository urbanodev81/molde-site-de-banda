<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({ tiposEvento: { type: Array, default: () => [] }, origens: { type: Array, default: () => [] } });

const form = useForm({
    nome: '', email: '', telefone: '', tipo_evento: 'bar',
    data_pretendida: '', cidade: '', local: '', mensagem: '', origem: 'whatsapp',
});
</script>

<template>
    <Head title="Registrar pedido" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Contratação</p></template>

        <PageHeader
            titulo="Registrar pedido"
            descricao="Para o que chegou por fora do site: direct do Instagram, WhatsApp, indicação. Sem isto, o funil mostra um número que não é o real."
            :voltar-para="route('painel.contratacoes.index')"
            voltar-rotulo="Pedidos"
        />

        <form class="max-w-3xl space-y-6" @submit.prevent="form.post(route('painel.contratacoes.store'))">
            <section class="rounded-lg border border-line bg-surface p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><InputLabel value="Nome" obrigatorio /><TextInput v-model="form.nome" /><InputError :message="form.errors.nome" /></div>
                    <div>
                        <InputLabel value="Como chegou" obrigatorio />
                        <select v-model="form.origem" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring">
                            <option v-for="o in origens" :key="o.valor" :value="o.valor">{{ o.rotulo }}</option>
                        </select>
                        <InputError :message="form.errors.origem" />
                    </div>
                    <div><InputLabel value="Telefone" /><TextInput v-model="form.telefone" /><InputError :message="form.errors.telefone" /></div>
                    <div><InputLabel value="E-mail" /><TextInput v-model="form.email" type="email" /><InputError :message="form.errors.email" /></div>
                    <div>
                        <InputLabel value="Tipo de evento" obrigatorio />
                        <select v-model="form.tipo_evento" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring">
                            <option v-for="t in tiposEvento" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                        </select>
                    </div>
                    <div><InputLabel value="Data pretendida" /><TextInput v-model="form.data_pretendida" type="date" /></div>
                    <div><InputLabel value="Cidade" /><TextInput v-model="form.cidade" /></div>
                    <div><InputLabel value="Local" /><TextInput v-model="form.local" /></div>
                    <div class="sm:col-span-2">
                        <InputLabel value="O que pediram" />
                        <textarea v-model="form.mensagem" rows="3" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" />
                    </div>
                </div>

                <p class="mt-4 rounded-md bg-surface-sunken p-3 text-sm text-fg-muted">
                    Pedido registrado à mão não tem consentimento gravado — a tela do pedido mostra isso. Use o contato para responder ao que a pessoa pediu, não para lista de divulgação.
                </p>
            </section>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton :disabled="form.processing">Registrar</PrimaryButton>
                <Link :href="route('painel.contratacoes.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
