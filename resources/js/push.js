import axios from 'axios';
import { reactive } from 'vue';

export const estadoPush = reactive({

    suportado: false,

    permissao: 'default',

    inscrito: false,

    ocupado: false,

    precisaInstalarNoIos: false,
});

function ehIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

function estaInstalado() {
    return (
        window.matchMedia?.('(display-mode: standalone)').matches ||
        window.navigator.standalone === true
    );
}

function chaveParaBytes(base64url) {
    const preenchimento = '='.repeat((4 - (base64url.length % 4)) % 4);
    const base64 = (base64url + preenchimento).replace(/-/g, '+').replace(/_/g, '/');
    const cru = window.atob(base64);

    return Uint8Array.from([...cru].map((c) => c.charCodeAt(0)));
}

export async function sincronizarEstado() {
    estadoPush.suportado =
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window;

    if (!estadoPush.suportado) {
        estadoPush.precisaInstalarNoIos = ehIos() && !estaInstalado();
        return;
    }

    estadoPush.permissao = Notification.permission;
    estadoPush.precisaInstalarNoIos = ehIos() && !estaInstalado();

    const registro = await navigator.serviceWorker.ready;
    estadoPush.inscrito = Boolean(await registro.pushManager.getSubscription());
}

export async function ativar(chavePublica) {
    if (!estadoPush.suportado || estadoPush.ocupado || !chavePublica) return false;

    estadoPush.ocupado = true;

    try {
        const permissao = await Notification.requestPermission();
        estadoPush.permissao = permissao;

        if (permissao !== 'granted') return false;

        const registro = await navigator.serviceWorker.ready;

        const inscricao =
            (await registro.pushManager.getSubscription()) ??
            (await registro.pushManager.subscribe({

                userVisibleOnly: true,
                applicationServerKey: chaveParaBytes(chavePublica),
            }));

        try {
            await axios.post('/push/inscricao', inscricao.toJSON());
        } catch {

            await inscricao.unsubscribe();
            return false;
        }

        estadoPush.inscrito = true;
        return true;
    } catch {
        return false;
    } finally {
        estadoPush.ocupado = false;
    }
}

export async function desativar() {
    if (!estadoPush.suportado || estadoPush.ocupado) return;

    estadoPush.ocupado = true;

    try {
        const registro = await navigator.serviceWorker.ready;
        const inscricao = await registro.pushManager.getSubscription();

        if (!inscricao) {
            estadoPush.inscrito = false;
            return;
        }

        await axios.delete('/push/inscricao', {
            data: { endpoint: inscricao.endpoint },
        });

        await inscricao.unsubscribe();
        estadoPush.inscrito = false;
    } catch {

    } finally {
        estadoPush.ocupado = false;
    }
}
