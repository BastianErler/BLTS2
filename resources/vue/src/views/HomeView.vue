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
                :user-bet="userBetsByGameId[g.id] ?? null"
                :clickable="true"
                @click="openGame(g)"
                @bet="openBet(g)"
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
import { gamesApi, type Bet, type Game } from "@/services/api";

const router = useRouter();

const loading = ref(true);
const error = ref<string | null>(null);
const games = ref<Game[]>([]);

// Local cache of bets by game id
const userBetsByGameId = ref<Record<number, Bet>>({});

// Modal state
const betModalOpen = ref(false);
const selectedGame = ref<Game | null>(null);
const selectedBet = ref<Bet | null>(null);

const normalizeBetResponse = (res: any): Bet | null => {
    const d = res?.data;

    if (d?.bet) return d.bet as Bet;

    if (d?.data?.bet) return d.data.bet as Bet;
    if (d?.data) return d.data as Bet;

    if (d?.id) return d as Bet;

    return null;
};

const loadUserBetsForGames = async (list: Game[]) => {
    // Wir laden pro Game den Tipp (nur wenige Upcoming Games => ok)
    const entries = await Promise.all(
        list.map(async (g) => {
            try {
                const res = await gamesApi.getUserBet(g.id);
                const bet = normalizeBetResponse(res);
                if (!bet) return [g.id, null] as const;
                return [g.id, bet] as const;
            } catch (e: any) {
                // Kein Tipp vorhanden ist kein Fehler (oft 404)
                return [g.id, null] as const;
            }
        }),
    );

    const map: Record<number, Bet> = {};
    for (const [gameId, bet] of entries) {
        if (bet) map[gameId] = bet;
    }

    userBetsByGameId.value = map;
};

const load = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await gamesApi.getUpcoming();
        const list = res.data.data ?? [];
        games.value = list;

        await loadUserBetsForGames(list);
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
    selectedBet.value = userBetsByGameId.value[game.id] ?? null; // Prefill aus Cache
    betModalOpen.value = true;
};

const closeBetModal = () => {
    betModalOpen.value = false;
    selectedGame.value = null;
    selectedBet.value = null;
};

const onBetSaved = (payload: { gameId: number; bet: Bet }) => {
    userBetsByGameId.value[payload.gameId] = payload.bet;

    if (selectedGame.value?.id === payload.gameId) {
        selectedBet.value = payload.bet;
    }
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
