import { computed, ref } from "vue";
import { appConfigApi, type AppConfigResponse } from "@/services/appConfigApi";

const config = ref<AppConfigResponse | null>(null);
const loaded = ref(false);

export function useAppConfig() {
    async function load() {
        // einmal laden reicht (Singleton)
        if (loaded.value) return;

        // optional: cache
        const cached = localStorage.getItem("app_config_cache");
        if (cached) {
            try {
                config.value = JSON.parse(cached) as AppConfigResponse;
            } catch {}
        }

        try {
            const fresh = await appConfigApi.get();
            config.value = fresh;
            localStorage.setItem("app_config_cache", JSON.stringify(fresh));
        } finally {
            loaded.value = true;
        }
    }

    const pwaDebug = computed(() => !!config.value?.pwa?.debug);
    const pushTestEnabled = computed(() => !!config.value?.pwa?.push_test);
    const vapidPublicKey = computed(
        () => config.value?.pwa?.vapid_public_key ?? null,
    );

    return {
        config,
        loaded,
        load,
        pwaDebug,
        pushTestEnabled,
        vapidPublicKey,
    };
}
