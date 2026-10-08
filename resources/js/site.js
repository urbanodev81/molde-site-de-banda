import { createApp, h, ref, watch } from 'vue';

if (document.querySelector('altcha-widget')) {

    import('altcha').then(() => import('altcha/i18n/pt-br'));
}
import AcessibilidadeWidget from './Components/AcessibilidadeWidget.vue';
import AnimacoesModal from './Components/AnimacoesModal.vue';
import { pausarAnimacoes } from './Composables/useAcessibilidade';
import { parada, paradas } from './Composables/useAnimacoesDoSite';
import VLibrasWidget from './Components/VLibrasWidget.vue';

const menosMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const ancora = document.createElement('div');
ancora.id = 'acessibilidade';
document.body.appendChild(ancora);

const modalDeAnimacoes = ref(false);

createApp({
    render: () => [
        h(AcessibilidadeWidget, { escolherAnimacoes: () => { modalDeAnimacoes.value = true; } }),
        h(AnimacoesModal, { aberto: modalDeAnimacoes.value, onFechar: () => { modalDeAnimacoes.value = false; } }),
        h(VLibrasWidget),
    ],
}).mount(ancora);

const portasDeAnimacoes = document.querySelectorAll('[data-abre-animacoes]');
const botaoAnimacoesTopo = document.getElementById('animacoes-topo');

portasDeAnimacoes.forEach((porta) => {
    porta.hidden = false;
    porta.addEventListener('click', () => {
        if (porta.closest('.gaveta.aberto')) {
            document.getElementById('hamburguer')?.click();
        }

        modalDeAnimacoes.value = true;
    });
});

const marcarBotaoDoTopo = () => {
    if (!botaoAnimacoesTopo) {
        return;
    }

    const alguma = pausarAnimacoes.value || paradas.value.length > 0;
    botaoAnimacoesTopo.dataset.ativo = alguma ? '1' : '0';
    botaoAnimacoesTopo.setAttribute('aria-label', alguma ? 'Parar animações (há animações paradas)' : 'Parar animações');
};

marcarBotaoDoTopo();

watch([pausarAnimacoes, paradas], () => {
    document.documentElement.classList.add('abertura-encerrada');
    marcarBotaoDoTopo();
});

const botaoTema = document.getElementById('tema');

if (botaoTema) {
    const raiz = document.documentElement;

    const vestir = (claro) => {
        if (claro) {
            raiz.dataset.tema = 'claro';
        } else {
            delete raiz.dataset.tema;
        }

        botaoTema.setAttribute('aria-pressed', String(claro));

        botaoTema.setAttribute('aria-label', claro ? 'Usar o tema escuro' : 'Usar o tema claro');
    };

    vestir(raiz.dataset.tema === 'claro');
    botaoTema.hidden = false;

    botaoTema.addEventListener('click', () => {
        const claro = raiz.dataset.tema !== 'claro';

        vestir(claro);

        try {
            localStorage.setItem('banda.tema', claro ? 'claro' : 'escuro');
        } catch {

        }
    });
}

const hamburguer = document.getElementById('hamburguer');
const gaveta = document.getElementById('gaveta');
const veu = document.getElementById('veu');

if (hamburguer && gaveta && veu) {
    const fechar = () => {
        gaveta.classList.remove('aberto');
        veu.classList.remove('aberto');
        veu.hidden = true;
        gaveta.setAttribute('aria-hidden', 'true');
        hamburguer.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-aberto');
        hamburguer.focus();
    };

    const abrir = () => {
        veu.hidden = false;

        requestAnimationFrame(() => veu.classList.add('aberto'));
        gaveta.classList.add('aberto');
        gaveta.setAttribute('aria-hidden', 'false');
        hamburguer.setAttribute('aria-expanded', 'true');
        document.body.classList.add('menu-aberto');
        gaveta.querySelector('a')?.focus();
    };

    hamburguer.addEventListener('click', () =>
        gaveta.classList.contains('aberto') ? fechar() : abrir());

    veu.addEventListener('click', fechar);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && gaveta.classList.contains('aberto')) {
            fechar();
        }
    });

    gaveta.querySelectorAll('a').forEach((link) => link.addEventListener('click', fechar));
}

const relogio = document.querySelector('[data-relogio]');

