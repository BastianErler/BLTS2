<template>
    <div class="min-h-screen bg-navy-900 text-white px-3 py-4">
        <div class="mx-auto w-full max-w-lg space-y-4">
            <!-- Header -->
            <section
                class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-white">
                            Spiel bearbeiten
                        </div>
                        <div class="mt-1 text-xs text-white/60">
                            Admin-Bereich · Spiel-ID: {{ gameId }}
                        </div>
                    </div>

                    <button type="button" class="btn-secondary" @click="goBack">
                        Zurück
                    </button>
                </div>

                <div
                    v-if="!isAdmin"
                    class="mt-3 rounded-2xl border border-rose-300/30 bg-rose-400/10 p-4"
                >
                    <div class="text-xs font-semibold text-rose-200">
                        Kein Zugriff
                    </div>
                    <div class="mt-1 text-xs text-rose-100/90">
                        Dein Account ist aktuell nicht als Admin markiert.
                    </div>
                </div>
            </section>

            <!-- Loading -->
            <section
                v-if="loading"
                class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
            >
                <div class="h-5 w-44 rounded bg-white/10"></div>
                <div class="mt-3 h-10 w-full rounded-xl bg-white/10"></div>
                <div class="mt-3 h-10 w-full rounded-xl bg-white/10"></div>
                <div class="mt-3 h-10 w-full rounded-xl bg-white/10"></div>
            </section>

            <!-- Error -->
            <section
                v-else-if="error"
                class="rounded-[28px] border border-rose-500/20 bg-rose-500/10 p-4 backdrop-blur-md"
            >
                <div class="text-sm font-semibold text-rose-200">
                    Laden fehlgeschlagen
                </div>
                <div class="mt-1 text-sm text-rose-200/80 break-words">
                    {{ error }}
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" class="btn-secondary" @click="load">
                        Erneut versuchen
                    </button>
                    <button type="button" class="btn-secondary" @click="goBack">
                        Zurück
                    </button>
                </div>
            </section>

            <!-- Form -->
            <section
                v-else
                class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-white">
                            Details
                        </div>
                        <div class="mt-1 text-xs text-white/60">
                            Begegnung, Zeiten, Markierungen & Ergebnis
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-secondary"
                        :disabled="saving"
                        @click="load"
                    >
                        Neu laden
                    </button>
                </div>

                <div class="my-4 h-px bg-white/10"></div>

                <!-- Match summary -->
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs text-white/60">
                        Begegnung (Vorschau)
                    </div>
                    <div class="mt-1 text-sm font-semibold text-white">
                        {{ metaTitle }}
                    </div>

                    <div
                        class="mt-3 grid grid-cols-2 gap-3 text-xs text-white/70"
                    >
                        <div
                            class="rounded-xl border border-white/10 bg-white/5 p-3"
                        >
                            <div class="text-white/50">Spieltag</div>
                            <div class="mt-1 font-semibold text-white/80">
                                {{ form.match_number ?? "—" }}
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-white/10 bg-white/5 p-3"
                        >
                            <div class="text-white/50">Status (Info)</div>
                            <div class="mt-1 font-semibold text-white/80">
                                {{ statusLabel(form.status) }}
                            </div>
                            <div class="mt-1 text-[11px] text-white/50">
                                Technisch: {{ form.status }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Heim/Auswärts (editierbar) -->
                <div
                    class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-white/80">
                                Austragungsort
                            </div>
                            <div class="mt-1 text-[11px] text-white/50">
                                Wichtig bei Playoffs: Import kann falsch liegen.
                                Hier korrigieren.
                            </div>
                        </div>

                        <span
                            class="shrink-0 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold text-white/70"
                        >
                            {{ form.is_home ? "Heimspiel" : "Auswärtsspiel" }}
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-xl border border-white/10 bg-navy-900/40 px-3 py-3 text-sm font-semibold transition"
                            :class="
                                form.is_home
                                    ? 'ring-2 ring-bordeaux-600/60 text-white'
                                    : 'text-white/70 hover:bg-white/5'
                            "
                            @click="form.is_home = true"
                        >
                            Heimspiel (EBB links)
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border border-white/10 bg-navy-900/40 px-3 py-3 text-sm font-semibold transition"
                            :class="
                                !form.is_home
                                    ? 'ring-2 ring-bordeaux-600/60 text-white'
                                    : 'text-white/70 hover:bg-white/5'
                            "
                            @click="form.is_home = false"
                        >
                            Auswärts (EBB rechts)
                        </button>
                    </div>

                    <div
                        class="mt-3 rounded-xl border border-amber-300/20 bg-amber-400/10 p-3"
                    >
                        <div class="text-[11px] font-semibold text-amber-200">
                            Hinweis
                        </div>
                        <div class="mt-1 text-[11px] text-amber-100/90">
                            Das beeinflusst die Darstellung in der GameCard
                            (Teams links/rechts) und die Interpretation von
                            Tipps (EBB vs Gegner).
                        </div>
                    </div>
                </div>

                <!-- Zeiten -->
                <div
                    class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="text-xs font-semibold text-white/80">
                        Zeiten
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3">
                        <div>
                            <label class="block text-xs text-white/60">
                                Spielbeginn (Kickoff)
                            </label>
                            <input
                                v-model="form.kickoff_at_local"
                                type="datetime-local"
                                class="mt-2 w-full rounded-xl border border-white/10 bg-navy-900/50 px-3 py-3 text-sm text-white outline-none"
                            />
                            <div class="mt-2 text-[11px] text-white/50">
                                Eingabe ist lokal, gespeichert wird ISO (UTC).
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-white/60">
                                Tipp-Deadline
                            </label>
                            <div
                                class="mt-2 w-full rounded-xl border border-white/10 bg-navy-900/40 px-3 py-3 text-sm text-white/80"
                            >
                                {{ betDeadlineLabel }}
                            </div>
                            <div class="mt-2 text-[11px] text-white/50">
                                Wird automatisch berechnet: Kickoff − 6 Stunden
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Markierungen -->
                <div
                    class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="text-xs font-semibold text-white/80">
                        Markierungen
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <label
                            class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm text-white/80"
                        >
                            <input
                                v-model="form.is_playoff"
                                type="checkbox"
                                class="h-4 w-4"
                            />
                            Playoff
                        </label>

                        <label
                            class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm text-white/80"
                        >
                            <input
                                v-model="form.is_derby"
                                type="checkbox"
                                class="h-4 w-4"
                            />
                            Derby
                        </label>
                    </div>
                </div>

                <!-- Status / Automatik -->
                <div
                    class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-white/80">
                                Spielstatus
                            </div>
                            <div class="mt-1 text-[11px] text-white/50">
                                Optional: automatisch aus Kickoff/Ergebnis
                                ableiten oder manuell überschreiben.
                            </div>
                        </div>

                        <label
                            class="flex items-center gap-2 text-[11px] font-semibold text-white/70"
                        >
                            <input
                                v-model="statusAuto"
                                type="checkbox"
                                class="h-4 w-4"
                            />
                            Auto
                        </label>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3">
                        <div>
                            <label class="block text-xs text-white/60">
                                Status (manuell)
                            </label>
                            <select
                                v-model="form.status"
                                class="mt-2 w-full rounded-xl border border-white/10 bg-navy-900/50 px-3 py-3 text-sm text-white outline-none"
                                :disabled="statusAuto"
                            >
                                <option value="scheduled">Angesetzt</option>
                                <option value="live">Live</option>
                                <option value="finished">Beendet</option>
                            </select>

                            <div
                                v-if="statusAuto"
                                class="mt-2 text-[11px] text-white/50"
                            >
                                Automatisch gesetzt auf:
                                <span class="font-semibold text-white/70">
                                    {{ statusLabel(derivedStatus) }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-white/10 bg-white/5 p-3"
                        >
                            <div
                                class="text-[11px] font-semibold text-white/70"
                            >
                                Ableitungslogik (Auto)
                            </div>
                            <div class="mt-1 text-[11px] text-white/60">
                                • Kickoff in Zukunft → <b>Angesetzt</b><br />
                                • Kickoff vorbei + Ergebnis fehlt → <b>Live</b
                                ><br />
                                • Kickoff vorbei + Ergebnis vorhanden →<b
                                    >Beendet</b
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ergebnis -->
                <div
                    class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-white/80">
                                Ergebnis
                            </div>
                            <div class="mt-1 text-[11px] text-white/50">
                                Auch bei „Beendet“ weiter editierbar (Import
                                kann falsch sein).
                            </div>
                        </div>

                        <span
                            class="shrink-0 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold text-white/70"
                        >
                            {{ form.eisbaeren_goals }} :
                            {{ form.opponent_goals }}
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-white/60">
                                Eisbären Tore
                            </label>
                            <input
                                v-model.number="form.eisbaeren_goals"
                                type="number"
                                min="0"
                                class="mt-2 w-full rounded-xl border border-white/10 bg-navy-900/50 px-3 py-3 text-sm text-white outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs text-white/60">
                                Gegner Tore
                            </label>
                            <input
                                v-model.number="form.opponent_goals"
                                type="number"
                                min="0"
                                class="mt-2 w-full rounded-xl border border-white/10 bg-navy-900/50 px-3 py-3 text-sm text-white outline-none"
                            />
                        </div>
                    </div>

                    <div
                        v-if="form.status !== 'finished'"
                        class="mt-3 rounded-xl border border-amber-300/20 bg-amber-400/10 p-3"
                    >
                        <div class="text-[11px] font-semibold text-amber-200">
                            Hinweis
                        </div>
                        <div class="mt-1 text-[11px] text-amber-100/90">
                            Status ist nicht „Beendet“. Tore werden trotzdem
                            gespeichert, aber in der App ggf. erst bei „Beendet“
                            angezeigt.
                        </div>
                    </div>
                </div>

                <!-- Save / output -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn-primary"
                        :disabled="saving || !isAdmin"
                        @click="save"
                    >
                        {{ saving ? "Speichern…" : "Speichern" }}
                    </button>

                    <button
                        type="button"
                        class="btn-secondary"
                        :disabled="saving"
                        @click="goBack"
                    >
                        Abbrechen
                    </button>
                </div>

                <div
                    v-if="saveOk"
                    class="mt-3 rounded-2xl border border-emerald-300/30 bg-emerald-400/10 p-4"
                >
                    <div class="text-xs font-semibold text-emerald-200">
                        Gespeichert
                    </div>
                    <div class="mt-1 text-xs text-emerald-100/90">
                        Änderungen wurden übernommen.
                    </div>
                </div>

                <div
                    v-if="saveError"
                    class="mt-3 rounded-2xl border border-rose-300/30 bg-rose-400/10 p-4"
                >
                    <div class="text-xs font-semibold text-rose-200">
                        Speichern fehlgeschlagen
                    </div>
                    <div class="mt-1 text-xs text-rose-100/90 break-words">
                        {{ saveError }}
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { gamesApi, type Game } from "@/services/api";

const router = useRouter();
const route = useRoute();

const returnTo = computed(() => {
    const from = route.query.from;
    return typeof from === "string" && from.startsWith("/") ? from : null;
});

const gameId = computed(() => Number(route.params.id));
const isAdmin = ref(false);

const loading = ref(true);
const error = ref<string | null>(null);

const saving = ref(false);
const saveOk = ref(false);
const saveError = ref<string | null>(null);

const game = ref<Game | null>(null);

const form = ref({
    status: "scheduled" as "scheduled" | "live" | "finished",

    // for datetime-local input
    kickoff_at_local: "" as string,

    // read-only from backend
    bet_deadline_at: null as string | null,

    // flags
    is_playoff: false,
    is_derby: false,

    // meta/editable
    is_home: false as boolean,
    match_number: null as number | null,

    // scores
    eisbaeren_goals: 0 as number,
    opponent_goals: 0 as number,
});

const statusAuto = ref(true);

function statusLabel(s: "scheduled" | "live" | "finished") {
    if (s === "scheduled") return "Angesetzt";
    if (s === "live") return "Live";
    return "Beendet";
}

const metaTitle = computed(() => {
    const g = game.value;
    if (!g) return "—";
    const opp =
        (g as any)?.opponent?.short_name ||
        (g as any)?.opponent?.name ||
        "Gegner";

    const isHome = Boolean(form.value.is_home);

    return `${isHome ? "EBB vs" : opp + " vs"} ${isHome ? opp : "EBB"}`;
});

const betDeadlineLabel = computed(() => {
    const iso = form.value.bet_deadline_at;
    if (!iso) return "—";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "—";
    const date = d.toLocaleDateString("de-DE", {
        weekday: "short",
        day: "2-digit",
        month: "2-digit",
    });
    const time = d.toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
    });
    return `${date} · ${time} Uhr`;
});

