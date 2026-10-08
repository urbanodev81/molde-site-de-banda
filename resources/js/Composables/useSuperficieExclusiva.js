import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const superficieAberta = ref(null);

router.on('navigate', () => {
    superficieAberta.value = null;
});

export function useSuperficieExclusiva() {
    function reivindicar(nome) {
        superficieAberta.value = nome;
    }

    function liberar(nome) {
        if (superficieAberta.value === nome) {
            superficieAberta.value = null;
        }
    }

    return { superficieAberta, reivindicar, liberar };
}
