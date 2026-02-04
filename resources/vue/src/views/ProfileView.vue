<template>
    <div class="space-y-4">
        <!-- =================== ADMIN DEBUG (nur wenn Backend es erlaubt) =================== -->
        <section
            v-if="pwaDebug"
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-white">
                        PWA / Push Debug
                    </div>
                    <div class="text-xs text-white/60">
                        Admin Tools (serverseitig aktiviert)
                    </div>
                </div>

                <span
                    class="shrink-0 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80"
                >
                    {{ envLabel }}
                </span>
            </div>

            <div class="my-4 h-px bg-white/10"></div>

            <div class="space-y-3 text-sm">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="grid grid-cols-2 gap-2 text-xs text-white/70">
                        <div>installed/standalone</div>
                        <div class="text-right text-white/90">
                            {{ isInstalled ? "yes" : "no" }}
                        </div>

                        <div>service worker</div>
                        <div class="text-right text-white/90">
                            {{ swStatus }}
                        </div>

                        <div>notification permission</div>
                        <div class="text-right text-white/90">
                            {{ notifPermission }}
                        </div>

                        <div>push test enabled</div>
                        <div class="text-right text-white/90">
                            {{ pushTestEnabled ? "yes" : "no" }}
                        </div>

                        <div>vapid key</div>
                        <div class="text-right text-white/90">
                            {{ vapidPublicKey ? "present" : "missing" }}
                        </div>

                        <div>sw version (from SW)</div>
                        <div class="text-right text-white/90">
                            {{ swClientStatus.swVersion ?? "—" }}
                        </div>

                        <div>controller scriptURL</div>
                        <div class="text-right text-white/90">
                            {{ swClientStatus.controllerScriptUrl ?? "—" }}
                        </div>

                        <div>controlled by SW</div>
                        <div class="text-right text-white/90">
                            {{ controlledBySw ? "yes" : "no" }}
                        </div>

                        <div>controller state</div>
                        <div class="text-right text-white/90">
                            {{ swClientStatus.controllerState ?? "—" }}
                        </div>

                        <div>reg active</div>
                        <div class="text-right text-white/90">
                            {{ swClientStatus.regActiveState ?? "—" }}
                        </div>

                        <div>reg waiting</div>
                        <div class="text-right text-white/90">
                            {{ swClientStatus.regWaitingState ?? "—" }}
                        </div>

                        <div>reg installing</div>
                        <div class="text-right text-white/90">
                            {{ swClientStatus.regInstallingState ?? "—" }}
                        </div>

                        <div>reg active scriptURL</div>
                        <div class="text-right text-white/90">
                            {{ consoleLike?.activeScriptUrl ?? "—" }}
                        </div>

                        <div>reg scope</div>
                        <div class="text-right text-white/90">
                            {{ consoleLike?.regScope ?? "—" }}
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="reloadApp"
                        >
                            Reload App
                        </button>

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="refreshAllDebug"
                        >
                            Refresh
                        </button>

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="requestNotificationPermission"
                        >
                            Permission anfragen
                        </button>

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="subscribePush"
                        >
                            Push Subscribe
                        </button>

                        <button
                            v-if="pushTestEnabled"
                            type="button"
                            class="btn-secondary"
                            @click="sendTestPush"
                        >
                            Test Push (später)
                        </button>

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="pingServiceWorker"
                        >
                            Ping SW
                        </button>

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="requestSwStatus"
                        >
                            SW Status
                        </button>

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="updateServiceWorker"
                        >
                            SW Update
                        </button>

                        <button
                            type="button"
                            class="btn-secondary"
                            @click="unregisterServiceWorker"
                        >
                            SW Unregister
                        </button>
                    </div>

                    <div v-if="pushInfo" class="mt-3 text-xs text-emerald-200">
                        {{ pushInfo }}
                    </div>
                    <div v-if="pushError" class="mt-3 text-xs text-rose-200">
                        {{ pushError }}
                    </div>
                </div>

                <div
                    v-if="pushSubscriptionJson"
                    class="rounded-2xl border border-white/10 bg-white/5 p-4"
                >
                    <div class="text-xs font-semibold text-white/80 mb-2">
                        Push Subscription
                    </div>
                    <pre
                        class="text-[11px] leading-snug text-white/70 whitespace-pre-wrap break-all"
                        >{{ pushSubscriptionJson }}</pre
                    >

                    <div
                        v-if="swClientStatus.lastEvent"
                        class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4"
                    >
                        <div class="text-xs font-semibold text-white/80 mb-2">
                            SW Debug (last event)
                        </div>
                        <pre
                            class="text-[11px] leading-snug text-white/70 whitespace-pre-wrap break-all"
                            >{{
                                JSON.stringify(
                                    swClientStatus.lastEvent,
                                    null,
                                    2,
                                )
                            }}</pre
                        >
                    </div>

                    <div
                        v-if="swClientStatus.log.length"
                        class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4"
                    >
                        <div class="text-xs font-semibold text-white/80 mb-2">
                            SW Debug Log (latest first)
                        </div>
                        <pre
                            class="text-[11px] leading-snug text-white/70 whitespace-pre-wrap break-all"
                            >{{
                                JSON.stringify(swClientStatus.log, null, 2)
                            }}</pre
                        >
                    </div>
                </div>
            </div>
        </section>

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
import {
    authApi,
    leaderboardApi,
    appConfigApi,
    pushApi,
    type UserStats,
    type AppConfigResponse,
} from "@/services/api";
import { usePwaInstall } from "@/pwa/usePwaInstall";
import { urlBase64ToUint8Array } from "@/pwa/push";

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

