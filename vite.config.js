import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";

export default defineConfig({
    plugins: [vue()],
    root: "resources/vue",
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
});
