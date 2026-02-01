<template>
    <div class="flex flex-col min-h-screen bg-navy-900 text-white">
        <!-- ================= HEADER ================= -->
        <header class="px-4 pt-4 pb-2">
            <h1 class="font-display text-3xl tracking-wide">Spiele</h1>
        </header>

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
                    clickable
                    @bet="openBet(game)"
                />
            </template>

            <!-- Live Spiele -->
            <template v-else-if="activeTab === 'live'">
                <GameCard
                    v-for="game in liveGames"
                    :key="game.id"
                    :game="game"
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
                />
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
import GameCard from "@/components/GameCard.vue";
import BetModal from "@/components/BetModal.vue";

import { gamesApi, type Game, type Bet } from "@/services/api";

/* ================= TABS ================= */

type TabKey = "upcoming" | "live" | "past";

const activeTab = ref<TabKey>("upcoming");

const tabs = [
    { key: "upcoming", label: "Kommende" },
    { key: "live", label: "Live" },
    { key: "past", label: "Vergangen" },
];

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

onMounted(load);

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
