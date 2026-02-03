<template>
    <div class="space-y-4">
        <!-- =================== FINANZEN / KONTO =================== -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-white">Konto</div>
                    <div class="text-xs text-white/60">
                        Einzahlungen, offene Beiträge & Stand
                    </div>
                </div>

                <span
                    class="shrink-0 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80"
                >
                    Saison {{ seasonLabel }}
                </span>
            </div>

            <div class="my-4 h-px bg-white/10"></div>

            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs text-white/60">Schon gezahlt</div>
                    <div class="mt-1 text-lg font-semibold text-white">
                        {{ formatMoney(payments.paid_total) }}
                    </div>
                    <div class="mt-1 text-xs text-white/50">
                        inkl. Einzahlungen & Beiträge
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs text-white/60">Noch zu zahlen</div>
                    <div
                        class="mt-1 text-lg font-semibold"
                        :class="
                            payments.open_total > 0
                                ? 'text-rose-200'
                                : 'text-emerald-200'
                        "
                    >
                        {{ formatMoney(payments.open_total) }}
                    </div>
                    <div class="mt-1 text-xs text-white/50">
                        offene Beiträge / Nachzahlung
                    </div>
                </div>

                <div
                    class="col-span-2 rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs text-white/60">
                                Aktueller Stand
                            </div>
                            <div class="mt-1 text-xl font-semibold text-white">
                                {{ formatMoney(payments.balance) }}
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-white/60">Hinweis</div>
                            <div class="mt-1 text-xs text-white/70">
                                {{
                                    payments.balance >= 0
                                        ? "Guthaben verfügbar"
                                        : "Bitte ausgleichen"
                                }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="btn-primary">
                            Einzahlung hinzufügen
                        </button>
                        <button type="button" class="btn-secondary">
                            Zahlungen ansehen
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================== TIPPS PERFORMANCE =================== -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-white">Tipps</div>
                    <div class="text-xs text-white/60">
                        Aufteilung nach Tipp-Kategorie (Kosten)
                    </div>
                </div>

                <span
                    class="shrink-0 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80"
                >
                    {{ stats.total_tips }} Tipps
                </span>
            </div>

            <div class="my-4 h-px bg-white/10"></div>

            <!-- Quick cards -->
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs text-white/60">0,00 € · Exakt</div>
                    <div class="mt-1 text-lg font-semibold text-white">
                        {{ stats.exact_bets }}
                        <span class="text-sm font-semibold text-white/60">
                            · {{ percent(stats.exact_bets, stats.total_tips) }}
                        </span>
                    </div>
                    <div class="mt-1 text-xs text-white/50">
                        exakte Tipps (0,00 €)
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs text-white/60">0,30 € · Tendenz</div>
                    <div class="mt-1 text-lg font-semibold text-white">
                        {{ stats.tendency_bets }}
                        <span class="text-sm font-semibold text-white/60">
                            ·
                            {{ percent(stats.tendency_bets, stats.total_tips) }}
                        </span>
                    </div>
                    <div class="mt-1 text-xs text-white/50">
                        Tendenz-Tipps (0,30 €)
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs text-white/60">0,60 € · Winner</div>
                    <div class="mt-1 text-lg font-semibold text-white">
                        {{ stats.winner_bets }}
                        <span class="text-sm font-semibold text-white/60">
                            · {{ percent(stats.winner_bets, stats.total_tips) }}
                        </span>
                    </div>
                    <div class="mt-1 text-xs text-white/50">
                        Sieger-Tipps (0,60 €)
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-xs text-white/60">1,00 € · Falsch</div>
                    <div class="mt-1 text-lg font-semibold text-white">
                        {{ stats.wrong_bets }}
                        <span class="text-sm font-semibold text-white/60">
                            · {{ percent(stats.wrong_bets, stats.total_tips) }}
                        </span>
                    </div>
                    <div class="mt-1 text-xs text-white/50">
                        falsche Tipps (1,00 €)
                    </div>
                </div>
            </div>

            <!-- little breakdown bar -->
            <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-white">
                        Verteilung
                    </div>
                    <div class="text-xs text-white/60">
                        Stand: {{ lastUpdatedLabel }}
                    </div>
                </div>

                <div
                    class="mt-3 h-3 w-full overflow-hidden rounded-full border border-white/10 bg-white/5"
                >
                    <div class="flex h-full w-full">
                        <div
                            class="h-full bg-bordeaux-400/40"
                            :style="{
                                width: barWidth(
                                    stats.exact_bets,
                                    stats.total_tips,
                                ),
                            }"
                            title="Exakt (0,00 €)"
                        ></div>
                        <div
                            class="h-full bg-sky-400/35"
                            :style="{
                                width: barWidth(
                                    stats.tendency_bets,
                                    stats.total_tips,
                                ),
                            }"
                            title="Tendenz (0,30 €)"
                        ></div>
                        <div
                            class="h-full bg-emerald-400/40"
                            :style="{
                                width: barWidth(
                                    stats.winner_bets,
                                    stats.total_tips,
                                ),
                            }"
                            title="Winner (0,60 €)"
                        ></div>
                        <div
                            class="h-full bg-white/15"
                            :style="{
                                width: barWidth(
                                    stats.wrong_bets,
                                    stats.total_tips,
                                ),
                            }"
                            title="Falsch (1,00 €)"
                        ></div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-white/80"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-bordeaux-400/60"
                        ></span>
                        0,00 € Exakt
                    </span>
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-white/80"
                    >
                        <span class="h-2 w-2 rounded-full bg-sky-400/60"></span>
                        0,30 € Tendenz
                    </span>
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-white/80"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-400/60"
                        ></span>
                        0,60 € Winner
                    </span>
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-white/80"
                    >
                        <span class="h-2 w-2 rounded-full bg-white/30"></span>
                        1,00 € Falsch
                    </span>
                </div>
            </div>

            <!-- CTA -->
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" class="btn-secondary" @click="goToMyBets">
                    Meine Tipps ansehen
                </button>
                <button
                    type="button"
                    class="btn-secondary"
                    @click="openStatsDetails"
                >
                    Statistik-Details
                </button>
            </div>
        </section>

        <!-- =================== ACCOUNT / SETTINGS =================== -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-white">Account</div>
                    <div class="text-xs text-white/60">
                        Profil & Benachrichtigungen
                    </div>
                </div>
            </div>

            <div class="my-4 h-px bg-white/10"></div>

            <div class="space-y-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-white">
                                Benachrichtigungen
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                Erinnerungen vor Spielstart, Ergebnis-Info etc.
                            </div>
                        </div>

                        <button type="button" class="btn-secondary">
                            Verwalten
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-white">
                                Name & Avatar
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                Wie du in Ranglisten angezeigt wirst
                            </div>
                        </div>

                        <button type="button" class="btn-secondary">
                            Bearbeiten
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-white">
                                Abmelden
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                Session beenden
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn-danger"
                            @click="logout"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================== STATISTIK DETAILS (Bottom Sheet Modal) =================== -->
        <Teleport to="body">
            <div
                v-if="showStatsDetails"
                class="fixed inset-0 z-[60] flex items-end justify-center"
                aria-modal="true"
                role="dialog"
            >
                <!-- Backdrop -->
                <button
                    type="button"
                    class="absolute inset-0 bg-black/60"
                    aria-label="Schließen"
                    @click="closeStatsDetails"
                ></button>

                <!-- Panel -->
                <div
                    class="relative w-full max-w-lg rounded-t-[28px] border border-white/10 bg-navy-900/95 p-4 backdrop-blur-md"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-white">
                                Statistik-Details
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                Saison {{ seasonLabel }} · Stand:
                                {{ lastUpdatedLabel }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white/80 hover:bg-white/10"
                            @click="closeStatsDetails"
                        >
                            Schließen
                        </button>
                    </div>

                    <div class="my-4 h-px bg-white/10"></div>

                    <div class="grid grid-cols-2 gap-3">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <div class="text-xs text-white/60">Dein Rang</div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                #{{ stats.position || "—" }}
                            </div>
                            <div class="mt-1 text-xs text-white/50">
                                in der aktuellen Saison
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <div class="text-xs text-white/60">
                                Gesamtkosten
                            </div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                {{ formatMoney(stats.total_cost) }}
                            </div>
                            <div class="mt-1 text-xs text-white/50">
                                Summe aller Tipps
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <div class="text-xs text-white/60">Ø pro Tipp</div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                {{ formatMoney(avgPerTip) }}
                            </div>
                            <div class="mt-1 text-xs text-white/50">
                                Durchschnittskosten
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <div class="text-xs text-white/60">
                                Tipps gesamt
                            </div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                {{ stats.total_tips }}
                            </div>
                            <div class="mt-1 text-xs text-white/50">
                                finished Spiele
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-semibold text-white">
                                Kategorien (Kosten)
                            </div>
                            <button
                                type="button"
                                class="text-xs font-semibold text-white/70 hover:text-white"
                                @click="goToLeaderboard"
                            >
                                Zur Rangliste →
                            </button>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div
                                class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2"
                            >
                                <span class="text-white/70">0,00 € Exakt</span>
                                <span class="font-semibold text-white">{{
                                    stats.exact_bets
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2"
                            >
                                <span class="text-white/70"
                                    >0,30 € Tendenz</span
                                >
                                <span class="font-semibold text-white">{{
                                    stats.tendency_bets
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2"
                            >
                                <span class="text-white/70">0,60 € Winner</span>
                                <span class="font-semibold text-white">{{
                                    stats.winner_bets
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2"
                            >
                                <span class="text-white/70">1,00 € Falsch</span>
                                <span class="font-semibold text-white">{{
                                    stats.wrong_bets
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="goToMyBets"
                        >
                            Meine Tipps ansehen
                        </button>
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="goToLeaderboard"
                        >
                            Rangliste öffnen
                        </button>
                        <button
                            type="button"
                            class="btn-primary"
                            @click="closeStatsDetails"
                        >
                            Fertig
                        </button>
                    </div>

                    <!-- Safe area / spacing for mobile -->
                    <div class="h-2"></div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { authApi, leaderboardApi, type UserStats } from "@/services/api";

/**
 * Payments sind aktuell noch Mock — sobald du dein Payments-Backend hast,
 * kannst du das genauso wie stats unten laden.
 */

const router = useRouter();

const seasonLabel = ref("—");

/* ======= Payments / Money (Mock) ======= */
const payments = ref({
    paid_total: 25.0,
    open_total: 10.0,
    balance: -10.0,
});

/* ======= Stats (aus /stats) ======= */
const stats = ref({
    total_tips: 0,
    exact_bets: 0,
    tendency_bets: 0,
    winner_bets: 0,
    wrong_bets: 0,
    total_cost: 0,
    position: 0,
    generated_at: new Date().toISOString(),
});

const showStatsDetails = ref(false);

onMounted(async () => {
    try {
        const res = await leaderboardApi.getUserStats();
        const data: UserStats = res.data;

        seasonLabel.value = data.season?.name ?? "—";

        stats.value = {
            total_tips: data.bet_count ?? 0,
            exact_bets: data.exact_bets ?? 0,
            tendency_bets: data.tendency_bets ?? 0,
            winner_bets: data.winner_bets ?? 0,
            wrong_bets: data.wrong_bets ?? 0,
            total_cost: data.total_cost ?? 0,
            position: data.position ?? 0,
            generated_at: new Date().toISOString(),
        };
    } catch (e) {
        // optional: später Toast/Notice
        // console.error(e);
    }
});

function openStatsDetails() {
    showStatsDetails.value = true;
}

function closeStatsDetails() {
    showStatsDetails.value = false;
}

function goToMyBets() {
    router.push("/bets");
}

function goToLeaderboard() {
    router.push("/leaderboard");
    showStatsDetails.value = false;
}

async function logout() {
    try {
        await authApi.logout();
    } finally {
        localStorage.removeItem("auth_token");
        if (window.location.pathname !== "/login") {
            window.location.href = "/login";
        }
    }
}

const avgPerTip = computed(() => {
    if (!stats.value.total_tips) return 0;
    return Number((stats.value.total_cost / stats.value.total_tips).toFixed(2));
});

const lastUpdatedLabel = computed(() => {
    const iso = stats.value.generated_at;
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

/* ======= Helpers ======= */
function formatMoney(value: number | null | undefined) {
    if (value === null || value === undefined) return "—";
    const n = Number(value);
    if (Number.isNaN(n)) return "—";
    return `${n.toFixed(2).replace(".", ",")} €`;
}

function percent(part: number, total: number) {
    if (!total) return "0%";
    const p = Math.round((part / total) * 100);
    return `${p}%`;
}

function barWidth(part: number, total: number) {
    if (!total) return "0%";
    const p = Math.max(0, Math.min(100, (part / total) * 100));
    return `${p}%`;
}
</script>