if (relogio) {

    const alvo = new Date(relogio.dataset.relogio).getTime();

    const desenhar = () => {
        const resta = alvo - Date.now();

        if (Number.isNaN(alvo) || resta <= 0) {

            relogio.hidden = true;

            return;
        }

        const segundos = Math.floor(resta / 1000);
        const partes = {
            dias: Math.floor(segundos / 86400),
            horas: Math.floor((segundos % 86400) / 3600),
            min: Math.floor((segundos % 3600) / 60),
        };

        for (const [chave, valor] of Object.entries(partes)) {
            const campo = relogio.querySelector(`[data-relogio-${chave}]`);

            if (campo) {
                campo.textContent = String(valor).padStart(2, '0');
            }
        }
    };

    desenhar();
    setInterval(desenhar, 30000);
}

const botaoSom = document.getElementById('som');
const palcoArea = document.getElementById('palco-area');
const varredura = document.getElementById('varredura');

if (botaoSom) {

    let audio = null;

    botaoSom.addEventListener('click', () => {
        if (audio && !audio.paused) {
            audio.pause();
            audio.currentTime = 0;
            botaoSom.dataset.tocando = '0';

            return;
        }

        audio ??= new Audio(botaoSom.dataset.src);

        audio.play().then(() => {
            botaoSom.dataset.tocando = '1';

            if (menosMovimento) {
                return;
            }

            palcoArea?.classList.add('tranco');
            setTimeout(() => palcoArea?.classList.remove('tranco'), 600);

            if (varredura) {
                varredura.animate(
                    [{ opacity: 0, transform: 'translateX(-30%)' },
                     { opacity: 1, offset: 0.4 },
                     { opacity: 0, transform: 'translateX(30%)' }],
                    { duration: 900, easing: 'cubic-bezier(.3,.8,.3,1)' },
                );
            }
        }).catch(() => {

            botaoSom.dataset.tocando = '0';
        });

        audio.addEventListener('ended', () => (botaoSom.dataset.tocando = '0'), { once: true });
    });
}

const reveláveis = document.querySelectorAll('.revela');

