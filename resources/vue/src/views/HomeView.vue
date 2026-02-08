<template>
    <div class="space-y-4">
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
                :user-bet="g.user_bet ?? null"
                :is-admin="isAdmin"
                :clickable="true"
                @click="openGame(g)"
                @bet="openBet(g)"
                @admin-edit="goToGameEdit"
            />
        </div>

        <!-- Bet Modal -->
        <BetModal
            :open="betModalOpen"
            :game="selectedGame"
            :existing-bet="selectedBet"
            @close="closeBetModal"
            @saved="onBetSaved"
        />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import GameCard from "@/components/GameCard.vue";
import BetModal from "@/components/BetModal.vue";
import { gamesApi, authApi, type Bet, type Game } from "@/services/api";

const router = useRouter();

const loading = ref(true);
const error = ref<string | null>(null);
const games = ref<Game[]>([]);

/* ================= ADMIN FLAG (wie ProfileView) ================= */
const isAdmin = ref(false);

/**
 * Robust: unterstützt {user:{...}} oder direkt {...}
 * Optional, falls localStorage mal out-of-sync ist.
 */
async function loadMeForAdminFlag() {
    try {
        const res = await authApi.getMe();
        const data = (res as any)?.data;
        const u = data?.user ?? data;

        isAdmin.value = Boolean(u?.is_admin);
        localStorage.setItem("is_admin", isAdmin.value ? "1" : "0");
    } catch {
        // fallback: local storage only
        isAdmin.value = localStorage.getItem("is_admin") === "1";
    }
}

// Modal state
const betModalOpen = ref(false);
const selectedGame = ref<Game | null>(null);
const selectedBet = ref<Bet | null>(null);

const load = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await gamesApi.getUpcoming();
        const list = res.data.data ?? [];

        // GameResource liefert user_bet bereits -> keine extra calls
        games.value = list;
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? e?.message ?? "Unbekannter Fehler";
    } finally {
        loading.value = false;
    }
};

const openGame = (game: Game) => {
    // Später: router.push(`/games/${game.id}`)
    router.push("/games");
};

const openBet = (game: Game) => {
    selectedGame.value = game;

    // Prefill direkt aus GameResource.user_bet
    selectedBet.value = game.user_bet ?? null;

    betModalOpen.value = true;
};

const closeBetModal = () => {
    betModalOpen.value = false;
    selectedGame.value = null;
    selectedBet.value = null;
};

const onBetSaved = (payload: { gameId: number; bet: Bet }) => {
    // 1) Direkt im aktuell selektierten Game updaten (sofort UI-Update)
    if (selectedGame.value?.id === payload.gameId) {
        selectedGame.value.user_bet = payload.bet as any;
        selectedBet.value = payload.bet;
    }

    // 2) Auch in der Games-Liste updaten (damit Card sofort "Dein Tipp" zeigt)
    const idx = games.value.findIndex((g) => g.id === payload.gameId);
    if (idx !== -1) {
        games.value[idx] = {
            ...games.value[idx],
            user_bet: payload.bet,
        } as any;
    }
};

function goToGameEdit(game: Game) {
    // Route-Name muss in deinem router existieren:
    // z.B. /admin/games/:id/edit
    router.push({
        name: "admin-games-edit",
        params: { id: game.id },
    });
}

onMounted(async () => {
    // bevorzugt localStorage (App.vue setzt es), optional via /me refresh
    isAdmin.value = localStorage.getItem("is_admin") === "1";
    await loadMeForAdminFlag();

    await load();
});
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