/* ======= BACKEND APP CONFIG (Admin toggles) ======= */
const appConfig = ref<AppConfigResponse | null>(null);

async function loadAppConfig() {
    try {
        const res = await appConfigApi.get();
        appConfig.value = res.data;
    } catch {
        appConfig.value = null;
    }
}

const pwaDebug = computed(() => appConfig.value?.pwa?.debug === true);
const pushTestEnabled = computed(
    () => appConfig.value?.pwa?.push_test === true,
);
const vapidPublicKey = computed(
    () => appConfig.value?.pwa?.vapid_public_key ?? null,
);
const appEnvLabel = computed(() => appConfig.value?.pwa?.env ?? "");
const envLabel = computed(() => appEnvLabel.value || "—");

/* ======= UI helpers ======= */
const pushSending = ref(false);
const pushInfo = ref<string>("");
const pushError = ref<string>("");

function setInfo(msg: string) {
    pushInfo.value = msg;
    window.setTimeout(() => {
        if (pushInfo.value === msg) pushInfo.value = "";
    }, 4000);
}

function setError(msg: string) {
    pushError.value = msg;
    window.setTimeout(() => {
        if (pushError.value === msg) pushError.value = "";
    }, 6000);
}

function reloadApp() {
    window.location.reload();
}

/* ======= PUSH / PWA DEBUG VALUES ======= */
const notifPermission = ref<NotificationPermission | "unsupported">(
    "unsupported",
);
const swStatus = ref<"unsupported" | "no-sw" | "ready" | "installing">(
    "unsupported",
);
const swScope = ref<string>("—");
const pushSubscriptionJson = ref<string>("");

/* ======= SW Debug plumbing ======= */
type SwClientStatus = {
    controllerScriptUrl: string | null;
    controllerState: string | null;

    swVersion: string | null;
    swScope: string | null;
    swStatusAt: string | null;

    regScope: string | null;
    regActiveScriptUrl: string | null;
    regActiveState: string | null;
    regWaitingScriptUrl: string | null;
    regWaitingState: string | null;
    regInstallingScriptUrl: string | null;
    regInstallingState: string | null;

    lastEvent: any | null;
    log: any[];
};

const swClientStatus = ref<SwClientStatus>({
    controllerScriptUrl: null,
    controllerState: null,

    swVersion: null,
    swScope: null,
    swStatusAt: null,

    regScope: null,
    regActiveScriptUrl: null,
    regActiveState: null,
    regWaitingScriptUrl: null,
    regWaitingState: null,
    regInstallingScriptUrl: null,
    regInstallingState: null,

    lastEvent: null,
    log: [],
});

const consoleLike = ref<{
    regScope: string | null;
    activeScriptUrl: string | null;
    waitingScriptUrl: string | null;
    installingScriptUrl: string | null;
} | null>(null);

function pushLog(msg: any) {
    swClientStatus.value.lastEvent = msg;
    swClientStatus.value.log = [msg, ...swClientStatus.value.log].slice(0, 30);
}

const controlledBySw = computed(() => {
    return !!navigator.serviceWorker?.controller;
});

function refreshControllerInfo() {
    const controller = navigator.serviceWorker?.controller as any;
    swClientStatus.value.controllerScriptUrl = controller?.scriptURL ?? null;
    swClientStatus.value.controllerState = controller?.state ?? null;
}

