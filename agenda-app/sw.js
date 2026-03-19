// Service Worker - Agenda CRCAP v3
const CACHE = 'agenda-crcap-v3';
const STATIC = [
  '/crcap/agenda-app/',
  '/crcap/agenda-app/manifest.json',
  '/crcap/agenda-app/icon-192.png',
  '/crcap/agenda-app/icon-512.png',
];

self.addEventListener('install', e => {
  self.skipWaiting();
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(STATIC).catch(()=>{})));
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  // Sempre busca do servidor para requisições AJAX e PHP
  if (url.searchParams.has('ajax') || url.pathname.endsWith('.php')) {
    e.respondWith(fetch(e.request).catch(() =>
      new Response(JSON.stringify({ok:false,msg:'Sem conexão'}),
        {headers:{'Content-Type':'application/json'}})
    ));
    return;
  }
  // Cache-first para assets estáticos
  e.respondWith(
    caches.match(e.request).then(cached =>
      cached || fetch(e.request).then(res => {
        if (res.ok && STATIC.includes(url.pathname)) {
          const clone = res.clone();
          caches.open(CACHE).then(c => c.put(e.request, clone));
        }
        return res;
      })
    )
  );
});

// Push notifications
self.addEventListener('push', e => {
  const data = e.data?.json() || { title: 'Agenda CRCAP', body: 'Novo compromisso!' };
  e.waitUntil(self.registration.showNotification(data.title, {
    body: data.body,
    icon: '/crcap/agenda-app/icon-192.png',
    badge: '/crcap/agenda-app/icon-192.png',
    tag: 'agenda-crcap',
    renotify: true,
    vibrate: [200, 100, 200],
  }));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  e.waitUntil(clients.openWindow('/crcap/agenda-app/'));
});