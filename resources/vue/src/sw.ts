/// <reference lib="webworker" />

import { clientsClaim } from "workbox-core";
import { precacheAndRoute, cleanupOutdatedCaches } from "workbox-precaching";
import { registerRoute, NavigationRoute } from "workbox-routing";
import { createHandlerBoundToURL } from "workbox-precaching";

declare const self: ServiceWorkerGlobalScope;

self.skipWaiting();
clientsClaim();

// VitePWA injects this at build time:
precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();

// optional: navigation fallback (offline page)
registerRoute(
    new NavigationRoute(createHandlerBoundToURL("/offline.html"), {
        denylist: [/^\/api\//],
    }),
);

// =========================
// Push Notifications
// =========================

type PushPayload = {
    title?: string;
    body?: string;
    url?: string;
    icon?: string;
    badge?: string;
    tag?: string;
};

self.addEventListener("push", (event) => {
    const e = event as PushEvent;

    e.waitUntil(
        (async () => {
            let data: PushPayload = {};

            try {
                data = e.data ? (e.data.json() as PushPayload) : {};
            } catch {
                try {
                    const txt = e.data ? await e.data.text() : "";
                    data = { body: txt };
                } catch {
                    data = {};
                }
            }

            const title = data.title ?? "BLTS";
            const body = data.body ?? "";
            const url = data.url ?? "/profile";

            // Defaults – ensure these files exist in your dist root, otherwise remove them
            const icon = data.icon ?? "/pwa-192x192.png";
            const badge = data.badge ?? "/pwa-192x192.png";

            await self.registration.showNotification(title, {
                body,
                icon,
                badge,
                tag: data.tag ?? "blts-push",
                data: {
                    url,
                },
            });
        })(),
    );
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    const data = event.notification.data as any;
    const url = data?.url ?? "/";

    event.waitUntil(
        (async () => {
            const all = await self.clients.matchAll({
                type: "window",
                includeUncontrolled: true,
            });

            // If there is already a window, focus it and navigate
            for (const client of all) {
                const wc = client as WindowClient;
                try {
                    await wc.focus();
                    if ("navigate" in wc) await wc.navigate(url);
                    return;
                } catch {
                    // ignore and continue
                }
            }

            // Otherwise open a new window
            await self.clients.openWindow(url);
        })(),
    );
});
