const VERSAO = '__VERSAO__';
const CACHE_SHELL = `banda-shell-${VERSAO}`;
const CACHE_ASSETS = `banda-assets-${VERSAO}`;

const PAGINA_OFFLINE = '/offline';

const SHELL = [PAGINA_OFFLINE, '/icons/icon-192.png', '/favicon.ico'];

self.addEventListener('install', (evento) => {
    evento.waitUntil(
        caches
            .open(CACHE_SHELL)

            .then((cache) => cache.addAll(SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches
            .keys()
            .then((nomes) =>
                Promise.all(
                    nomes

                        .filter((nome) => (nome.startsWith('banda-') || nome.startsWith('legado-')) && !nome.endsWith(VERSAO))
                        .map((nome) => caches.delete(nome)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (evento) => {
    if (evento.data === 'assumir-agora') {
        self.skipWaiting();
    }
});

function ehAsset(url) {
    return url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/');
}

self.addEventListener('fetch', (evento) => {
    const requisicao = evento.request;

    if (requisicao.method !== 'GET') return;

    const url = new URL(requisicao.url);
    if (url.origin !== self.location.origin) return;

    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/notificacoes/')) return;

    if (requisicao.mode === 'navigate') {
        evento.respondWith(
            fetch(requisicao).catch(() =>
                caches.match(PAGINA_OFFLINE, { cacheName: CACHE_SHELL }),
            ),
        );
        return;
    }

    if (ehAsset(url)) {
        evento.respondWith(
            caches.open(CACHE_ASSETS).then(async (cache) => {
                const guardado = await cache.match(requisicao);
                if (guardado) return guardado;

                const resposta = await fetch(requisicao);

                if (resposta.ok && resposta.status === 200) {
                    cache.put(requisicao, resposta.clone());
                }
                return resposta;
            }),
        );
    }
});

self.addEventListener('push', (evento) => {
    if (!evento.data) return;

    let carga;
    try {
        carga = evento.data.json();
    } catch {

        return;
    }

    const titulo = carga.title ?? 'A melhor banda';
    const opcoes = {
        body: carga.body ?? '',
        icon: carga.icon ?? '/icons/icon-192.png',
        badge: carga.badge ?? '/icons/icon-192.png',

        tag: carga.tag ?? 'banda',
        data: { url: carga.url ?? '/inicio' },
        requireInteraction: false,
    };

    evento.waitUntil(self.registration.showNotification(titulo, opcoes));
});

self.addEventListener('notificationclick', (evento) => {
    evento.notification.close();

    const destino = evento.notification.data?.url ?? '/inicio';

    evento.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((janelas) => {
                for (const janela of janelas) {
                    if (new URL(janela.url).origin === self.location.origin) {
                        janela.focus();
                        return janela.navigate(destino);
                    }
                }

                return self.clients.openWindow(destino);
            }),
    );
});
