// Service Worker do GP.
//
// Estrategia deliberadamente CONSERVADORA: cacheia so arquivo estatico
// (CSS, SVG, PNG, JS, fontes). Paginas do PHP (extrato, kanban, perfil...)
// NUNCA sao cacheadas -- elas tem token CSRF preso a sessao do usuario e
// dados que mudam a hora inteira (saldo, tarefas, mensagens); servir isso
// do cache seria mostrar informacao desatualizada ou, pior, quebrar o
// proximo formulario com um CSRF token que nao bate mais.
//
// Se estiver offline, a pessoa ve uma pagina de aviso simples em vez do
// erro feio padrao do navegador -- mas o APP em si (dados reais) so
// funciona com internet, de proposito.

const CACHE_NAME = 'gp-estatico-v1';

const ASSETS_ESTATICOS = [
    '/style.css',
    '/assets/img/logo/logo.svg',
    '/assets/img/logo/icon.svg',
    '/manifest.json',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_ESTATICOS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((nomes) =>
            Promise.all(nomes.filter((nome) => nome !== CACHE_NAME).map((nome) => caches.delete(nome)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // So GET pode ser cacheado -- POST (login, formularios, o chat de
    // suporte, etc.) sempre vai direto pra rede, sem passar pelo cache.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const ehArquivoEstatico = /\.(css|js|svg|png|jpg|jpeg|webp|ico|woff2?)$/.test(url.pathname);

    if (!ehArquivoEstatico) {
        // Paginas dinamicas do PHP: direto pra rede. So cai no fallback se
        // estiver genuinamente offline.
        event.respondWith(
            fetch(request).catch(() => paginaOffline(request))
        );
        return;
    }

    // Estatico: cache-first (mais rapido pra abrir), atualizando o cache em
    // segundo plano a cada visita pra nao ficar preso numa versao antiga
    // pra sempre.
    event.respondWith(
        caches.match(request).then((cacheado) => {
            const buscaNaRede = fetch(request)
                .then((resposta) => {
                    if (resposta && resposta.ok) {
                        const copia = resposta.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copia));
                    }
                    return resposta;
                })
                .catch(() => cacheado);

            return cacheado || buscaNaRede;
        })
    );
});

function paginaOffline(request) {
    if (request.mode === 'navigate') {
        return new Response(
            `<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sem conexão — GP</title>
<style>
    body { font-family: system-ui, -apple-system, sans-serif; background: #212529; color: #f8f9fa;
           display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;
           text-align: center; padding: 24px; box-sizing: border-box; }
    .caixa { max-width: 360px; }
    h1 { font-size: 1.3rem; margin-bottom: 8px; }
    p { color: #adb5bd; font-size: 0.95rem; line-height: 1.5; }
    button { margin-top: 18px; padding: 10px 22px; border: none; border-radius: 6px;
             background: #fd7e14; color: #fff; font-weight: bold; cursor: pointer; font-size: 0.95rem; }
</style>
</head>
<body>
    <div class="caixa">
        <h1>Sem conexão com a internet</h1>
        <p>O GP precisa de internet pra carregar suas informações. Verifique sua conexão e tente de novo.</p>
        <button onclick="location.reload()">Tentar novamente</button>
    </div>
</body>
</html>`,
            { headers: { 'Content-Type': 'text/html; charset=UTF-8' } }
        );
    }

    return new Response('', { status: 503, statusText: 'Offline' });
}
