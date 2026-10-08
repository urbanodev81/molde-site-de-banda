import { ref, watch } from 'vue';
import { pausarAnimacoes } from './useAcessibilidade';

export const CHAVE = 'banda.animacoes-paradas';

export const CATALOGO = [
    {
        chave: 'abertura',
        nome: 'Abertura do palco',
        descricao: 'O pano subindo, o letreiro acendendo e a banda entrando em cena, na página inicial.',
    },
    {
        chave: 'cortina',
        nome: 'Transição entre páginas',
        descricao: 'A cortina que fecha e abre a cada troca de página.',
    },
    {
        chave: 'faixa',
        nome: 'Faixa de estilos',
        descricao: 'A faixa rosa com os estilos musicais, que corre sem parar.',
    },
    {
        chave: 'enfeites',
        nome: 'Enfeites balançando',
        descricao: 'A estrela e o raio do palco, e o anel pulsando no botão de som.',
    },
    {
        chave: 'rolar',
        nome: 'Efeitos ao rolar a página',
        descricao: 'Blocos que surgem conforme você desce e a arte do palco se deslocando.',
    },
    {
        chave: 'rolagem',
        nome: 'Rolagem animada',
        descricao: 'O deslizar até a seção ao clicar num link do menu. Parada, a página salta direto.',
    },
];

function ler() {
    try {
        const lista = JSON.parse(localStorage.getItem(CHAVE) || '[]');

        return Array.isArray(lista) ? lista.filter((c) => CATALOGO.some((item) => item.chave === c)) : [];
    } catch {
        return [];
    }
}

export const paradas = ref(ler());

function aplicar() {
    const html = document.documentElement;

    CATALOGO.forEach(({ chave }) => html.classList.toggle(`sem-anim-${chave}`, paradas.value.includes(chave)));
}

watch(paradas, (lista) => {
    try {
        localStorage.setItem(CHAVE, JSON.stringify(lista));
    } catch {

    }

    aplicar();
});

aplicar();

export function parada(chave) {
    return pausarAnimacoes.value || paradas.value.includes(chave);
}

export function alternar(chave) {
    paradas.value = paradas.value.includes(chave)
        ? paradas.value.filter((c) => c !== chave)
        : [...paradas.value, chave];
}
