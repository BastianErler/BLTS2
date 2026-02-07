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

function normalizeMeResponse(data: any): AppUser | null {
    if (!data) return null;
    // unterstützt: { user: {...} } ODER direkt {...}
    const u = data.user ?? data;

    if (typeof u !== "object" || u === null) return null;

    // minimal required fields – wenn was fehlt, trotzdem best effort
    return {
        id: Number(u.id ?? 0),
        name: String(u.name ?? ""),
        email: String(u.email ?? ""),
        is_admin: Boolean(u.is_admin ?? false),
        balance: u.balance ?? "0.00",
        jokers_remaining: Number(u.jokers_remaining ?? 0),
        avatar: u.avatar ?? null,
    };
}

function persistAdminFlag(u: AppUser | null) {
    localStorage.setItem("is_admin", u?.is_admin ? "1" : "0");
}

onMounted(async () => {
    // user laden (falls eingeloggt)
    try {
        const me = await authApi.getMe();
        const normalized = normalizeMeResponse((me as any)?.data);

        user.value = normalized;
        persistAdminFlag(normalized);
    } catch {
        user.value = null;
        persistAdminFlag(null);
    }
});
</script>
