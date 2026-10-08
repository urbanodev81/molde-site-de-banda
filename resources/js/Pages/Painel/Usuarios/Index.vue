<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import LoteDaLista from '@/Components/LoteDaLista.vue';
import CaixaDeLote from '@/Components/CaixaDeLote.vue';
import BotaoRestaurar from '@/Components/BotaoRestaurar.vue';
import { useListaEmLote } from '@/Composables/useListaEmLote';
import { Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    usuarios: { type: Array, default: () => [] },

    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
    podeGerenciar: { type: Boolean, default: false },
});

const lote = useListaEmLote({
    slug: 'usuarios',
    ids: () => props.usuarios.filter((u) => !u.euMesmo).map((u) => u.uuid),
    podeGerenciar: () => props.podeGerenciar,
    arquivados: () => props.arquivados,
    nome: ['conta', 'contas', 'a'],
    liga: ['Ativar', 'Desativar'],
});
</script>

<template>
    <Head title="Contas de acesso" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Administração</p></template>

        <PageHeader
            titulo="Contas de acesso"
            descricao="Não existe auto-cadastro: conta aqui nasce criada por quem já está dentro."
        >
            <template #acoes>
                <Link v-if="podeGerenciar" :href="route('painel.usuarios.create')">
                    <PrimaryButton type="button"><Plus class="h-4 w-4" aria-hidden="true" />Nova conta</PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <LoteDaLista :lote="lote" :totais="totais" em-uso="Com acesso" />

        <div v-if="usuarios.length" class="overflow-hidden rounded-lg border border-line bg-surface">
            <ul class="divide-y divide-line">
                <li v-for="usuario in usuarios" :key="usuario.uuid" class="flex flex-wrap items-center gap-4 px-4 py-4 sm:px-5">
                    <CaixaDeLote v-if="!usuario.euMesmo" :lote="lote" :id="usuario.uuid" :rotulo="usuario.nome" />
                    <span v-else class="h-11 w-11 shrink-0" aria-hidden="true" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-fg">
                            {{ usuario.nome }}
                            <span v-if="usuario.euMesmo" class="font-normal text-fg-subtle">(você)</span>
                        </p>
                        <p class="truncate text-sm text-fg-muted">{{ usuario.email }}</p>
                        <p class="text-xs text-fg-subtle">
                            {{ usuario.ultimoAcesso ? `Último acesso em ${usuario.ultimoAcesso}` : 'Nunca entrou' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Chip v-for="perfil in usuario.perfis" :key="perfil" :rotulo="perfil" token="info" />
                        <Chip v-if="!usuario.ativo" rotulo="Inativa" token="danger" icone="circle-slash" />
                    </div>

                    <div v-if="podeGerenciar" class="flex flex-wrap gap-2">
                        <template v-if="!arquivados">
                            <Link :href="route('painel.usuarios.edit', usuario.uuid)"><SecondaryButton type="button">Editar</SecondaryButton></Link>
                            <TwoStepConfirm v-if="!usuario.euMesmo" rotulo="Arquivar" verbo="Arquivar" :nome="usuario.nome" @confirmar="router.delete(route('painel.usuarios.destroy', usuario.uuid))" />
                        </template>
                        <BotaoRestaurar v-else :lote="lote" :id="usuario.uuid" />
                    </div>
                </li>
            </ul>
        </div>
    </AuthenticatedLayout>
</template>