if (menosMovimento || !('IntersectionObserver' in window)) {
    reveláveis.forEach((alvo) => alvo.classList.add('visto'));
} else {
    const observador = new IntersectionObserver(
        (entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('visto');
                    observador.unobserve(entrada.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px' },
    );

    reveláveis.forEach((alvo) => observador.observe(alvo));
}

const palco = document.querySelector('.palco');

if (palco && !menosMovimento) {
    let agendado = false;

    const mover = () => {
        agendado = false;

        const y = parada('rolar') ? 0 : Math.min(window.scrollY * 0.16, 60);
        palco.style.setProperty('--desloca', `${y}px`);
    };

    window.addEventListener('scroll', () => {
        if (!agendado) {
            agendado = true;
            requestAnimationFrame(mover);
        }
    }, { passive: true });
}

const CORTINA_MS = 1500;

const cortina = document.getElementById('cortina');

if (cortina) {
    const rosto = document.getElementById('cortina-rosto');

    let rostos = [];
    try {
        rostos = JSON.parse(cortina.dataset.rostos || '[]');
    } catch {
        rostos = [];
    }

    const sortear = () => rostos[Math.floor(Math.random() * rostos.length)] ?? '';

    if (cortina.dataset.chegada === '1' && !window.location.hash && !parada('cortina')) {
        cortina.classList.add('abrindo');

        setTimeout(() => cortina.classList.add('fim'), 1400);
    } else {
        cortina.classList.add('fim');
    }

    const fechar = (destino) => {
        if (rosto) {
            rosto.src = sortear();
        }

        cortina.classList.remove('fim', 'abrindo');
        cortina.classList.add('fechando');

        setTimeout(() => { window.location.href = destino; }, CORTINA_MS);

        setTimeout(() => cortina.classList.add('fim'), CORTINA_MS + 2500);
    };

    document.addEventListener('click', (evento) => {
        const link = evento.target.closest('a[href]');

        if (!link || evento.defaultPrevented || parada('cortina')) {
            return;
        }

        if (link.target === '_blank' || link.hasAttribute('download')
            || evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey || evento.button !== 0) {
            return;
        }

        let url;
        try {
            url = new URL(link.href, window.location.href);
        } catch {
            return;
        }

        if (url.origin !== window.location.origin
            || url.pathname === window.location.pathname
            || url.pathname.startsWith('/painel')) {
            return;
        }

        evento.preventDefault();
        fechar(url.href);
    });

    window.addEventListener('pageshow', () => {
        cortina.classList.remove('fechando');
        cortina.classList.add('fim');
    });
}

const palcoSec = document.querySelector('.palco-sec');

if (palcoSec && !menosMovimento && 'IntersectionObserver' in window) {
    new IntersectionObserver(
        ([entrada]) => palcoSec.classList.toggle('fora-de-vista', !entrada.isIntersecting),

        { rootMargin: '200px' },
    ).observe(palcoSec);
}

const DESVIO_DO_TOPO = 72;

function rolarAte(destinoY) {
    const inicio = window.scrollY;
    const distancia = destinoY - inicio;

    if (!distancia) {
        return;
    }

    if (parada('rolagem')) {
        window.scrollTo({ top: destinoY, behavior: 'instant' });

        return;
    }

    const duracao = Math.min(1300, Math.max(450, Math.abs(distancia) * 0.45));
    const suavizar = (t) => (t < 0.5 ? 2 * t * t : 1 - ((-2 * t + 2) ** 2) / 2);
    let comeco = null;

    const passo = (agora) => {
        comeco ??= agora;
        const parte = Math.min(1, (agora - comeco) / duracao);
        window.scrollTo({ top: inicio + distancia * suavizar(parte), behavior: 'instant' });

        if (parte < 1) {
            requestAnimationFrame(passo);
        }
    };

    requestAnimationFrame(passo);
}

document.addEventListener('click', (evento) => {
    const link = evento.target.closest('a[href^="#"], a[href*="#"]');

    if (!link) {
        return;
    }

    const href = link.getAttribute('href') ?? '';

    if (!href.startsWith('#')) {
        const url = new URL(link.href, window.location.href);

        if (url.pathname !== window.location.pathname) {
            return;
        }
    }

    const id = href.slice(href.indexOf('#') + 1);
    const alvo = id ? document.getElementById(id) : null;

    if (!id || !alvo) {
        return;
    }

    evento.preventDefault();
    rolarAte(Math.max(0, alvo.getBoundingClientRect().top + window.scrollY - DESVIO_DO_TOPO));
    history.pushState(null, '', href.startsWith('#') ? href : `#${id}`);
});

if (window.location.hash) {
    const alvoDaChegada = document.getElementById(window.location.hash.slice(1));

    if (alvoDaChegada) {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        window.scrollTo({ top: 0, behavior: 'instant' });

        requestAnimationFrame(() => {
            rolarAte(Math.max(0, alvoDaChegada.getBoundingClientRect().top + window.scrollY - DESVIO_DO_TOPO));
        });
    }
}

const subir = document.getElementById('subir');

const artistas = document.querySelectorAll('.elenco__toque');

if (artistas.length) {
    const fechar = (menos) => artistas.forEach((b) => {
        if (b !== menos) {
            b.setAttribute('aria-expanded', 'false');
        }
    });

    artistas.forEach((botao) => {
        botao.addEventListener('click', () => {
            const aberto = botao.getAttribute('aria-expanded') === 'true';
            fechar(botao);
            botao.setAttribute('aria-expanded', aberto ? 'false' : 'true');
        });
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            fechar(null);

            if (document.activeElement?.classList.contains('elenco__toque')) {
                document.activeElement.blur();
            }
        }
    });

    document.addEventListener('click', (evento) => {
        if (!evento.target.closest('.elenco__m')) {
            fechar(null);
        }
    });
}

const social = document.getElementById('social-flut');

if (subir || social) {

    subir?.addEventListener('click', () => rolarAte(0));

    const avaliar = () => {
        const passou = window.scrollY > 700;
        subir?.classList.toggle('visivel', passou);
        social?.classList.toggle('visivel', passou);
    };

    avaliar();
    window.addEventListener('scroll', avaliar, { passive: true });
}

const abrir = (modal) => {
    if (!modal?.showModal) {
        return false;
    }

    modal.showModal();

    return true;
};

document.addEventListener('click', (evento) => {
    const fechar = evento.target.closest('[data-fecha-modal]');

    if (fechar) {
        fechar.closest('dialog')?.close();

        return;
    }

    const gatilho = evento.target.closest('[data-abre-modal]');

    if (gatilho && !evento.metaKey && !evento.ctrlKey && evento.button === 0) {
        if (abrir(document.getElementById(gatilho.dataset.abreModal))) {
            evento.preventDefault();
        }
    }
});

document.querySelectorAll('dialog.modal').forEach((modal) => {
    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) {
            modal.close();
        }
    });
});

