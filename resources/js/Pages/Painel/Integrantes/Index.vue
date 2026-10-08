<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { Plus, EyeOff } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    integrantes: { type: Array, default: () => [] },
    podeGerenciar: { type: Boolean, default: false },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
});

const lote = useListaEmLote({
    slug: 'integrantes',
    ids: () => props.integrantes.map((p) => p.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['integrante', 'integrantes', 'a'],
    liga: ['Ativar', 'Desativar'],
});
</script>

<template>
    <Head title="Integrantes" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">O site</p></template>

        <PageHeader
            titulo="Integrantes"
            descricao="Nome e rosto no site exigem autorização de uso de imagem registrada. Sem ela, a integrante fica só aqui dentro."
        >
            <template #acoes>
                <Link v-if="podeGerenciar" :href="route('painel.integrantes.create')">
                    <PrimaryButton type="button"><Plus class="h-4 w-4" aria-hidden="true" />Nova integrante</PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Cadastradas" />

        <div v-if="integrantes.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="pessoa in integrantes" :key="pessoa.uuid" class="min-w-0 rounded-lg border border-line bg-surface p-5">
                <div class="flex items-start gap-4">
                    <CaixaDeLote :lote="lote" :id="pessoa.uuid" :rotulo="pessoa.comoAparece" />
                    <img
                        v-if="pessoa.recorte || pessoa.foto"
                        :src="pessoa.recorte || pessoa.foto"
                        :alt="`Foto de ${pessoa.comoAparece}`"
                        class="h-16 w-16 shrink-0 rounded-full border border-line object-cover"
                    />
                    <span v-else class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-surface-sunken font-display text-2xl font-bold text-fg-subtle">
                        {{ pessoa.comoAparece.charAt(0) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-display text-xl font-bold uppercase text-fg">{{ pessoa.comoAparece }}</h2>
                        <p class="truncate text-sm text-fg-muted">{{ pessoa.instrumento || 'Instrumento a definir' }}</p>
                        <p class="font-label text-label uppercase text-fg-subtle">Ordem no palco: {{ pessoa.ordem }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Chip
                        :rotulo="pessoa.autorizada ? `Autorizada em ${pessoa.autorizadaEm}` : 'Sem autorização'"
                        :token="pessoa.autorizada ? 'success' : 'danger'"
                        :icone="pessoa.autorizada ? 'shield-check' : 'shield-alert'"
                    />
                    <Chip v-if="!pessoa.ativa" rotulo="Inativa" token="fg-subtle" icone="circle-slash" />
                </div>

                <div v-if="pessoa.motivos.length" class="mt-3 flex items-start gap-2 rounded-md bg-warning-subtle p-3 text-sm text-warning">
                    <EyeOff class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    <span>Não aparece no site: {{ pessoa.motivos.join('; ') }}.</span>
                </div>

                <div v-if="podeGerenciar" class="mt-4 flex flex-wrap gap-2">
                    <template v-if="!arquivados">
                        <Link :href="route('painel.integrantes.edit', pessoa.uuid)"><SecondaryButton type="button">Editar</SecondaryButton></Link>
                        <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="pessoa.comoAparece" @confirmar="router.delete(route('painel.integrantes.destroy', pessoa.uuid))" />
                    </template>
                    <BotaoRestaurar v-else :lote="lote" :id="pessoa.uuid" />
                </div>
            </article>
        </div>

        <EstadoVazio v-else-if="!arquivados" icone="users" titulo="Nenhuma integrante cadastrada" descricao="Sem elas, o palco da home fica vazio.">
            <Link v-if="podeGerenciar" :href="route('painel.integrantes.create')">
                <PrimaryButton type="button">Cadastrar integrante</PrimaryButton>
            </Link>
        </EstadoVazio>
    </AuthenticatedLayout>
</template>