async function refreshRegistrationInfo() {
    if (!("serviceWorker" in navigator)) return;

    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) {
        swClientStatus.value.regScope = null;
        swClientStatus.value.regActiveScriptUrl = null;
        swClientStatus.value.regActiveState = null;
        swClientStatus.value.regWaitingScriptUrl = null;
        swClientStatus.value.regWaitingState = null;
        swClientStatus.value.regInstallingScriptUrl = null;
        swClientStatus.value.regInstallingState = null;
        consoleLike.value = null;
        return;
    }

    swClientStatus.value.regScope = reg.scope ?? null;

    swClientStatus.value.regActiveScriptUrl =
        (reg.active as any)?.scriptURL ?? null;
    swClientStatus.value.regActiveState = (reg.active as any)?.state ?? null;

    swClientStatus.value.regWaitingScriptUrl =
        (reg.waiting as any)?.scriptURL ?? null;
    swClientStatus.value.regWaitingState = (reg.waiting as any)?.state ?? null;

    swClientStatus.value.regInstallingScriptUrl =
        (reg.installing as any)?.scriptURL ?? null;
    swClientStatus.value.regInstallingState =
        (reg.installing as any)?.state ?? null;

    // "Console light"
    consoleLike.value = {
        regScope: reg.scope ?? null,
        activeScriptUrl: (reg.active as any)?.scriptURL ?? null,
        waitingScriptUrl: (reg.waiting as any)?.scriptURL ?? null,
        installingScriptUrl: (reg.installing as any)?.scriptURL ?? null,
    };
}

function attachSwMessageListenerOnce() {
    if (!("serviceWorker" in navigator)) return;

    const key = "__blueliner_sw_msg_listener";
    if ((window as any)[key]) return;
    (window as any)[key] = true;

    navigator.serviceWorker.addEventListener("message", (event) => {
        const msg = event.data;
        if (!msg?.type) return;

        if (msg.type === "sw-status") {
            swClientStatus.value.swVersion = msg.v ?? null;
            swClientStatus.value.swScope = msg.registrationScope ?? null;
            swClientStatus.value.swStatusAt = msg.at ?? null;
            pushLog(msg);

            refreshControllerInfo();
            refreshRegistrationInfo();
            return;
        }

        if (msg.type === "pong") {
            swClientStatus.value.swVersion =
                msg.v ?? swClientStatus.value.swVersion;
            swClientStatus.value.swStatusAt =
                msg.at ?? swClientStatus.value.swStatusAt;
            pushLog(msg);

            refreshControllerInfo();
            refreshRegistrationInfo();
            return;
        }

        if (
            msg.type === "push-received" ||
            msg.type === "notification-shown" ||
            msg.type === "notification-error" ||
            msg.type === "ui-note"
        ) {
            if (msg.v) swClientStatus.value.swVersion = msg.v;
            pushLog(msg);
            return;
        }
    });
}

/**
 * Wichtig: sendet an controller, sonst fallback an reg.active/waiting/installing
 * (damit iOS ohne controller trotzdem Messages annimmt)
 */
async function postToAnyWorker(message: any) {
    if (!("serviceWorker" in navigator)) throw new Error("SW nicht verfügbar");

    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) throw new Error("Kein SW registriert");

    if (navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage(message);
        return;
    }

    const w = reg.active ?? reg.waiting ?? reg.installing;
    if (!w)
        throw new Error(
            "Keine SW Instanz (active/waiting/installing) vorhanden",
        );

    w.postMessage(message);

    pushLog({
        type: "ui-note",
        at: new Date().toISOString(),
        message:
            "Kein controller (iOS). Message an reg.active/waiting/installing gesendet.",
    });
}

async function requestSwStatus() {
    pushError.value = "";
    try {
        await postToAnyWorker({ type: "get-status" });
    } catch (e: any) {
        pushError.value = e?.message ?? String(e);
    }
}

async function pingServiceWorker() {
    pushError.value = "";
    try {
        await postToAnyWorker({ type: "ping" });
    } catch (e: any) {
        pushError.value = e?.message ?? String(e);
    }
}

async function updateServiceWorker() {
    pushError.value = "";
    try {
        const reg = await navigator.serviceWorker.getRegistration();
        if (!reg) throw new Error("Kein Service Worker registriert");
        await reg.update();
        await refreshRegistrationInfo();
        setInfo("SW update() ausgelöst");
    } catch (e: any) {
        pushError.value = e?.message ?? String(e);
    }
}

async function unregisterServiceWorker() {
    pushError.value = "";
    try {
        const reg = await navigator.serviceWorker.getRegistration();
        if (!reg) throw new Error("Kein Service Worker registriert");
        await reg.unregister();
        setInfo("SW unregistered (Reload)");
        await refreshRegistrationInfo();
        refreshControllerInfo();
    } catch (e: any) {
        pushError.value = e?.message ?? String(e);
    }
}

