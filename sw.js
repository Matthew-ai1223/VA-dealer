/**
 * VA Auto Sales PWA Service Worker
 * 
 * Note: Caching is intentionally disabled per user instructions.
 * This service worker acts as a transparent network pass-through,
 * which fulfills the browser requirement for PWA installation
 * without caching any files locally.
 */

const CACHE_NAME = 'va-auto-sales-v1';

// Install event - activates immediately
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

// Activate event - claims control immediately
self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

// Fetch event - direct pass-through to network (no caching)
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch((err) => {
      // In case of network failure, let the browser handle it naturally
      console.warn('[Service Worker] Fetch failed; returning network error:', err);
      throw err;
    })
  );
});
