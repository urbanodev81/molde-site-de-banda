<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import * as icones from 'lucide-vue-next';
import { ShieldCheck, ShieldAlert, MessageSquarePlus } from 'lucide-vue-next';
import MateriaisLigados from '@/Components/Painel/MateriaisLigados.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Chip from '@/Components/Chip.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    pedido: { type: Object, required: true },
    materiais: { type: Array, default: () => [] },
    interacoes: { type: Array, default: () => [] },
    status: { type: Array, default: () => [] },
    tiposEvento: { type: Array, default: () => [] },
    tiposInteracao: { type: Array, default: () => [] },
    responsaveis: { type: Array, default: () => [] },
    podeVerValores: { type: Boolean, default: false },
    podeGerenciar: { type: Boolean, default: false },
});

const form = useForm({
    nome: props.pedido.nome,
    email: props.pedido.email ?? '',
    telefone: props.pedido.telefone ?? '',
    tipo_evento: props.pedido.tipoEvento,
    data_pretendida: props.pedido.dataPretendida ?? '',
    cidade: props.pedido.cidade ?? '',
    local: props.pedido.local ?? '',
    status: props.pedido.status,
    motivo_perda: props.pedido.motivoPerda ?? '',
    responsavel_id: props.pedido.responsavelId ?? '',
    show_id: props.pedido.showId ?? '',
    valor_proposto: props.pedido.valor ?? '',
});

const nota = useForm({ tipo: 'nota', descricao: '' });

const iconeDe = (nome) => {
    const chave = (nome ?? 'sticky-note').split('-').map((p) => p[0].toUpperCase() + p.slice(1)).join('');

    return icones[chave] ?? icones.StickyNote;
};
</script>