async function refreshPwaDebugStatus() {
    pushError.value = "";

    if (!("Notification" in window)) {
        notifPermission.value = "unsupported";
    } else {
        notifPermission.value = Notification.permission;
    }

    if (!("serviceWorker" in navigator)) {
        swStatus.value = "unsupported";
        swScope.value = "—";
        return;
    }

    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) {
        swStatus.value = "no-sw";
        swScope.value = "—";
        return;
    }

    swScope.value = reg.scope || "—";
    if (reg.installing) swStatus.value = "installing";
    else swStatus.value = "ready";
}

async function requestNotificationPermission() {
    pushError.value = "";

    if (!("Notification" in window)) {
        pushError.value = "Notification API nicht verfügbar";
        return;
    }

    const res = await Notification.requestPermission();
    notifPermission.value = res;
}

/**
 * OPTIONAL (aber empfehlenswert fürs Debuggen):
 * Wenn du Probleme hast, mach unsubscribe -> subscribe,
 * damit du wirklich mit neuen Parametern arbeitest.
 */
const forceResubscribe = true;

async function subscribePush() {
    pushError.value = "";
    pushSubscriptionJson.value = "";

    try {
        if (!pwaDebug.value)
            throw new Error("Debug ist serverseitig nicht aktiv (kein Admin?)");
        if (!("serviceWorker" in navigator))
            throw new Error("Service Worker nicht verfügbar");
        if (!("PushManager" in window))
            throw new Error("PushManager nicht verfügbar");
        if (!("Notification" in window))
            throw new Error("Notification API nicht verfügbar");

        const key = vapidPublicKey.value;
        if (!key)
            throw new Error("VAPID public key fehlt (Backend liefert null)");

        const perm = await Notification.requestPermission();
        notifPermission.value = perm;
        if (perm !== "granted")
            throw new Error(`Notification Permission ist ${perm}`);

        const reg = await navigator.serviceWorker.getRegistration();
        if (!reg) throw new Error("Kein Service Worker registriert");

        // optional: hart neu abonnieren
        if (forceResubscribe) {
            const existing = await reg.pushManager.getSubscription();
            if (existing) {
                try {
                    await existing.unsubscribe();
                } catch {
                    // ignore
                }
            }
        }

        let sub = await reg.pushManager.getSubscription();
        if (!sub) {
            sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(key),
            });
        }

        const json = sub.toJSON();
        if (!json.keys?.p256dh || !json.keys?.auth) {
            throw new Error("Subscription keys fehlen (p256dh/auth)");
        }

        await pushApi.subscribe({
            endpoint: sub.endpoint,
            keys: json.keys,
            // ✅ iOS/Safari: sehr oft nötig
            contentEncoding: "aes128gcm",
            device: /iphone|ipad|ipod/i.test(navigator.userAgent)
                ? "ios"
                : /android/i.test(navigator.userAgent)
                  ? "android"
                  : "desktop",
        });

        pushSubscriptionJson.value = JSON.stringify(json, null, 2);

        await refreshPwaDebugStatus();
        await refreshRegistrationInfo();
        refreshControllerInfo();
        await requestSwStatus();
        setInfo("Subscribed (aes128gcm) ✅");
    } catch (e: any) {
        pushError.value = e?.message ?? String(e);
    }
}

async function sendTestPush() {
    pushError.value = "";
    pushInfo.value = "";

    try {
        if (!pwaDebug.value) {
            setError("Debug ist serverseitig nicht aktiv (kein Admin?)");
            return;
        }
        if (!pushTestEnabled.value) {
            setError("push_test ist serverseitig nicht aktiviert");
            return;
        }

        pushSending.value = true;
        setInfo("Sende Test Push…");

        const res = await pushApi.test({
            title: "BLUELINER BERLIN",
            body: "Test Push ✅",
            url: "/profile",
        });

        const status = (res as any)?.status ?? (res as any)?.statusCode ?? "ok";
        setInfo(`Test Push Request OK (${status})`);
    } catch (e: any) {
        const msg =
            e?.response?.data?.message ??
            e?.message ??
            "Unbekannter Fehler beim Test Push";
        setError(`Test Push failed: ${msg}`);
    } finally {
        pushSending.value = false;
    }
}

async function refreshAllDebug() {
    await refreshPwaDebugStatus();
    refreshControllerInfo();
    await refreshRegistrationInfo();
    await requestSwStatus();
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

    await loadAppConfig();
    await refreshPwaDebugStatus();

    // SW debug init
    attachSwMessageListenerOnce();
    refreshControllerInfo();
    await refreshRegistrationInfo();

    // sofort Status anfordern (wenn SW antwortet)
    await requestSwStatus();

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