function goBack() {
    if (returnTo.value) {
        router.replace(returnTo.value);
    } else {
        router.back();
    }
}

function toLocalDatetimeInputValue(iso: string | null | undefined) {
    if (!iso) return "";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "";
    const pad = (n: number) => String(n).padStart(2, "0");
    const yyyy = d.getFullYear();
    const mm = pad(d.getMonth() + 1);
    const dd = pad(d.getDate());
    const hh = pad(d.getHours());
    const mi = pad(d.getMinutes());
    return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
}

function localDatetimeToIso(value: string) {
    if (!value) return null;
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return null;
    return d.toISOString();
}

function hasScore(
    g1: number | null | undefined,
    g2: number | null | undefined,
) {
    const a = g1 !== null && g1 !== undefined;
    const b = g2 !== null && g2 !== undefined;
    return a && b;
}

const derivedStatus = computed<"scheduled" | "live" | "finished">(() => {
    const kickoffIso = localDatetimeToIso(form.value.kickoff_at_local);
    if (!kickoffIso) return "scheduled";

    const kickoff = new Date(kickoffIso).getTime();
    const now = Date.now();

    if (kickoff > now) return "scheduled";

    const scored = hasScore(
        form.value.eisbaeren_goals,
        form.value.opponent_goals,
    );
    return scored ? "finished" : "live";
});

