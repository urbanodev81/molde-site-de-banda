import { reactive } from 'vue';

export const estadoPwa = reactive({

    convite: null,

    podeInstalar: false,

    temVersaoNova: false,

    aguardando: null,
});

const CHAVE_DISPENSA = 'banda-pwa-dispensado';

const DIAS_DE_SILENCIO = 30;

function foiDispensadoRecentemente() {
    try {
        const quando = Number(localStorage.getItem(CHAVE_DISPENSA));
        if (!quando) return false;

        return Date.now() - quando < DIAS_DE_SILENCIO * 24 * 60 * 60 * 1000;
    } catch {

        return false;
    }
}

export function dispensarConvite() {
    estadoPwa.podeInstalar = false;
    try {
        localStorage.setItem(CHAVE_DISPENSA, String(Date.now()));
    } catch {

    }
}

export async function instalar() {
    if (!estadoPwa.convite) return false;

    estadoPwa.convite.prompt();
    const { outcome } = await estadoPwa.convite.userChoice;

    estadoPwa.convite = null;
    estadoPwa.podeInstalar = false;

    if (outcome === 'dismissed') {

        dispensarConvite();
    }

    return outcome === 'accepted';
}

export function aplicarVersaoNova() {
    estadoPwa.aguardando?.postMessage('assumir-agora');
    estadoPwa.temVersaoNova = false;
}

export function registrarPwa() {
    if (!('serviceWorker' in navigator)) return;

    if (!import.meta.env.PROD) return;

    window.addEventListener('beforeinstallprompt', (evento) => {

        evento.preventDefault();

        estadoPwa.convite = evento;
        estadoPwa.podeInstalar = !foiDispensadoRecentemente();
    });

    window.addEventListener('appinstalled', () => {
        estadoPwa.convite = null;
        estadoPwa.podeInstalar = false;
    });

    window.addEventListener('load', async () => {
        try {
            const registro = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

            if (registro.waiting) {
                estadoPwa.aguardando = registro.waiting;
                estadoPwa.temVersaoNova = true;
            }

            registro.addEventListener('updatefound', () => {
                const novo = registro.installing;
                if (!novo) return;

                novo.addEventListener('statechange', () => {

                    if (novo.state === 'installed' && navigator.serviceWorker.controller) {
                        estadoPwa.aguardando = novo;
                        estadoPwa.temVersaoNova = true;
                    }
                });
            });

            const procurarVersaoNova = () => registro.update().catch(() => {});

            setInterval(procurarVersaoNova, 30 * 60 * 1000);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') procurarVersaoNova();
            });
        } catch {

        }
    });

    let recarregando = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (recarregando) return;
        recarregando = true;
        window.location.reload();
    });
}