const modalContratar = document.getElementById('modal-contratar');

if (modalContratar?.querySelector('.erro, .recado')) {
    abrir(modalContratar);
}

const modal = document.getElementById('modal-video');

if (modal) {
    const quadro = document.getElementById('modal-video-quadro');
    const titulo = document.getElementById('modal-video-titulo');
    const onde = document.getElementById('modal-video-onde');
    const linkYoutube = document.getElementById('modal-video-youtube');
    const botaoCompartilhar = document.getElementById('modal-video-compartilhar');
    const linkZap = document.getElementById('modal-video-zap');
    const aviso = document.getElementById('modal-video-aviso');

    let ultimoFoco = null;

    const fecharModal = () => {
        modal.removeAttribute('open');

        quadro.innerHTML = '';
        quadro.classList.remove('modal__quadro--em-pe');
        document.body.classList.remove('menu-aberto');
        ultimoFoco?.focus();
    };

    const abrirModal = (cartao) => {
        const d = cartao.dataset;
        ultimoFoco = cartao;

        titulo.textContent = d.titulo;
        onde.textContent = d.onde || '';
        aviso.hidden = d.demonstracao !== '1';

        if (d.youtube) {
            const quadroYt = document.createElement('iframe');

            quadroYt.src = `https://www.youtube-nocookie.com/embed/${d.youtube}?autoplay=1&rel=0`;
            quadroYt.title = d.titulo;
            quadroYt.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen';
            quadroYt.allowFullscreen = true;
            quadro.replaceChildren(quadroYt);

            linkYoutube.href = `https://www.youtube.com/watch?v=${d.youtube}`;
            linkYoutube.textContent = 'Ver no YouTube';
            linkYoutube.hidden = false;
        } else if (d.embed) {

            const quadroExt = document.createElement('iframe');
            quadroExt.src = d.embed;
            quadroExt.title = d.titulo;
            quadroExt.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
            quadroExt.allowFullscreen = true;
            quadro.replaceChildren(quadroExt);
            quadro.classList.toggle('modal__quadro--em-pe', d.provedor === 'Instagram' || d.provedor === 'TikTok');

            linkYoutube.href = d.origem;
            linkYoutube.textContent = `Ver no ${d.provedor}`;
            linkYoutube.hidden = !d.origem;
        } else {
            const video = document.createElement('video');
            video.controls = true;
            video.autoplay = true;
            video.playsInline = true;
            if (d.capa) video.poster = d.capa;

            for (const [caminho, tipo] of [[d.webm, 'video/webm'], [d.mp4, 'video/mp4']]) {
                if (caminho) {
                    const fonte = document.createElement('source');
                    fonte.src = caminho;
                    fonte.type = tipo;
                    video.appendChild(fonte);
                }
            }

            quadro.replaceChildren(video);
            linkYoutube.hidden = true;

            video.addEventListener('loadedmetadata', () =>
                quadro.classList.toggle('modal__quadro--em-pe', video.videoHeight > video.videoWidth), { once: true });
        }

        const alvo = `${location.origin}${location.pathname}#videos`;
        const texto = `${d.titulo} — ${document.title}`;

        linkZap.href = `https://wa.me/?text=${encodeURIComponent(`${texto} ${alvo}`)}`;

        botaoCompartilhar.onclick = async () => {
            try {
                if (navigator.share) {
                    await navigator.share({ title: d.titulo, text: texto, url: alvo });

                    return;
                }

                await navigator.clipboard.writeText(alvo);
                botaoCompartilhar.textContent = 'Link copiado!';
                setTimeout(() => (botaoCompartilhar.textContent = 'Compartilhar'), 2200);
            } catch {

            }
        };

        modal.setAttribute('open', '');
        document.body.classList.add('menu-aberto');
        modal.querySelector('.modal__fechar')?.focus();
    };

    document.querySelectorAll('[data-video]').forEach((cartao) =>
        cartao.addEventListener('click', () => abrirModal(cartao)));

    modal.querySelectorAll('[data-modal-fecha]').forEach((alvo) =>
        alvo.addEventListener('click', fecharModal));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.hasAttribute('open')) {
            fecharModal();
        }
    });
}