watch(
    () => [
        statusAuto.value,
        form.value.kickoff_at_local,
        form.value.eisbaeren_goals,
        form.value.opponent_goals,
    ],
    () => {
        if (!statusAuto.value) return;
        form.value.status = derivedStatus.value;
    },
    { immediate: false },
);

function applyGameToForm(g: Game) {
    form.value.status = (g.status ?? "scheduled") as any;

    form.value.kickoff_at_local = toLocalDatetimeInputValue(
        (g as any).kickoff_at,
    );
    form.value.bet_deadline_at = (g as any).bet_deadline_at ?? null;

    form.value.is_playoff = Boolean((g as any).is_playoff);
    form.value.is_derby = Boolean((g as any).is_derby);

    form.value.is_home = Boolean((g as any).is_home);
    form.value.match_number = (g as any).match_number ?? null;

    form.value.eisbaeren_goals = Number((g as any).eisbaeren_goals ?? 0);
    form.value.opponent_goals = Number((g as any).opponent_goals ?? 0);

    if (statusAuto.value) {
        form.value.status = derivedStatus.value;
    }
}

async function load() {
    saveOk.value = false;
    saveError.value = null;

    loading.value = true;
    error.value = null;

    try {
        const res = await gamesApi.getOne(gameId.value);
        const maybe: any = res?.data?.data;
        if (!maybe) throw new Error("Leere Response");

        game.value = maybe as Game;
        applyGameToForm(game.value);
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ??
            e?.message ??
            "Spiel konnte nicht geladen werden.";
        game.value = null;
    } finally {
        loading.value = false;
    }
}

