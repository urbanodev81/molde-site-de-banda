import { computed, ref, watch } from 'vue';

export function useSelecaoEmLote(idsDaPagina) {
    const ids = computed(() => idsDaPagina() ?? []);
    const selecionados = ref([]);

    const todosMarcados = computed(() => ids.value.length > 0 && ids.value.every((id) => selecionados.value.includes(id)));

    watch(ids, () => { selecionados.value = []; });

    const marcado = (id) => selecionados.value.includes(id);
    const alternar = (id) => {
        selecionados.value = marcado(id) ? selecionados.value.filter((i) => i !== id) : [...selecionados.value, id];
    };
    const alternarTodos = () => { selecionados.value = todosMarcados.value ? [] : [...ids.value]; };
    const limpar = () => { selecionados.value = []; };

    return { selecionados, todosMarcados, marcado, alternar, alternarTodos, limpar };
}
