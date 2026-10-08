import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, Eye, EyeOff, Printer, Send } from 'lucide-vue-next';
import { useSelecaoEmLote } from './useSelecaoEmLote';

export function useListaEmLote({ slug, ids, podeGerenciar, arquivados, nome, liga = ['Publicar', 'Tirar do site'], envia = false }) {
    const selecao = useSelecaoEmLote(ids);
    const enviando = ref(false);
    const feminino = nome[2] === 'a';

    const acoes = computed(() => {
        const imprimir = { key: 'imprimir', label: 'Imprimir', icon: Printer };

        if (!podeGerenciar()) return [imprimir];

        if (arquivados()) return [{ key: 'restaurar', label: 'Restaurar', icon: ArchiveRestore }, imprimir];

        return [
            ...(liga ? [
                { key: 'ligar', label: liga[0], icon: Eye },
                { key: 'desligar', label: liga[1], icon: EyeOff },
            ] : []),
            {
                key: 'arquivar', label: 'Arquivar', icon: Archive,
                confirmar: `Arquivar ${feminino ? 'as' : 'os'} ${nome[1]} ${feminino ? 'marcadas' : 'marcados'}? ${feminino ? 'Elas ficam' : 'Eles ficam'} na aba de ${feminino ? 'arquivadas' : 'arquivados'}.`,
            },
            imprimir,
            ...(envia ? [{ key: 'enviar', label: 'Enviar por e-mail', icon: Send }] : []),
        ];
    });

    function emLote(acao, quais = selecao.selecionados.value) {
        if (acao === 'imprimir') {
            window.open(route(`painel.${slug}.imprimir`, { ids: quais }), '_blank', 'noopener');
            return;
        }

        if (acao === 'enviar') {
            enviando.value = true;
            return;
        }

        router.post(route(`painel.${slug}.lote`), { acao, ids: [...quais] }, { preserveScroll: true, onSuccess: selecao.limpar });
    }

    return { slug, selecao, acoes, emLote, enviando, nome, envia, ids, arquivados, podeGerenciar };
}