async function save() {
    if (!isAdmin.value) return;

    saving.value = true;
    saveOk.value = false;
    saveError.value = null;

    try {
        const payload: any = {
            is_home: Boolean(form.value.is_home),

            kickoff_at: localDatetimeToIso(form.value.kickoff_at_local),

            is_playoff: Boolean(form.value.is_playoff),
            is_derby: Boolean(form.value.is_derby),

            eisbaeren_goals: Number(form.value.eisbaeren_goals ?? 0),
            opponent_goals: Number(form.value.opponent_goals ?? 0),

            status: statusAuto.value ? derivedStatus.value : form.value.status,
        };

        await gamesApi.updateAdmin(gameId.value, payload);

        saveOk.value = true;

        // zurück dahin, wo du herkamst (GameCard übergibt ?from=/...)
        const from = route.query.from;
        if (typeof from === "string" && from.startsWith("/")) {
            router.replace(from);
            return;
        }

        // fallback
        router.back();
    } catch (e: any) {
        saveError.value =
            e?.response?.data?.message ??
            e?.message ??
            "Speichern fehlgeschlagen.";
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    isAdmin.value = localStorage.getItem("is_admin") === "1";

    if (!isAdmin.value) {
        loading.value = false;
        error.value = null;
        return;
    }

    await load();
});
</script>
