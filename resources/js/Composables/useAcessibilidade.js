import { onMounted, ref, watch } from 'vue';

const FONTE_CLASSES = ['', 'a11y-fonte-1', 'a11y-fonte-2'];
const PREFIXO = 'banda-a11y-';

function lerNumero(chave, padrao = 0) {
    return Number(localStorage.getItem(PREFIXO + chave) ?? padrao);
}

function lerBooleano(chave) {
    return localStorage.getItem(PREFIXO + chave) === '1';
}

const fonteNivel = ref(lerNumero('fonte'));
const contrasteNivel = ref(lerNumero('contraste-nivel'));
const escalaCinza = ref(lerBooleano('escala-cinza'));
const sublinharLinks = ref(lerBooleano('sublinhar-links'));
const espacamentoTexto = ref(lerBooleano('espacamento-texto'));
const pausarAnimacoes = ref(lerBooleano('pausar-animacoes'));
const cursorGrande = ref(lerBooleano('cursor-grande'));
const guiaLeitura = ref(lerBooleano('guia-leitura'));

const CURSOR_GRANDE =
    `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 24 24'%3E%3Cpath d='M3 2l7.07 18 2.51-7.39L20 10.1z' fill='%23000' stroke='%23fff' stroke-width='1.5' stroke-linejoin='round'/%3E%3C/svg%3E") 2 2, auto`;

function aplicarFiltro() {
    const partes = [];

    if (contrasteNivel.value === 2) partes.push('contrast(1.4)', 'saturate(1.25)');
    else if (contrasteNivel.value === 1) partes.push('contrast(1.2)', 'saturate(1.1)');
    else if (contrasteNivel.value === -1) partes.push('contrast(0.9)', 'brightness(1.03)');
    else if (contrasteNivel.value === -2) partes.push('contrast(0.78)', 'brightness(1.06)');

    if (escalaCinza.value) partes.push('grayscale(1)');

    document.documentElement.style.filter = partes.join(' ');
}

function aplicar() {
    const html = document.documentElement;

    FONTE_CLASSES.forEach((classe, nivel) => classe && html.classList.toggle(classe, nivel === fonteNivel.value));
    aplicarFiltro();
    html.classList.toggle('a11y-sublinhar-links', sublinharLinks.value);
    html.classList.toggle('a11y-espacamento-texto', espacamentoTexto.value);
    html.classList.toggle('a11y-pausar-animacoes', pausarAnimacoes.value);
    html.style.cursor = cursorGrande.value ? CURSOR_GRANDE : '';
}

function persistir(chave, referencia) {
    watch(referencia, (valor) => {
        localStorage.setItem(PREFIXO + chave, typeof valor === 'boolean' ? (valor ? '1' : '0') : String(valor));
        aplicar();
    });
}

persistir('fonte', fonteNivel);
persistir('contraste-nivel', contrasteNivel);
persistir('escala-cinza', escalaCinza);
persistir('sublinhar-links', sublinharLinks);
persistir('espacamento-texto', espacamentoTexto);
persistir('pausar-animacoes', pausarAnimacoes);
persistir('cursor-grande', cursorGrande);

watch(guiaLeitura, (valor) => localStorage.setItem(PREFIXO + 'guia-leitura', valor ? '1' : '0'));

export { pausarAnimacoes };

export function useAcessibilidade() {
    onMounted(aplicar);

    return {
        fonteNivel,
        contrasteNivel,
        escalaCinza,
        sublinharLinks,
        espacamentoTexto,
        pausarAnimacoes,
        cursorGrande,
        guiaLeitura,

        aumentarFonte: () => (fonteNivel.value = Math.min(2, fonteNivel.value + 1)),
        diminuirFonte: () => (fonteNivel.value = Math.max(0, fonteNivel.value - 1)),
        aumentarContraste: () => (contrasteNivel.value = Math.min(2, contrasteNivel.value + 1)),
        diminuirContraste: () => (contrasteNivel.value = Math.max(-2, contrasteNivel.value - 1)),
        toggleEscalaCinza: () => (escalaCinza.value = !escalaCinza.value),
        toggleSublinharLinks: () => (sublinharLinks.value = !sublinharLinks.value),
        toggleEspacamentoTexto: () => (espacamentoTexto.value = !espacamentoTexto.value),
        togglePausarAnimacoes: () => (pausarAnimacoes.value = !pausarAnimacoes.value),
        toggleCursorGrande: () => (cursorGrande.value = !cursorGrande.value),
        toggleGuiaLeitura: () => (guiaLeitura.value = !guiaLeitura.value),

        resetar: () => {
            fonteNivel.value = 0;
            contrasteNivel.value = 0;
            escalaCinza.value = false;
            sublinharLinks.value = false;
            espacamentoTexto.value = false;
            pausarAnimacoes.value = false;
            cursorGrande.value = false;
            guiaLeitura.value = false;
        },
    };
}
