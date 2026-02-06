<template>
    <AppLayout :user="user ?? undefined">
        <RouterView />
    </AppLayout>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { RouterView } from "vue-router";
import AppLayout from "@/components/AppLayout.vue";
import { authApi } from "@/services/api";

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

onMounted(async () => {
    // user laden (falls eingeloggt)
    try {
        const me = await authApi.getMe();
        user.value = me.data as any;
    } catch {
        user.value = null;
    }
});
</script>
