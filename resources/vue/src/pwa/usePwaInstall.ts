import { ref } from "vue";

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{
        outcome: "accepted" | "dismissed";
        platform: string;
    }>;
};

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
const canInstall = ref(false);

let initialized = false;

export function usePwaInstall() {
    function init() {
        if (initialized) return;
        initialized = true;

        window.addEventListener("beforeinstallprompt", (e) => {
            console.log("[PWA] beforeinstallprompt fired");
            e.preventDefault();
            deferredPrompt.value = e as BeforeInstallPromptEvent;
            canInstall.value = true;
        });

        window.addEventListener("appinstalled", () => {
            console.log("[PWA] appinstalled");
            deferredPrompt.value = null;
            canInstall.value = false;
        });
    }

    async function triggerInstall() {
        if (!deferredPrompt.value) return;

        await deferredPrompt.value.prompt();
        await deferredPrompt.value.userChoice;

        deferredPrompt.value = null;
        canInstall.value = false;
    }

    return {
        canInstall,
        init,
        triggerInstall,
    };
}
