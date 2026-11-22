const CACHE_NAME = 'garage-offline-v2';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // On ne met en cache que les requêtes GET de notre propre domaine
    if (request.method !== 'GET' || !request.url.startsWith(self.location.origin)) {
        return;
    }

    // Ne jamais mettre en cache les routes d'authentification ou de déconnexion
    const url = new URL(request.url);
    if (url.pathname.startsWith('/login') || url.pathname.startsWith('/logout') || url.pathname.startsWith('/register')) {
        return;
    }

    event.respondWith(
        fetch(request)
            .then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });

                    // Si c'est une page HTML (ex: /dashboard), on pré-charge en arrière-plan tous les véhicules et PDF liés
                    const contentType = networkResponse.headers.get('content-type') || '';
                    if (contentType.includes('text/html')) {
                        networkResponse.clone().text().then((html) => {
                            prefetchLinkedResources(html);
                        });
                    }
                }
                return networkResponse;
            })
            .catch(async () => {
                // Hors connexion : on sert la version en cache si elle existe
                const cached = await caches.match(request);
                if (cached) {
                    return cached;
                }

                // Si la page demandée n'a jamais été mise en cache, on redirige vers le dashboard en cache
                if (request.mode === 'navigate') {
                    const dashboardFallback = await caches.match('/dashboard');
                    if (dashboardFallback) {
                        return dashboardFallback;
                    }
                }

                return new Response('Contenu non disponible hors connexion.', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                });
            })
    );
});

/**
 * Extrait automatiquement les liens /vehicle/X et /uploads/... du HTML
 * et les télécharge en arrière-plan pour qu'ils soient prêts hors connexion.
 */
async function prefetchLinkedResources(html) {
    const cache = await caches.open(CACHE_NAME);
    const urlsToPrefetch = new Set();

    // 1. Toutes les fiches véhicules (/vehicle/1, /vehicle/2, etc.)
    const vehicleMatches = html.matchAll(/href="(\/vehicle\/\d+)"/g);
    for (const match of vehicleMatches) {
        urlsToPrefetch.add(match);
    }

    // 2. Toutes les factures et attestations (/uploads/invoices/... et /uploads/insurances/...)
    const uploadMatches = html.matchAll(/(\/uploads\/(?:invoices|insurances)\/[^"'\s>]+)/g);
    for (const match of uploadMatches) {
        urlsToPrefetch.add(match);
    }

    for (const path of urlsToPrefetch) {
        const alreadyCached = await cache.match(path);
        if (!alreadyCached) {
            try {
                const res = await fetch(path, { credentials: 'same-origin' });
                if (res.ok) {
                    await cache.put(path, res.clone());
                    // Si c'est une fiche véhicule, on pré-charge aussi les factures qu'elle contient !
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('text/html')) {
                        const subHtml = await res.text();
                        const subUploads = subHtml.matchAll(/(\/uploads\/(?:invoices|insurances)\/[^"'\s>]+)/g);
                        for (const subMatch of subUploads) {
                            const subCached = await cache.match(subMatch);
                            if (!subCached) {
                                const fileRes = await fetch(subMatch, { credentials: 'same-origin' });
                                if (fileRes.ok) await cache.put(subMatch, fileRes);
                            }
                        }
                    }
                }
            } catch (e) {
                // Ignore silencieusement si la connexion coupe pendant le pré-chargement
            }
        }
    }
}

