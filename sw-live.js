// Service Worker - CRCAP Live v1
// Deploy em: /crcap/sw-live.js
const CACHE = 'crcap-live-v1';

self.addEventListener('install', e => { self.skipWaiting(); });
self.addEventListener('activate', e => { e.waitUntil(self.clients.claim()); });

// Push notifications — Live
self.addEventListener('push', e => {
  const data = e.data?.json() || {
    title: '🔴 AO VIVO — CRCAP',
    body: 'Transmissão ao vivo iniciada! Clique para assistir.',
    url: '/crcap/'
  };
  e.waitUntil(self.registration.showNotification(data.title, {
    body: data.body,
    icon: '/crcap/uploads/images/icon-192.png',
    badge: '/crcap/uploads/images/icon-192.png',
    tag: 'crcap-live',
    renotify: true,
    vibrate: [300, 100, 300, 100, 300],
    requireInteraction: true,
    actions: [
      { action: 'watch', title: '▶ Assistir agora' },
      { action: 'close', title: 'Fechar' }
    ],
    data: { url: data.url || '/crcap/' }
  }));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  if (e.action === 'close') return;
  const url = e.notification.data?.url || '/crcap/';
  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
      for (const c of list) {
        if (c.url.includes('/crcap/') && 'focus' in c) return c.focus();
      }
      return clients.openWindow(url);
    })
  );
});
