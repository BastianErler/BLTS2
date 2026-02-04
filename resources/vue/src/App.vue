<template>
    <AppLayout :user="user ?? undefined" :app-config="appConfig ?? undefined">
        <RouterView />
    </AppLayout>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { RouterView } from "vue-router";
import AppLayout from "@/components/AppLayout.vue";
import { authApi, appConfigApi, type AppConfigResponse } from "@/services/api";

type AppUser = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    balance: string | number;
    jokers_remaining: number;
    avatar?: string | null;
};

const user = ref<AppUser | null>(null);
const appConfig = ref<AppConfigResponse | null>(null);

onMounted(async () => {
    // user laden (falls eingeloggt)
    try {
        const me = await authApi.getMe();
        user.value = me.data as any;
    } catch {
        user.value = null;
    }

    // app-config laden (nur wenn eingeloggt, sonst 401)
    try {
        const cfg = await appConfigApi.get();
        appConfig.value = cfg.data;
    } catch {
        appConfig.value = null;
    }
});
</script>
