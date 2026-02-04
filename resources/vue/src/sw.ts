/// <reference lib="webworker" />

import { clientsClaim } from "workbox-core";
import { precacheAndRoute, cleanupOutdatedCaches } from "workbox-precaching";
import { registerRoute, NavigationRoute } from "workbox-routing";
import { createHandlerBoundToURL } from "workbox-precaching";

declare let self: ServiceWorkerGlobalScope;

const SW_VERSION = "2026-02-03-3";

self.skipWaiting();
clientsClaim();

// VitePWA injects this at build time:
precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();

// optional: navigation fallback
registerRoute(
    new NavigationRoute(createHandlerBoundToURL("/offline.html"), {
        denylist: [/^\/api\//],
    }),
);

// --- messaging helpers ---
async function broadcast(message: any) {
    const allClients = await self.clients.matchAll({
        type: "window",
        includeUncontrolled: true,
    });
    for (const c of allClients) c.postMessage(message);
}

self.addEventListener("message", (event) => {
    const data = event.data;
    if (data?.type === "sw-status") {
        event.source?.postMessage?.({
            type: "sw-status",
            v: SW_VERSION,
            at: new Date().toISOString(),
        });
        return;
    }

    // optional: generic log
    broadcast({
        type: "sw-message",
        v: SW_VERSION,
        at: new Date().toISOString(),
        data,
    });
});

setInterval(() => {
    broadcast({
        type: "sw-heartbeat",
        v: SW_VERSION,
        at: new Date().toISOString(),
    });
}, 5000);
