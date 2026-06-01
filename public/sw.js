self.addEventListener('install', e => {
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(clients.claim());
});

// Cache offline
const CACHE = 'previas-v1';
const OFFLINE_URLS = [
    '/alumno/dashboard',
    '/alumno/historial',
    '/alumno/calendario',
    '/public/css/base.css',
    '/public/css/alumno.css',
    '/public/js/alumno.js',
    '/public/js/push.js',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(cache => cache.addAll(OFFLINE_URLS).catch(() => {}))
    );
});

self.addEventListener('fetch', e => {
    // Solo cachear GET de páginas del alumno y assets
    if (e.request.method !== 'GET') return;
    const url = new URL(e.request.url);
    const esPaginaAlumno = url.pathname.startsWith('/alumno/') || url.pathname.startsWith('/public/');
    if (!esPaginaAlumno) return;

    e.respondWith(
        fetch(e.request)
            .then(res => {
                // Guardar copia fresca en cache
                const clone = res.clone();
                caches.open(CACHE).then(cache => cache.put(e.request, clone));
                return res;
            })
            .catch(() => caches.match(e.request))
    );
});

self.addEventListener('push', e => {
    let data = { titulo: 'Previa', cuerpo: 'Tenés una previa próxima.', url: '/' };
    try { data = e.data.json(); } catch (_) {}
    e.waitUntil(
        self.registration.showNotification(data.titulo, {
            body: data.cuerpo,
            icon: '/public/icon.png',
            data: { url: data.url }
        })
    );
});

self.addEventListener('notificationclick', e => {
    e.notification.close();
    const url = e.notification.data?.url || '/';
    e.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const c of list) {
                if (c.url.includes(url) && 'focus' in c) return c.focus();
            }
            return clients.openWindow(url);
        })
    );
});