<template>
    <Head :title="pedido.nome" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Contratação</p></template>

        <PageHeader :titulo="pedido.nome" :voltar-para="route('painel.contratacoes.index')" voltar-rotulo="Pedidos">
            <template #acoes>
                <a v-if="pedido.telefone" :href="`https://wa.me/${pedido.telefone.replace(/\D/g, '')}`" target="_blank" rel="noopener">
                    <PrimaryButton type="button">Responder no WhatsApp</PrimaryButton>
                </a>
                <TwoStepConfirm v-if="podeGerenciar" :nome="pedido.nome" @confirmar="router.delete(route('painel.contratacoes.destroy', pedido.uuid))" />
            </template>
        </PageHeader>

        <div class="mb-6 flex flex-wrap items-center gap-3">
            <Chip :rotulo="pedido.statusRotulo" :token="pedido.statusToken" />
            <Chip :rotulo="`via ${pedido.origemRotulo}`" token="info" />
            <span class="text-sm text-fg-subtle [font-variant-numeric:tabular-nums]">
                Chegou em {{ pedido.criadoEm }} · {{ pedido.diasEsperando === 0 ? 'hoje' : `há ${pedido.diasEsperando} dia(s)` }}
            </span>
        </div>

        <div
            class="mb-6 flex items-start gap-3 rounded-lg border p-4"
            :class="pedido.consentimento ? 'border-success bg-success-subtle' : 'border-warning bg-warning-subtle'"
        >
            <component :is="pedido.consentimento ? ShieldCheck : ShieldAlert" class="mt-0.5 h-5 w-5 shrink-0" :class="pedido.consentimento ? 'text-success' : 'text-warning'" aria-hidden="true" />
            <p class="text-sm" :class="pedido.consentimento ? 'text-success' : 'text-warning'">
                <template v-if="pedido.consentimento">
                    Consentimento registrado no formulário do site em {{ pedido.consentimento }}.
                </template>
                <template v-else>
                    Sem consentimento registrado — este pedido foi cadastrado à mão. O contato pode ser usado para responder ao que a pessoa pediu, não para lista de divulgação.
                </template>
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <form class="space-y-6 lg:col-span-2" @submit.prevent="form.put(route('painel.contratacoes.update', pedido.uuid), { preserveScroll: true })">
                <section class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">O pedido</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><InputLabel value="Nome" obrigatorio /><TextInput v-model="form.nome" /><InputError :message="form.errors.nome" /></div>
                        <div><InputLabel value="Telefone" /><TextInput v-model="form.telefone" /><InputError :message="form.errors.telefone" /></div>
                        <div><InputLabel value="E-mail" /><TextInput v-model="form.email" type="email" /><InputError :message="form.errors.email" /></div>
                        <div>
                            <InputLabel value="Tipo de evento" />
                            <select v-model="form.tipo_evento" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring">
                                <option v-for="t in tiposEvento" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                            </select>
                        </div>
                        <div><InputLabel value="Data pretendida" /><TextInput v-model="form.data_pretendida" type="date" /></div>
                        <div><InputLabel value="Cidade" /><TextInput v-model="form.cidade" /></div>
                        <div class="sm:col-span-2"><InputLabel value="Local" /><TextInput v-model="form.local" /></div>
                    </div>

                    <div v-if="pedido.mensagem" class="mt-4 rounded-md bg-surface-sunken p-4">
                        <p class="font-label text-label uppercase text-fg-subtle">O que escreveram</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-fg">{{ pedido.mensagem }}</p>
                    </div>
                </section>

                <section class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Andamento</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Etapa" />
                            <select v-model="form.status" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring">
                                <option v-for="s in status" :key="s.valor" :value="s.valor">{{ s.rotulo }}</option>
                            </select>
                            <p class="mt-1 text-xs text-fg-subtle">Mudar a etapa gera uma linha no histórico automaticamente.</p>
                        </div>
                        <div>
                            <InputLabel value="Responsável" />
                            <SelectComBusca rotulo="Responsável" v-model="form.responsavel_id" :opcoes="responsaveis" opcao-vazia="Ninguém ainda" />
                        </div>
                        <div v-if="podeVerValores">
                            <InputLabel value="Valor proposto (R$)" />
                            <TextInput v-model="form.valor_proposto" type="number" step="0.01" min="0" />
                            <InputError :message="form.errors.valor_proposto" />
                        </div>
                        <div v-if="form.status === 'perdido'">
                            <InputLabel value="Por que não fechou" />
                            <TextInput v-model="form.motivo_perda" placeholder="preço, data ocupada, escolheu outra banda" />
                        </div>
                    </div>

                    <div class="mt-4"><PrimaryButton :disabled="form.processing">Salvar</PrimaryButton></div>
                </section>

                <MateriaisLigados :materiais="materiais" titulo="Documentos do pedido" />
            </form>

            <section class="rounded-lg border border-line bg-surface">
                <header class="border-b border-line px-5 py-4">
                    <h2 class="font-display text-xl font-bold uppercase text-fg">Histórico</h2>
                    <p class="mt-1 text-sm text-fg-muted">Num grupo de três, ninguém lembra quem já respondeu.</p>
                </header>

                <form v-if="podeGerenciar" class="space-y-3 border-b border-line p-5" @submit.prevent="nota.post(route('painel.contratacoes.interacoes.store', pedido.uuid), { preserveScroll: true, onSuccess: () => nota.reset() })">
                    <select v-model="nota.tipo" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" aria-label="Tipo de registro">
                        <option v-for="t in tiposInteracao" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                    </select>
                    <textarea v-model="nota.descricao" rows="3" class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" placeholder="O que foi conversado" aria-label="Descrição" />
                    <InputError :message="nota.errors.descricao" />
                    <PrimaryButton :disabled="nota.processing"><MessageSquarePlus class="h-4 w-4" aria-hidden="true" />Registrar</PrimaryButton>
                </form>

                <ol class="divide-y divide-line">
                    <li v-for="item in interacoes" :key="item.id" class="flex gap-3 px-5 py-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-surface-sunken text-fg-subtle">
                            <component :is="iconeDe(item.tipoIcone)" class="h-4 w-4" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm text-fg">{{ item.descricao }}</p>
                            <p class="mt-1 text-xs text-fg-subtle">{{ item.tipoRotulo }} · {{ item.autor }} · {{ item.quando }}</p>
                        </div>
                    </li>
                </ol>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
