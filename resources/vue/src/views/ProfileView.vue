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
                        <button type="button" class="btn-primary" disabled>
                            Einzahlung hinzufügen
                        </button>
                        <button type="button" class="btn-secondary" disabled>
                            Zahlungen ansehen
                        </button>
                    </div>

                    <div class="mt-2 text-xs text-white/50">
                        Einzahlungen kann nur der Admin hinzufügen.
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
                <div
                    v-if="!isInstalled"
                    class="rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-white">
                                App installieren
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                {{
                                    canInstall
                                        ? "Direkt installieren (Chrome/Android/Desktop)"
                                        : "Falls kein Button erscheint: Browser-Menü → Installieren"
                                }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn-primary"
                            @click="handleInstallClick"
                        >
                            Installieren
                        </button>
                    </div>
                </div>

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

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="router.push('/profile/notifications')"
                        >
                            Verwalten
                        </button>
                    </div>

                    <div class="mt-2 text-xs text-white/50">
                        Verwaltung kommt als eigene Seite.
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

                        <button type="button" class="btn-secondary" disabled>
                            Bearbeiten
                        </button>
                    </div>

                    <div class="mt-2 text-xs text-white/50">
                        Kommt später (Profil-Editing).
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

        <!-- =================== INSTALL HELP (Bottom Sheet) =================== -->
        <Teleport to="body">
            <div
                v-if="showInstallHelp"
                class="fixed inset-0 z-[70] flex items-end justify-center"
                aria-modal="true"
                role="dialog"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-black/60"
                    aria-label="Schließen"
                    @click="closeInstallHelp"
                ></button>

                <div
                    class="relative w-full max-w-lg rounded-t-[28px] border border-white/10 bg-navy-900/95 p-4 backdrop-blur-md"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-white">
                                App installieren
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                {{ installHelpSubtitle }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white/80 hover:bg-white/10"
                            @click="closeInstallHelp"
                        >
                            Schließen
                        </button>
                    </div>

                    <div class="my-4 h-px bg-white/10"></div>

                    <div class="space-y-3 text-sm text-white/80">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <div class="font-semibold text-white">
                                {{ installHelpTitle }}
                            </div>
                            <div class="mt-2 whitespace-pre-line">
                                {{ installHelpBody }}
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <div class="font-semibold text-white">Tipp</div>
                            <div class="mt-2 whitespace-pre-line">
                                Wenn du den Install-Dialog schon mal weggeklickt
                                hast, kann Chrome ihn für eine Weile nicht mehr
                                anbieten. Dann klappt es meistens nach einem
                                Neustart des Browsers oder nach ein paar Minuten
                                erneut.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn-primary"
                            @click="closeInstallHelp"
                        >
                            Verstanden
                        </button>
                    </div>

                    <div class="h-2"></div>
                </div>
            </div>
        </Teleport>

        <!-- =================== STATISTIK DETAILS (Bottom Sheet Modal) =================== -->
        <Teleport to="body">
            <div
                v-if="showStatsDetails"
                class="fixed inset-0 z-[60] flex items-end justify-center"
                aria-modal="true"
                role="dialog"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-black/60"
                    aria-label="Schließen"
                    @click="closeStatsDetails"
                ></button>

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
import { usePwaInstall } from "@/pwa/usePwaInstall";

const { canInstall, init, triggerInstall } = usePwaInstall();
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

/* ======= PWA INSTALL HELP ======= */
const showInstallHelp = ref(false);
const isInstalled = ref(false);

function detectInstalled() {
    const iosStandalone = (window.navigator as any).standalone === true;
    const mql =
        window.matchMedia && window.matchMedia("(display-mode: standalone)");
    const standalone = mql?.matches ?? false;
    isInstalled.value = iosStandalone || standalone;
}

function openInstallHelp() {
    showInstallHelp.value = true;
}
function closeInstallHelp() {
    showInstallHelp.value = false;
}

function isIOS() {
    const ua = navigator.userAgent.toLowerCase();
    return /iphone|ipad|ipod/.test(ua);
}

function isAndroid() {
    const ua = navigator.userAgent.toLowerCase();
    return /android/.test(ua);
}

const installHelpTitle = computed(() => {
    if (isIOS()) return "iPhone/iPad (Safari)";
    if (isAndroid()) return "Android (Chrome)";
    return "Desktop (Chrome/Edge)";
});

const installHelpSubtitle = computed(() => {
    return "Falls der automatische Install-Dialog nicht erscheint, so geht’s trotzdem:";
});

const installHelpBody = computed(() => {
    if (isIOS()) {
        return `1) Öffne die Seite in Safari
2) Tippe unten auf „Teilen“
3) Wähle „Zum Home-Bildschirm“
4) Bestätigen → App-Icon erscheint`;
    }

    return `1) Öffne das Browser-Menü (⋮ oben rechts)
2) Tippe „App installieren“ / „Zum Startbildschirm hinzufügen“
3) Bestätigen`;
});

async function handleInstallClick() {
    if (canInstall.value) {
        await triggerInstall();
        setTimeout(detectInstalled, 500);
        return;
    }
    openInstallHelp();
}

/* ======= Navigation / Actions ======= */
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

/* ======= init ======= */
onMounted(async () => {
    init();
    detectInstalled();

    window.addEventListener("appinstalled", () => {
        detectInstalled();
    });

    // load stats
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
    } catch {
        // optional
    }
});
</script>

<style>
/* mobile tap feedback */
.btn-primary,
.btn-secondary,
.btn-danger {
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    user-select: none;
    transition:
        transform 90ms ease,
        filter 120ms ease,
        opacity 120ms ease;
}

.btn-primary:active,
.btn-secondary:active,
.btn-danger:active {
    transform: translateY(1px) scale(0.99);
    filter: brightness(1.08);
}

/* disabled state (also for "loading") */
.btn-primary[disabled],
.btn-secondary[disabled],
.btn-danger[disabled] {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
    filter: none;
}

/* focus visible for keyboard (desktop) */
.btn-primary:focus-visible,
.btn-secondary:focus-visible,
.btn-danger:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.35);
    outline-offset: 2px;
    border-radius: 9999px; /* falls pill buttons */
}
</style>
