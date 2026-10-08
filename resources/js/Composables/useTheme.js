import { computed, ref } from 'vue';

const CHAVE = 'banda-tema';

function lerSalvo() {
    try {
        const valor = localStorage.getItem(CHAVE);

        return ['light', 'dark', 'system'].includes(valor) ? valor : 'system';
    } catch {

        return 'system';
    }
}

function ficaEscuro(tema) {
    return tema === 'dark'
        || (tema === 'system'
            && typeof matchMedia !== 'undefined'
            && matchMedia('(prefers-color-scheme: dark)').matches);
}

const tema = ref(typeof document === 'undefined' ? 'system' : lerSalvo());

if (typeof matchMedia !== 'undefined') {
    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (tema.value === 'system') {
            document.documentElement.classList.toggle('dark', ficaEscuro('system'));
        }
    });
}

export function useTheme() {
    function definirTema(valor) {
        tema.value = valor;

        try {
            localStorage.setItem(CHAVE, valor);
        } catch {

        }

        document.documentElement.classList.toggle('dark', ficaEscuro(valor));
    }

    return {
        tema: computed(() => tema.value),
        escuro: computed(() => ficaEscuro(tema.value)),
        definirTema,
        alternar: () => definirTema(ficaEscuro(tema.value) ? 'light' : 'dark'),
    };
}