const modalFoto = document.getElementById('modal-foto');

if (modalFoto?.showModal) {
    const imagem = document.getElementById('modal-foto-imagem');
    const tituloFoto = document.getElementById('modal-foto-titulo');
    const ondeFoto = document.getElementById('modal-foto-onde');
    const creditoFoto = document.getElementById('modal-foto-credito');
    const descricaoFoto = document.getElementById('modal-foto-descricao');
    const linkFoto = document.getElementById('modal-foto-link');
    const contador = document.getElementById('modal-foto-contador');
    const setaAntes = modalFoto.querySelector('[data-foto-anterior]');
    const setaDepois = modalFoto.querySelector('[data-foto-proxima]');

    let grupo = [];
    let indice = 0;

    const mostrar = (posicao) => {

        indice = (posicao + grupo.length) % grupo.length;

        const d = grupo[indice].dataset;

        imagem.src = d.foto;
        imagem.alt = d.alt || '';

        tituloFoto.textContent = d.legenda || d.alt || 'Foto';

        ondeFoto.textContent = [d.contexto, d.quando].filter(Boolean).join(' · ');

        creditoFoto.textContent = d.credito ? `Foto: ${d.credito}` : '';
        creditoFoto.hidden = !d.credito;

        descricaoFoto.textContent = d.descricao || '';
        descricaoFoto.hidden = !d.descricao;
        descricaoFoto.scrollTop = 0;

        linkFoto.href = d.link || '#';
        linkFoto.hidden = !d.link;

        const varias = grupo.length > 1;
        setaAntes.hidden = !varias;
        setaDepois.hidden = !varias;
        contador.textContent = varias ? `${indice + 1} / ${grupo.length}` : '';
    };

    document.addEventListener('click', (evento) => {
        const cartao = evento.target.closest('[data-foto]');

        if (!cartao) {
            return;
        }

        grupo = [...document.querySelectorAll(`[data-foto][data-grupo="${cartao.dataset.grupo}"]`)];
        mostrar(Math.max(0, grupo.indexOf(cartao)));
        modalFoto.showModal();
    });

    setaAntes.addEventListener('click', () => mostrar(indice - 1));
    setaDepois.addEventListener('click', () => mostrar(indice + 1));

    modalFoto.addEventListener('keydown', (evento) => {
        if (grupo.length < 2) {
            return;
        }

        if (evento.key === 'ArrowLeft') {
            evento.preventDefault();
            mostrar(indice - 1);
        }

        if (evento.key === 'ArrowRight') {
            evento.preventDefault();
            mostrar(indice + 1);
        }
    });

    modalFoto.addEventListener('close', () => {
        imagem.removeAttribute('src');
    });
}

document.querySelectorAll('[data-vitrine]').forEach((faixa) => {
    const caixa = faixa.parentElement;
    const antes = caixa.querySelector('[data-vitrine-antes]');
    const depois = caixa.querySelector('[data-vitrine-depois]');

    if (!antes || !depois) {
        return;
    }

    const passo = () => Math.max(160, faixa.clientWidth * 0.8);

    const atualizar = () => {
        const rolavel = faixa.scrollWidth - faixa.clientWidth > 8;

        antes.hidden = !rolavel;
        depois.hidden = !rolavel;

        if (!rolavel) {
            return;
        }

        antes.disabled = faixa.scrollLeft <= 4;
        depois.disabled = faixa.scrollLeft + faixa.clientWidth >= faixa.scrollWidth - 4;
    };

    const rolar = (direcao) => faixa.scrollBy({
        left: direcao * passo(),
        behavior: menosMovimento ? 'auto' : 'smooth',
    });

    antes.addEventListener('click', () => rolar(-1));
    depois.addEventListener('click', () => rolar(1));
    faixa.addEventListener('scroll', atualizar, { passive: true });
    window.addEventListener('resize', atualizar);

    atualizar();
});
