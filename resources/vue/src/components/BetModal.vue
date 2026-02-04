<template>
    <teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/70" @click="emitClose"></div>

            <!-- Dialog -->
            <div
                class="absolute inset-0 flex items-end sm:items-center justify-center p-3"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-navy-900/95 ring-1 ring-white/10 p-4"
                    role="dialog"
                    aria-modal="true"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm text-white/70">
                                {{ headlineDate }}
                            </div>
                            <div
                                class="mt-1 text-lg font-extrabold text-white/95 truncate"
                            >
                                {{ titleLine }}
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                Tippe rechtzeitig – danach wird gesperrt.
                            </div>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-xl px-3 py-2 text-white/80 hover:text-white hover:bg-white/10"
                            @click="emitClose"
                            aria-label="Schließen"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-2xl bg-white/5 ring-1 ring-white/10 p-3"
                            >
                                <div class="flex items-center gap-2">
                                    <img
                                        :src="leftLogo"
                                        class="h-7 w-7 object-contain"
                                    />
                                    <div
                                        class="text-sm font-semibold text-white/90 truncate"
                                    >
                                        {{ leftLabel }}
                                    </div>
                                </div>

                                <label class="mt-3 block text-xs text-white/60">
                                    Tore
                                </label>
                                <input
                                    v-model.number="leftGoals"
                                    type="number"
                                    min="0"
                                    max="20"
                                    inputmode="numeric"
                                    class="mt-1 w-full rounded-xl bg-black/30 ring-1 ring-white/10 px-3 py-2 text-white/95 placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-ice-500"
                                />
                            </div>

                            <div
                                class="rounded-2xl bg-white/5 ring-1 ring-white/10 p-3"
                            >
                                <div
                                    class="flex items-center gap-2 justify-end"
                                >
                                    <div
                                        class="text-sm font-semibold text-white/90 truncate text-right"
                                    >
                                        {{ rightLabel }}
                                    </div>
                                    <img
                                        :src="rightLogo"
                                        class="h-7 w-7 object-contain"
                                    />
                                </div>

                                <label
                                    class="mt-3 block text-xs text-white/60 text-right"
                                >
                                    Tore
                                </label>
                                <input
                                    v-model.number="rightGoals"
                                    type="number"
                                    min="0"
                                    max="20"
                                    inputmode="numeric"
                                    class="mt-1 w-full rounded-xl bg-black/30 ring-1 ring-white/10 px-3 py-2 text-white/95 placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-ice-500 text-right"
                                />
                            </div>
                        </div>

                        <div
                            v-if="error"
                            class="rounded-xl bg-rose-500/10 ring-1 ring-rose-500/30 px-3 py-2 text-sm text-rose-100"
                        >
                            {{ error }}
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-5 flex items-center gap-2">
                        <button
                            type="button"
                            class="w-1/3 rounded-xl px-3 py-3 text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 ring-1 ring-white/10"
                            @click="emitClose"
                            :disabled="saving"
                        >
                            Abbrechen
                        </button>

                        <button
                            type="button"
                            class="w-2/3 rounded-xl px-3 py-3 text-sm font-extrabold text-white bg-bordeaux-800 hover:bg-bordeaux-700 disabled:opacity-60 disabled:cursor-not-allowed"
                            @click="save"
                            :disabled="saving || !canSubmit"
                        >
                            {{
                                saving
                                    ? "Speichern…"
                                    : existingBetId
                                      ? "Tipp ändern"
                                      : "Tipp speichern"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Bet, Game, Team } from "@/services/api";
import { betsApi, gamesApi, getLogoUrl } from "@/services/api";

const props = defineProps<{
    open: boolean;
    game: Game | null;
    existingBet?: Bet | null; // <-- NEU
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "saved", payload: { gameId: number; bet: Bet }): void;
}>();

const saving = ref(false);
const error = ref<string | null>(null);

const existingBetId = ref<number | null>(null);

// left/right Values (UI-seitig, passend zu home/away Spiegelung)
const leftGoals = ref<number>(0);
const rightGoals = ref<number>(0);

// Heimspiel => EBB links, Auswärts => EBB rechts
const isHomeGame = computed(() => !!props.game?.is_home);

const eisbaeren = computed<Team>(() => ({
    id: 4,
    name: "Eisbären Berlin",
    short_name: "EBB",
    logo_url: "team_EBB.svg",
}));

