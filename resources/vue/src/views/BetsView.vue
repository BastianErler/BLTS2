<template>
    <div class="flex flex-col min-h-screen bg-navy-900 text-white">
        <!-- ================= CONTENT ================= -->
        <div class="flex-1 px-3 py-4 space-y-4">
            <!-- Loading -->
            <p v-if="loading" class="text-center text-sm text-navy-300 pt-6">
                Lade Tipps…
            </p>

            <!-- Error -->
            <div
                v-else-if="error"
                class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4"
            >
                <div class="text-sm font-semibold text-rose-200">
                    Konnte Tipps nicht laden
                </div>
                <div class="text-sm text-rose-200/80 mt-1">
                    {{ error }}
                </div>
                <button class="btn-secondary mt-3" type="button" @click="load">
                    Erneut versuchen
                </button>
            </div>

            <!-- Empty -->
            <div v-else-if="bets.length === 0" class="glass-card p-4">
                <div class="text-sm font-semibold text-white">
                    Noch keine Tipps
                </div>
                <div class="text-sm text-white/70 mt-1">
                    Geh auf „Spiele“ und gib deinen ersten Tipp ab.
                </div>
            </div>

            <!-- List -->
            <div v-else class="space-y-3">
                <div
                    v-for="b in bets"
                    :key="b.id"
                    class="rounded-2xl bg-white/5 ring-1 ring-white/10 p-4"
                >
                    <!-- Top row: Match + date + status -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm text-white/70">
                                {{ formatKickoff(b.game?.kickoff_at) }}
                            </div>

                            <div
                                class="mt-1 text-lg font-extrabold text-white/95 truncate"
                            >
                                {{ titleLine(b) }}
                            </div>
                        </div>

                        <span
                            class="shrink-0 inline-flex items-center rounded-xl px-2.5 py-1 text-xs font-bold ring-1"
                            :class="outcomeTone(b)"
                        >
                            {{ outcomeLabel(b) }}
                        </span>
                    </div>

                    <!-- Teams / tip / result -->
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <!-- Left box -->
                        <div
                            class="rounded-2xl bg-black/20 ring-1 ring-white/10 p-3"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-7 w-7 rounded-full bg-white/10 ring-1 ring-white/10 flex items-center justify-center overflow-hidden"
                                >
                                    <img
                                        :src="leftLogo(b)"
                                        class="h-5 w-5 object-contain"
                                    />
                                </div>
                                <div
                                    class="text-sm font-semibold text-white/90 truncate"
                                >
                                    {{ leftLabel(b) }}
                                </div>
                            </div>

                            <div class="mt-3 text-xs text-white/60">
                                Dein Tipp
                            </div>
                            <div
                                class="mt-1 text-2xl font-extrabold text-white"
                            >
                                {{ leftTipGoals(b) }}
                            </div>

                            <div
                                v-if="b.game?.is_finished"
                                class="mt-3 text-xs text-white/60"
                            >
                                Ergebnis
                            </div>
                            <div
                                v-if="b.game?.is_finished"
                                class="mt-1 text-lg font-bold text-white/85"
                            >
                                {{ leftResultGoals(b) }}
                            </div>
                        </div>

                        <!-- Right box -->
                        <div
                            class="rounded-2xl bg-black/20 ring-1 ring-white/10 p-3 text-right"
                        >
                            <div class="flex items-center gap-2 justify-end">
                                <div
                                    class="text-sm font-semibold text-white/90 truncate"
                                >
                                    {{ rightLabel(b) }}
                                </div>
                                <div
                                    class="h-7 w-7 rounded-full bg-white/10 ring-1 ring-white/10 flex items-center justify-center overflow-hidden"
                                >
                                    <img
                                        :src="rightLogo(b)"
                                        class="h-5 w-5 object-contain"
                                    />
                                </div>
                            </div>

                            <div class="mt-3 text-xs text-white/60">
                                Dein Tipp
                            </div>
                            <div
                                class="mt-1 text-2xl font-extrabold text-white"
                            >
                                {{ rightTipGoals(b) }}
                            </div>

                            <div
                                v-if="b.game?.is_finished"
                                class="mt-3 text-xs text-white/60"
                            >
                                Ergebnis
                            </div>
                            <div
                                v-if="b.game?.is_finished"
                                class="mt-1 text-lg font-bold text-white/85"
                            >
                                {{ rightResultGoals(b) }}
                            </div>
                        </div>
                    </div>

                    <!-- Footer: joker + price + action -->
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span
                                v-if="b.joker_type"
                                class="inline-flex items-center rounded-xl px-2.5 py-1 text-xs font-bold bg-ice-500/15 text-ice-100 ring-1 ring-ice-500/30"
                            >
                                {{ jokerEmoji(b.joker_type) }} Joker
                            </span>

                            <span class="text-sm text-white/70">
                                Faktor:
                                <span class="font-semibold text-white/90">
                                    {{ formatMultiplier(b.multiplier) }}
                                </span>
                            </span>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-white/60">Kosten</div>
                            <div
                                class="text-lg font-extrabold"
                                :class="priceClass(b)"
                            >
                                {{ formatPrice(b.final_price) }}
                            </div>
                        </div>
                    </div>

                    <div v-if="canEdit(b)" class="mt-4 flex justify-end">
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-sm font-extrabold text-white bg-bordeaux-800 hover:bg-bordeaux-700 disabled:opacity-60 disabled:cursor-not-allowed"
                            @click="openBetFromBet(b)"
                        >
                            Tipp ändern
                        </button>
                    </div>
                </div>
            </div>
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
import { computed, onMounted, ref } from "vue";
import BetModal from "@/components/BetModal.vue";
import {
    betsApi,
    getLogoUrl,
    type Bet,
    type Game,
    type Team,
} from "@/services/api";

