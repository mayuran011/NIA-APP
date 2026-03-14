/**
 * Minimal service worker: optional offline "You're offline" for navigate requests.
 */
self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
    if (event.request.mode !== 'navigate') return;
    event.respondWith(
        fetch(event.request).catch(function () {
            return new Response(
                '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title><style>body{font-family:system-ui,sans-serif;background:#0f0f12;color:#f4f4f5;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:1rem;} h1{font-size:1.5rem;} a{color:#6366f1;}</style></head><body><div><h1>You\'re offline</h1><p>Check your connection and try again.</p><p><a href="./">Reload</a></p></div></body></html>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
        })
    );
});
