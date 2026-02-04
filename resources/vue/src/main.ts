import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";

import "../app.css";

import { usePwaInstall } from "@/pwa/usePwaInstall";

const app = createApp(App);

app.use(router);
app.mount("#app");

// PWA Install Hook initialisieren (einmal global)
const pwa = usePwaInstall();
pwa.init();
