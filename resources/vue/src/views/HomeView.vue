<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-end justify-between">
            <div>
                <h2 class="section-title">Nächste Spiele</h2>
                <p class="muted text-sm mt-1">
                    Tippe rechtzeitig – danach wird gesperrt.
                </p>
            </div>

            <RouterLink
                to="/games"
                class="text-sm font-semibold text-white/80 hover:text-white transition"
            >
                Alle →
            </RouterLink>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="space-y-3">
            <div v-for="n in 2" :key="n" class="glass-card p-4">
                <div class="h-5 w-28 rounded bg-white/10"></div>
                <div class="mt-3 h-24 w-full rounded-xl bg-white/10"></div>
                <div class="mt-3 h-11 w-full rounded-xl bg-white/10"></div>
            </div>
        </div>

        <!-- Error -->
        <div
            v-else-if="error"
            class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4"
        >
            <div class="text-sm font-semibold text-rose-200">
                Konnte Spiele nicht laden
            </div>
            <div class="text-sm text-rose-200/80 mt-1">
                {{ error }}
            </div>

            <button class="btn-secondary mt-3" type="button" @click="load">
                Erneut versuchen
            </button>
        </div>

        <!-- Empty -->
        <div v-else-if="games.length === 0" class="glass-card p-4">
            <div class="text-sm font-semibold text-white">
                Keine anstehenden Spiele gefunden
            </div>
            <div class="text-sm text-white/70 mt-1">
                Schau später nochmal rein.
            </div>
        </div>

        <!-- Games -->
        <div v-else class="space-y-3">
            <GameCard
                v-for="g in games"
                :key="g.id"
                :game="g"
                :user-bet="null"
                :clickable="true"
                @click="openGame(g)"
                @bet="openBet(g)"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import GameCard from "@/components/GameCard.vue";
import { gamesApi, type Game } from "@/services/api";

const router = useRouter();

const loading = ref(true);
const error = ref<string | null>(null);
const games = ref<Game[]>([]);

const load = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await gamesApi.getUpcoming();
        games.value = res.data.data ?? [];
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? e?.message ?? "Unbekannter Fehler";
    } finally {
        loading.value = false;
    }
};

const openGame = (game: Game) => {
    // Wenn du später eine Detailseite hast: router.push(`/games/${game.id}`)
    router.push("/games");
};

const openBet = (game: Game) => {
    router.push("/games");
};

onMounted(load);
</script>

<style scoped>
.quick-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    border-radius: 12px;
    background: rgb(255 255 255 / 0.08);
    border: 1px solid rgb(255 255 255 / 0.12);
    color: rgb(255 255 255 / 0.9);
    font-weight: 800;
    transition:
        transform 120ms ease,
        background-color 120ms ease;
}
.quick-link:hover {
    background: rgb(255 255 255 / 0.12);
}
.quick-link:active {
    transform: scale(0.99);
}
</style>
