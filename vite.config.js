import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";
import { VitePWA } from "vite-plugin-pwa";

export default defineConfig({
    root: "resources/vue",

    plugins: [
        vue(),

        VitePWA({
            /**
             * 🔑 EXTREM WICHTIG
             * Wir benutzen InjectManifest → dein eigener Service Worker
             */
            strategies: "injectManifest",

            /**
             * Pfad zu deinem Custom-SW
             */
            srcDir: "src",
            filename: "sw.ts",

            injectManifest: {
                /**
                 * Ziel-Datei im Build (dist / dev-dist)
                 */
                swDest: "sw.js",
            },

            /**
             * SW Registrierung automatisch
             * → KEIN eigenes register() im Code!
             */
            registerType: "autoUpdate",
            injectRegister: "auto",

            /**
             * PWA / Scope
             */
            scope: "/",
            base: "/",

            /**
             * Assets
             */
            includeAssets: [
                "favicon.ico",
                "apple-touch-icon.png",
                "pwa-192x192.png",
                "pwa-512x512.png",
                "pwa-maskable-512x512.png",
            ],

            /**
             * Manifest
             */
            manifestFilename: "manifest.webmanifest",
            manifest: {
                name: "Blueliner Tippspiel",
                short_name: "BLTS",
                description: "Eisbären Berlin Fanclub Tippspiel",
                theme_color: "#0b0f14",
                background_color: "#0b0f14",
                display: "standalone",
                start_url: "/profile",
                scope: "/",
                id: "/",
                icons: [
                    {
                        src: "/pwa-192x192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                    {
                        src: "/pwa-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                    },
                    {
                        src: "/pwa-maskable-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                        purpose: "maskable",
                    },
                ],
            },

            /**
             * DEV MODE (iOS & Push Debug!)
             */
            devOptions: {
                enabled: true,
                /**
                 * Muss module sein, sonst iOS Chaos
                 */
                type: "module",
            },
        }),
    ],

    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/vue/src", import.meta.url)),
        },
    },

    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
    },

    build: {
        outDir: "dist",
        emptyOutDir: true,
    },
});