const leftTeam = computed<Team>(() => {
    if (!props.game) return eisbaeren.value;
    return isHomeGame.value ? eisbaeren.value : props.game.opponent;
});
const rightTeam = computed<Team>(() => {
    if (!props.game) return eisbaeren.value;
    return isHomeGame.value ? props.game.opponent : eisbaeren.value;
});

const leftLabel = computed(
    () => leftTeam.value.short_name || leftTeam.value.name,
);
const rightLabel = computed(
    () => rightTeam.value.short_name || rightTeam.value.name,
);

const leftLogo = computed(() => getLogoUrl(leftTeam.value.logo_url));
const rightLogo = computed(() => getLogoUrl(rightTeam.value.logo_url));

const headlineDate = computed(() => {
    if (!props.game) return "";
    const date = new Date(props.game.kickoff_at);
    const d = date.toLocaleDateString("de-DE", {
        day: "2-digit",
        month: "short",
    });
    const t = date.toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
    });
    return `${d} · ${t}`;
});

const titleLine = computed(() => {
    if (!props.game) return "";
    return `${leftLabel.value} vs ${rightLabel.value}`;
});

const canSubmit = computed(() => {
    if (!props.game) return false;
    if (!Number.isFinite(leftGoals.value) || !Number.isFinite(rightGoals.value))
        return false;
    if (leftGoals.value < 0 || rightGoals.value < 0) return false;
    if (leftGoals.value > 20 || rightGoals.value > 20) return false;
    return true;
});

function emitClose() {
    if (saving.value) return;
    emit("close");
}

function applyBetToInputs(bet: Bet) {
    existingBetId.value = bet.id ?? null;

    // API-Bet ist immer in EBB/opponent Logik
    // UI ist left/right (spiegeln je nach Heim/Auswärts)
    if (isHomeGame.value) {
        // EBB links, Opp rechts
        leftGoals.value = bet.eisbaeren_goals ?? 0;
        rightGoals.value = bet.opponent_goals ?? 0;
    } else {
        // Opp links, EBB rechts
        leftGoals.value = bet.opponent_goals ?? 0;
        rightGoals.value = bet.eisbaeren_goals ?? 0;
    }
}

function resetInputs() {
    existingBetId.value = null;
    leftGoals.value = 0;
    rightGoals.value = 0;
}

async function loadExistingBetFromApi(gameId: number) {
    try {
        const res = await gamesApi.getUserBet(gameId);
        const bet = (res as any).data?.data ?? (res as any).data;
        if (!bet) return;
        applyBetToInputs(bet as Bet);
    } catch (e: any) {
        // kein existing bet => ok
    }
}

async function save() {
    if (!props.game) return;
    if (!canSubmit.value) return;

    saving.value = true;
    error.value = null;

    // zurück mappen ins API-Format (EBB/opponent)
    const payload = isHomeGame.value
        ? { eisbaeren_goals: leftGoals.value, opponent_goals: rightGoals.value }
        : {
              eisbaeren_goals: rightGoals.value,
              opponent_goals: leftGoals.value,
          };

    try {
        let res;
        if (existingBetId.value) {
            res = await betsApi.update(existingBetId.value, payload);
        } else {
            res = await betsApi.create({
                game_id: props.game.id,
                ...payload,
            });
        }

        const bet = (res as any).data?.data ?? (res as any).data;
        if (!bet) throw new Error("Unerwartete Antwort vom Server.");

        emit("saved", { gameId: props.game.id, bet: bet as Bet });
        emit("close");
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ||
            e?.response?.data?.error ||
            e?.message ||
            "Speichern fehlgeschlagen.";
    } finally {
        saving.value = false;
    }
}

// Modal-Open Lifecycle:
// 1) Sofort aus existingBet (Cache) befüllen
// 2) optional: danach nochmal API lesen (falls du Server truth willst)
watch(
    () => [props.open, props.game?.id, props.existingBet?.id] as const,
    async ([open, gameId]) => {
        error.value = null;
        if (!open || !gameId) return;

        resetInputs();

        if (props.existingBet) {
            applyBetToInputs(props.existingBet);
        } else {
            // wenn kein Cache-Bet vorhanden, versuchen wir API
            await loadExistingBetFromApi(gameId);
        }
    },
    { immediate: true },
);
</script>
