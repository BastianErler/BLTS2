<template>
    <div class="space-y-4">
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <!-- Season Picker -->
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-white">Saison</div>
                    <div class="text-xs text-white/60">
                        Rangliste & Auswertung je Saison
                    </div>
                </div>

                <div class="shrink-0">
                    <div class="relative">
                        <select
                            v-model="selectedSeasonId"
                            class="h-10 w-[140px] appearance-none rounded-xl border border-white/10 bg-white/5 px-3 pr-9 text-sm font-semibold text-white outline-none focus:border-white/20"
                            :disabled="seasonsLoading || seasons.length === 0"
                            @change="onSeasonChange"
                        >
                            <option
                                v-if="seasonsLoading"
                                :value="null"
                                disabled
                            >
                                Lade…
                            </option>

                            <option
                                v-for="s in seasons"
                                :key="s.id"
                                :value="s.id"
                                class="bg-navy-900 text-white"
                            >
                                {{ s.name }}{{ s.is_active ? " · aktiv" : "" }}
                            </option>
                        </select>

                        <!-- Chevron -->
                        <span
                            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-white/70"
                            aria-hidden="true"
                        >
                            ▾
                        </span>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="my-4 h-px bg-white/10"></div>

            <!-- Loading -->
            <p v-if="loading" class="text-center text-sm text-white/70 py-8">
                Lade Rangliste…
            </p>

            <!-- Error -->
            <div
                v-else-if="error"
                class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4"
            >
                <div class="text-sm font-semibold text-rose-200">
                    Konnte Rangliste nicht laden
                </div>
                <div class="text-sm text-rose-200/80 mt-1">
                    {{ error }}
                </div>
                <button class="btn-secondary mt-3" type="button" @click="load">
                    Erneut versuchen
                </button>
            </div>

            <template v-else>
                <!-- Dein Stand -->
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-white"
                                aria-hidden="true"
                            >
                                🏒
                            </div>

                            <div>
                                <div class="text-xs text-white/60">
                                    Dein Stand
                                </div>
                                <div class="mt-0.5 flex items-baseline gap-2">
                                    <div
                                        class="text-lg font-semibold text-white"
                                    >
                                        Platz {{ me?.rank ?? "—" }}
                                    </div>
                                    <div class="text-sm text-white/60">
                                        · {{ formatMoney(me?.total_cost) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-white/60">
                                seit letztem Spiel
                            </div>

                            <div
                                class="mt-1 inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold"
                                :class="deltaTone(me?.delta ?? 0)"
                            >
                                <span v-if="(me?.delta ?? 0) > 0">▲</span>
                                <span v-else-if="(me?.delta ?? 0) < 0">▼</span>
                                <span v-else>•</span>

                                <span v-if="(me?.delta ?? 0) > 0"
                                    >+{{ me?.delta }}</span
                                >
                                <span v-else-if="(me?.delta ?? 0) < 0">{{
                                    me?.delta
                                }}</span>
                                <span v-else>0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 3 -->
                <div
                    class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-semibold text-white">
                            Top 3
                        </div>
                        <div class="text-xs text-white/60">
                            Stand: {{ lastUpdated }}
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <!-- 2 -->
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-3 text-center"
                        >
                            <div class="text-[11px] text-white/60">2.</div>
                            <div
                                class="mt-1 truncate text-sm font-semibold text-white"
                            >
                                {{ top3[1]?.name ?? "—" }}
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                {{ formatMoney(top3[1]?.total_cost) }}
                            </div>
                        </div>

                        <!-- 1 -->
                        <div
                            class="rounded-2xl border border-bordeaux-400/30 bg-bordeaux-600/10 p-3 text-center"
                        >
                            <div class="text-[11px] text-bordeaux-100/90">
                                1. 🏆
                            </div>
                            <div
                                class="mt-1 truncate text-sm font-semibold text-white"
                            >
                                {{ top3[0]?.name ?? "—" }}
                            </div>
                            <div class="mt-1 text-xs text-bordeaux-100/90">
                                {{ formatMoney(top3[0]?.total_cost) }}
                            </div>
                        </div>

                        <!-- 3 -->
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-3 text-center"
                        >
                            <div class="text-[11px] text-white/60">3.</div>
                            <div
                                class="mt-1 truncate text-sm font-semibold text-white"
                            >
                                {{ top3[2]?.name ?? "—" }}
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                {{ formatMoney(top3[2]?.total_cost) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gesamtrangliste -->
                <div class="mt-4 rounded-2xl border border-white/10 bg-white/5">
                    <div class="border-b border-white/10 p-4">
                        <div class="text-sm font-semibold text-white">
                            Gesamtrangliste
                        </div>
                        <div class="text-xs text-white/60">
                            Freundeskreis · max. 20 Spieler
                        </div>
                    </div>

                    <ul class="divide-y divide-white/10">
                        <li
                            v-for="row in entries"
                            :key="row.id"
                            class="flex items-center justify-between gap-3 p-4"
                            :class="row.is_me ? 'bg-ice-400/10' : ''"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border text-sm font-semibold"
                                    :class="
                                        row.rank === 1
                                            ? 'border-bordeaux-400/30 bg-bordeaux-600/10 text-bordeaux-100'
                                            : 'border-white/10 bg-white/5 text-white/90'
                                    "
                                >
                                    {{ row.rank }}
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="flex items-center gap-2 min-w-0"
                                    >
                                        <div
                                            class="truncate text-sm font-semibold text-white"
                                        >
                                            {{ row.name }}
                                        </div>

                                        <span
                                            v-if="row.is_me"
                                            class="shrink-0 rounded-full border border-ice-300/30 bg-ice-400/10 px-2 py-0.5 text-[11px] font-semibold text-ice-100"
                                        >
                                            Du
                                        </span>
                                    </div>

                                    <div class="text-xs text-white/60">
                                        {{ formatMoney(row.total_cost) }}
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0 text-right">
                                <div class="text-xs text-white/60">Δ</div>
                                <div
                                    class="mt-1 inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold"
                                    :class="deltaTone(row.delta)"
                                >
                                    <span v-if="row.delta > 0">▲</span>
                                    <span v-else-if="row.delta < 0">▼</span>
                                    <span v-else>•</span>

                                    <span v-if="row.delta > 0"
                                        >+{{ row.delta }}</span
                                    >
                                    <span v-else-if="row.delta < 0">{{
                                        row.delta
                                    }}</span>
                                    <span v-else>0</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </template>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    leaderboardApi,
    seasonsApi,
    type Season,
    type LeaderboardEntry,
    type LeaderboardResponse,
} from "@/services/api";

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const error = ref<string | null>(null);

const data = ref<LeaderboardResponse | null>(null);

const me = computed(() => data.value?.me ?? null);
const top3 = computed<LeaderboardEntry[]>(() => data.value?.top3 ?? []);
const entries = computed<LeaderboardEntry[]>(() => data.value?.entries ?? []);

const lastUpdated = computed(() => {
    const iso = data.value?.generated_at;
    if (!iso) return "—";
    const d = new Date(iso);
    const date = d.toLocaleDateString("de-DE", {
        day: "2-digit",
        month: "2-digit",
    });
    const time = d.toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
    });
    return `${date} ${time}`;
});

/* ================= SEASONS ================= */

const seasonsLoading = ref(true);
const seasons = ref<Season[]>([]);
const selectedSeasonId = ref<number | null>(null);

function pickDefaultSeasonId(list: Season[]) {
    // bevorzugt active, sonst erste (neueste)
    const active = list.find((s) => s.is_active);
    return (active?.id ?? list[0]?.id ?? null) as number | null;
}

async function loadSeasons() {
    seasonsLoading.value = true;

    try {
        const res = await seasonsApi.getAll();
        seasons.value = res.data.data ?? [];

        // URL Query bevorzugen: ?season=ID
        const fromQuery = Number(route.query.season);
        const hasQuery = Number.isFinite(fromQuery) && fromQuery > 0;

        selectedSeasonId.value = hasQuery
            ? fromQuery
            : pickDefaultSeasonId(seasons.value);
    } finally {
        seasonsLoading.value = false;
    }
}

function onSeasonChange() {
    // query sync (nice für sharing / browser back)
    if (selectedSeasonId.value) {
        router.replace({
            query: { ...route.query, season: String(selectedSeasonId.value) },
        });
    } else {
        const q = { ...route.query };
        delete (q as any).season;
        router.replace({ query: q });
    }
}

/* wenn user per back/forward die query ändert -> reload */
watch(
    () => route.query.season,
    (val) => {
        const n = Number(val);
        if (Number.isFinite(n) && n > 0 && n !== selectedSeasonId.value) {
            selectedSeasonId.value = n;
        }
    },
);

/* ================= LEADERBOARD LOAD ================= */

async function load() {
    loading.value = true;
    error.value = null;

    try {
        const res = await leaderboardApi.get({
            season_id: selectedSeasonId.value ?? undefined,
        });
        data.value = res.data;
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? e?.message ?? "Unbekannter Fehler";
        data.value = null;
    } finally {
        loading.value = false;
    }
}

watch(
    () => selectedSeasonId.value,
    () => {
        if (!seasonsLoading.value) load();
    },
);

onMounted(async () => {
    await loadSeasons();
    await load();
});

/* ================= HELPERS ================= */

function deltaTone(delta: number) {
    if (delta > 0)
        return "border-emerald-300/30 bg-emerald-400/10 text-emerald-200";
    if (delta < 0) return "border-rose-400/20 bg-rose-400/10 text-rose-200";
    return "border-white/10 bg-white/5 text-white/70";
}

function formatMoney(value: number | null | undefined) {
    if (value === null || value === undefined) return "—";
    const n = Number(value);
    if (Number.isNaN(n)) return "—";
    return `${n.toFixed(2).replace(".", ",")} €`;
}
</script>
