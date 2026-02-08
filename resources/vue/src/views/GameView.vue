<template>
    <div class="flex flex-col min-h-screen bg-navy-900 text-white">
        <!-- ================= TABS ================= -->
        <div
            class="sticky top-0 z-20 bg-navy-900/95 backdrop-blur border-b border-navy-700"
        >
            <div class="flex px-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex-1 py-3 text-sm font-semibold transition"
                    :class="
                        activeTab === tab.key
                            ? 'text-bordeaux-600 border-b-2 border-bordeaux-600'
                            : 'text-navy-200'
                    "
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <!-- ================= CONTENT ================= -->
        <div class="flex-1 px-3 py-4 space-y-4">
            <!-- Loading -->
            <p v-if="loading" class="text-center text-sm text-navy-300 pt-6">
                Lade Spiele…
            </p>

            <!-- Error -->
            <p v-else-if="error" class="text-center text-sm text-rose-300 pt-6">
                {{ error }}
            </p>

            <!-- Kommende Spiele -->
            <template v-else-if="activeTab === 'upcoming'">
                <GameCard
                    v-for="game in upcomingGames"
                    :key="game.id"
                    :game="game"
                    :user-bet="game.user_bet ?? null"
                    :is-admin="isAdmin"
                    clickable
                    @bet="openBet(game)"
                    @admin-edit="goToGameEdit"
                />

                <p
                    v-if="upcomingGames.length === 0"
                    class="text-center text-sm text-navy-300 pt-8"
                >
                    Aktuell keine kommenden Spiele
                </p>
            </template>

            <!-- Live Spiele -->
            <template v-else-if="activeTab === 'live'">
                <GameCard
                    v-for="game in liveGames"
                    :key="game.id"
                    :game="game"
                    :is-admin="isAdmin"
                    @admin-edit="goToGameEdit"
                />
                <p
                    v-if="liveGames.length === 0"
                    class="text-center text-sm text-navy-300 pt-8"
                >
                    Aktuell keine Live-Spiele
                </p>
            </template>

            <!-- Vergangene Spiele -->
            <template v-else>
                <GameCard
                    v-for="game in pastGames"
                    :key="game.id"
                    :game="game"
                    :user-bet="game.user_bet ?? null"
                    :is-admin="isAdmin"
                    @admin-edit="goToGameEdit"
                />

                <p
                    v-if="pastGames.length === 0"
                    class="text-center text-sm text-navy-300 pt-8"
                >
                    Aktuell keine vergangenen Spiele
                </p>
            </template>
        </div>

        <!-- ================= BET MODAL ================= -->
        <BetModal
            :open="betModalOpen"
            :game="selectedGame"
            :existing-bet="selectedExistingBet"
            @close="closeBet"
            @saved="onBetSaved"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";

import GameCard from "@/components/GameCard.vue";
import BetModal from "@/components/BetModal.vue";

import { gamesApi, authApi, type Game, type Bet } from "@/services/api";

/* ================= ROUTER ================= */
const router = useRouter();

/* ================= TABS ================= */
type TabKey = "upcoming" | "live" | "past";

const activeTab = ref<TabKey>("upcoming");

const tabs = [
    { key: "upcoming", label: "Kommende" },
    { key: "live", label: "Live" },
    { key: "past", label: "Vergangen" },
];

/* ================= ADMIN FLAG (wie ProfileView) ================= */
const isAdmin = ref(false);

async function loadMeForAdminFlag() {
    try {
        const res = await authApi.getMe();
        const data = (res as any)?.data;
        const u = data?.user ?? data;

        isAdmin.value = Boolean(u?.is_admin);
        localStorage.setItem("is_admin", isAdmin.value ? "1" : "0");
    } catch {
        isAdmin.value = localStorage.getItem("is_admin") === "1";
    }
}

/* ================= DATA ================= */
const loading = ref(true);
const error = ref<string | null>(null);

const games = ref<Game[]>([]);

/* ================= FETCH ================= */
const load = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await gamesApi.getAll();
        games.value = res.data.data ?? [];
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ??
            e?.message ??
            "Spiele konnten nicht geladen werden.";
        games.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    // wie bei dir: erst localStorage, dann /me refresh
    isAdmin.value = localStorage.getItem("is_admin") === "1";
    await loadMeForAdminFlag();

    await load();
});

/* ================= COMPUTED ================= */
const upcomingGames = computed(() =>
    games.value.filter((g) => g.status === "scheduled"),
);

const liveGames = computed(() =>
    games.value.filter((g) => g.status === "live"),
);

const pastGames = computed(() =>
    games.value.filter((g) => g.status === "finished"),
);

/* ================= ADMIN EDIT NAV ================= */
function goToGameEdit(game: Game) {
    // Route-Name bitte an deine echte Route anpassen
    // Empfehlung: /admin/games/:id/edit
    router.push({
        name: "admin-games-edit",
        params: { id: game.id },
    });
}

/* ================= MODAL ================= */
const betModalOpen = ref(false);
const selectedGame = ref<Game | null>(null);
const selectedExistingBet = ref<Bet | null>(null);

function openBet(game: Game) {
    selectedGame.value = game;
    selectedExistingBet.value = game.user_bet ?? null;
    betModalOpen.value = true;
}

function closeBet() {
    betModalOpen.value = false;
    selectedGame.value = null;
    selectedExistingBet.value = null;
}

function onBetSaved(payload: { gameId: number; bet: Bet }) {
    // 1) Update in Liste (damit "Dein Tipp" sofort sichtbar wird)
    const idx = games.value.findIndex((g) => g.id === payload.gameId);
    if (idx !== -1) {
        games.value[idx] = {
            ...games.value[idx],
            user_bet: payload.bet,
        } as any;
    }

    // 2) Update im offenen Modal-State
    if (selectedGame.value?.id === payload.gameId) {
        selectedGame.value.user_bet = payload.bet as any;
        selectedExistingBet.value = payload.bet;
    }
}
</script>
