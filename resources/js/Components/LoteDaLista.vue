<script setup>
import { computed } from 'vue';
import AbasDeArquivados from './AbasDeArquivados.vue';
import BarraDeAcoesEmLote from './BarraDeAcoesEmLote.vue';
import ModalEnviarLista from './ModalEnviarLista.vue';
import Checkbox from './Checkbox.vue';
import EstadoVazio from './EstadoVazio.vue';

const props = defineProps({

    lote: { type: Object, required: true },

    totais: { type: Array, default: () => [0, 0] },

    emUso: { type: String, default: 'Em uso' },

    explicacao: { type: String, default: 'Vai só o que está publicado no site; o resto fica de fora.' },
});

const feminino = computed(() => props.lote.nome[2] === 'a');
const naTela = computed(() => props.lote.ids().length);
const marcados = computed(() => props.lote.selecao.selecionados.value);
</script>

<template>
    <AbasDeArquivados
        :rota="`painel.${lote.slug}.index`"
        :aberta="lote.arquivados()"
        :rotulos="[emUso, feminino ? 'Arquivadas' : 'Arquivados']"
        :totais="totais"
        :descricao="`${lote.nome[1]} em uso ou ${feminino ? 'arquivadas' : 'arquivados'}`"
    />

    <label v-if="naTela" class="mb-3 inline-flex min-h-[44px] items-center gap-2 text-sm text-fg">
        <Checkbox :checked="lote.selecao.todosMarcados.value" @update:checked="lote.selecao.alternarTodos()" />
        Marcar {{ feminino ? 'as' : 'os' }} {{ naTela }} desta página
    </label>

    <BarraDeAcoesEmLote :quantidade="marcados.length" :acoes="lote.acoes.value" :nome="lote.nome" @acao="lote.emLote($event.key)" @limpar="lote.selecao.limpar()" />

    <EstadoVazio
        v-if="lote.arquivados() && !totais[1]"
        icone="archive"
        :titulo="feminino ? `Nenhuma ${lote.nome[0]} arquivada` : `Nenhum ${lote.nome[0]} arquivado`"
        :descricao="`O que é arquivado sai da lista e do site, e fica guardado aqui até alguém restaurar.`"
    />

    <ModalEnviarLista
        v-if="lote.envia"
        :show="lote.enviando.value"
        :ids="marcados"
        :rota="route(`painel.${lote.slug}.lote.enviar`)"
        :nome="lote.nome"
        :explicacao="explicacao"
        @enviado="lote.selecao.limpar()"
        @close="lote.enviando.value = false"
    />
</template>