/**
 * Hinweis:
 * Bet.game muss ausreichend Felder enthalten:
 * - is_home
 * - opponent.logo_url / short_name
 * - kickoff_at
 * - is_finished
 * - can_bet
 * - eisbaeren_goals / opponent_goals
 */

const loading = ref(true);
const error = ref<string | null>(null);
const bets = ref<Bet[]>([]);

/* ================= MODAL ================= */

const betModalOpen = ref(false);
const selectedGame = ref<Game | null>(null);
const selectedExistingBet = ref<Bet | null>(null);

/* ================= LOAD ================= */

const load = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await betsApi.getAll();
        const list = (res as any).data?.data ?? (res as any).data ?? [];
        bets.value = list;
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? e?.message ?? "Unbekannter Fehler";
        bets.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(load);

/* ================= SORT (optional) ================= */

const sortedBets = computed(() => {
    // falls du es später nutzen willst
    return [...bets.value].sort((a, b) => {
        const ad = new Date(a.game?.kickoff_at ?? 0).getTime();
        const bd = new Date(b.game?.kickoff_at ?? 0).getTime();
        return bd - ad;
    });
});

/* ================= HELPERS ================= */

const eisbaeren: Team = {
    id: 1,
    name: "Eisbären Berlin",
    short_name: "EBB",
    logo_url: "1.svg",
};

function formatKickoff(iso?: string) {
    if (!iso) return "";
    const d = new Date(iso);
    const date = d.toLocaleDateString("de-DE", {
        day: "2-digit",
        month: "short",
    });
    const time = d.toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
    });
    return `${date} · ${time}`;
}

function titleLine(b: Bet) {
    const g = b.game;
    if (!g) return "Spiel";
    const left = g.is_home
        ? "EBB"
        : (g.opponent?.short_name ?? g.opponent?.name ?? "Gegner");
    const right = g.is_home
        ? (g.opponent?.short_name ?? g.opponent?.name ?? "Gegner")
        : "EBB";
    return `${left} vs ${right}`;
}

function leftTeam(b: Bet): Team {
    const g = b.game!;
    return g.is_home ? eisbaeren : (g.opponent as any);
}
function rightTeam(b: Bet): Team {
    const g = b.game!;
    return g.is_home ? (g.opponent as any) : eisbaeren;
}

function leftLabel(b: Bet) {
    const t = leftTeam(b);
    return t.short_name || t.name;
}
function rightLabel(b: Bet) {
    const t = rightTeam(b);
    return t.short_name || t.name;
}

function leftLogo(b: Bet) {
    return getLogoUrl(leftTeam(b).logo_url ?? null);
}
function rightLogo(b: Bet) {
    return getLogoUrl(rightTeam(b).logo_url ?? null);
}

/**
 * Tipp-Werte links/rechts wie im Modal:
 * - Heimspiel: EBB links
 * - Auswärts: EBB rechts
 */
