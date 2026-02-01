<template>
    <div class="space-y-4">
        <div class="glass-card p-4">
            <h2 class="section-title">Login</h2>
            <p class="muted text-sm mt-1">
                Melde dich an, um tippen zu können.
            </p>

            <form class="mt-4 space-y-3" @submit.prevent="submit">
                <BaseInput
                    v-model="email"
                    label="E-Mail"
                    type="email"
                    placeholder="bastian@mail.de"
                />

                <BaseInput
                    v-model="password"
                    label="Passwort"
                    type="password"
                    placeholder="••••••••"
                />

                <div v-if="error" class="text-sm text-rose-300">
                    {{ error }}
                </div>

                <BaseButton
                    variant="primary"
                    size="md"
                    full-width
                    :loading="loading"
                    type="submit"
                >
                    Einloggen
                </BaseButton>
            </form>
        </div>

        <div class="glass-card p-4">
            <div class="text-sm text-white/70">Dev-Hinweis</div>
            <div class="text-sm text-white/80 mt-1">
                Token wird in localStorage gespeichert und bei Requests als
                <span class="font-mono">Authorization: Bearer</span> gesendet.
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import BaseInput from "@/components/BaseInput.vue";
import BaseButton from "@/components/BaseButton.vue";
import { authApi } from "@/services/api";

const router = useRouter();

const email = ref("");
const password = ref("");

const loading = ref(false);
const error = ref<string | null>(null);

const submit = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await authApi.login(email.value, password.value);

        // Erwartet: { token, user }
        const token = (res.data as any).token;
        if (!token) {
            throw new Error("Login-Response enthält kein Token.");
        }

        localStorage.setItem("auth_token", token);

        // Optional: user speichern (für später)
        localStorage.setItem(
            "me",
            JSON.stringify((res.data as any).user ?? {}),
        );

        await router.push("/");
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? e?.message ?? "Login fehlgeschlagen";
    } finally {
        loading.value = false;
    }
};
</script>