function leftTipGoals(b: Bet) {
    const g = b.game!;
    return g.is_home ? b.eisbaeren_goals : b.opponent_goals;
}
function rightTipGoals(b: Bet) {
    const g = b.game!;
    return g.is_home ? b.opponent_goals : b.eisbaeren_goals;
}

/**
 * Ergebnis-Werte links/rechts
 */
function leftResultGoals(b: Bet) {
    const g = b.game!;
    if (!g.is_finished) return "-";
    return g.is_home ? g.eisbaeren_goals : g.opponent_goals;
}
function rightResultGoals(b: Bet) {
    const g = b.game!;
    if (!g.is_finished) return "-";
    return g.is_home ? g.opponent_goals : g.eisbaeren_goals;
}

/**
 * Outcome über "single source" base_price (und is_finished)
 * - offen: !is_finished
 * - 0.00 = exact
 * - 0.30 = tendency
 * - 0.60 = winner
 * - 1.00 = wrong (invalid wird nicht extra gelabelt)
 */
function outcomeKey(
    b: Bet,
): "open" | "exact" | "tendency" | "winner" | "wrong" {
    if (!b.game?.is_finished) return "open";

    const bp = Number(b.base_price);

    if (bp === 0) return "exact";
    if (bp === 0.3) return "tendency";
    if (bp === 0.6) return "winner";
    return "wrong";
}

function outcomeLabel(b: Bet) {
    return (
        {
            open: "Offen",
            exact: "✔ Volltreffer",
            tendency: "≈ Tendenz",
            winner: "🏆 Gewinner",
            wrong: "✖ Daneben",
        } as const
    )[outcomeKey(b)];
}

function outcomeTone(b: Bet) {
    return (
        {
            open: "bg-white/10 text-white/80 ring-white/10",
            exact: "bg-green-500/15 text-green-200 ring-green-500/30",
            tendency: "bg-yellow-400/15 text-yellow-200 ring-yellow-400/30",
            winner: "bg-ice-400/15 text-ice-200 ring-ice-400/30",
            wrong: "bg-rose-500/15 text-rose-200 ring-rose-500/30",
        } as const
    )[outcomeKey(b)];
}

function jokerEmoji(type: string) {
    const emojis: Record<string, string> = {
        safety: "🛡️",
        precision: "🎯",
        insurance: "💰",
        comeback: "⚡",
        double_down: "🔥",
        underdog: "🐺",
        bankier: "🏦",
    };
    return emojis[type] || "⭐";
}

function formatMultiplier(m: any) {
    const n = Number(m);
    if (Number.isNaN(n)) return "—";
    return n.toFixed(2).replace(".", ",") + "×";
}

function formatPrice(price: any) {
    const n = Number(price);
    if (Number.isNaN(n)) return "—";
    // negative = bonus
    const sign = n < 0 ? "-" : "";
    const abs = Math.abs(n);
    return `${sign}${abs.toFixed(2).replace(".", ",")} €`;
}

function priceClass(b: Bet) {
    const n = Number(b.final_price);
    if (Number.isNaN(n)) return "text-white/80";
    if (n < 0) return "text-green-200";
    if (n === 0) return "text-green-200";
    if (n <= 0.5) return "text-yellow-200";
    return "text-rose-200";
}

function canEdit(b: Bet) {
    // edit nur wenn spiel nicht finished, und server erlaubt tippen, und bet nicht gelocked
    if (!b.game) return false;
    if (b.game.is_finished) return false;
    if (!b.game.can_bet) return false;
    if ((b as any).is_locked) return false;
    return true;
}

/* ================= MODAL ACTIONS ================= */

function openBetFromBet(b: Bet) {
    if (!b.game) return;

    selectedGame.value = b.game as any;
    selectedExistingBet.value = b;
    betModalOpen.value = true;
}

function closeBet() {
    betModalOpen.value = false;
    selectedGame.value = null;
    selectedExistingBet.value = null;
}

function onBetSaved(payload: { gameId: number; bet: Bet }) {
    // Update im lokalen Bet-Array
    const idx = bets.value.findIndex((x) => x.id === payload.bet.id);
    if (idx !== -1) {
        bets.value[idx] = payload.bet;
    } else {
        // falls create aus irgendeinem Grund hier landet
        bets.value.unshift(payload.bet);
    }

    // wenn Modal offen ist
    selectedExistingBet.value = payload.bet;
    closeBet();
}
</script>
