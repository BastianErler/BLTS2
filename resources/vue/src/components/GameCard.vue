<template>
    <BaseCard
        :hoverable="clickable"
        variant="glass"
        class="select-none relative"
        @click="handleClick"
    >
        <!-- Admin edit (icon button) -->
        <button
            v-if="showAdminGear"
            type="button"
            class="admin-edit-btn"
            aria-label="Spiel bearbeiten"
            title="Spiel bearbeiten"
            @click.stop="goToAdminEdit"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                class="h-5 w-5"
                aria-hidden="true"
            >
                <path
                    d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005Z"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </button>

        <!-- Contrast layer -->
        <div class="space-y-3 rounded-2xl bg-black/25 ring-1 ring-white/10 p-3">
            <!-- Top row -->
            <div class="flex items-center justify-between">
                <BaseBadge :variant="statusVariant" size="sm">
                    {{ statusLabel }}
                </BaseBadge>

                <div class="flex items-center gap-2">
                    <span
                        v-if="game.is_playoff"
                        class="text-[11px] font-bold uppercase tracking-wide text-white/90 rounded-full px-2 py-1 bg-white/10 border border-white/15"
                    >
                        Playoff
                    </span>

                    <span
                        v-if="game.is_derby"
                        class="text-[11px] font-bold uppercase tracking-wide text-bordeaux-100 rounded-full px-2 py-1 bg-bordeaux-800/25 border border-bordeaux-800/40"
                    >
                        Derby
                    </span>
                </div>
            </div>

            <!-- Teams & center -->
            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                <!-- LEFT TEAM -->
                <div class="flex flex-col items-center text-center">
                    <div
                        class="h-20 w-20 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center overflow-hidden"
                    >
                        <img
                            :src="leftLogo"
                            :alt="leftAlt"
                            class="h-16 w-16 object-contain"
                            loading="lazy"
                        />
                    </div>

                    <div class="mt-2 text-sm font-semibold text-white/90">
                        {{ leftLabel }}
                    </div>

                    <div
                        v-if="isFinished"
                        class="mt-2 text-3xl leading-none font-extrabold text-white"
                    >
                        {{ leftGoals }}
                    </div>
                </div>

                <!-- Center -->
                <div class="text-center px-1">
                    <div class="space-y-1">
                        <div class="text-sm font-semibold text-white/90">
                            {{ gameDate }}
                        </div>
                        <div class="text-xs text-white/70">
                            {{ gameTime }}
                        </div>

                        <div
                            v-if="showCountdown"
                            class="mt-1 text-[11px] text-white/70"
                        >
                            <CountdownTimer
                                :target-date="game.bet_deadline_at"
                            />
                        </div>
                    </div>

                    <div
                        class="mt-2 text-[11px] text-white/60"
                        v-if="game.match_number"
                    >
                        Spiel {{ game.match_number }}
                    </div>
                </div>

                <!-- RIGHT TEAM -->
                <div class="flex flex-col items-center text-center min-w-0">
                    <div
                        class="h-20 w-20 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center overflow-hidden"
                    >
                        <img
                            :src="rightLogo"
                            :alt="rightAlt"
                            class="h-16 w-16 object-contain"
                            loading="lazy"
                        />
                    </div>

                    <div
                        class="mt-2 text-sm font-semibold text-white/90 truncate w-full"
                    >
                        {{ rightLabel }}
                    </div>

                    <div
                        v-if="isFinished"
                        class="mt-2 text-3xl leading-none font-extrabold text-white"
                    >
                        {{ rightGoals }}
                    </div>
                </div>
            </div>

            <!-- Bet info -->
            <div v-if="userBet" class="pt-3 border-t border-white/10">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-white/70">Dein Tipp</span>

                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-white">
                            {{ displayedUserBetLeftGoals }} :
                            {{ displayedUserBetRightGoals }}
                        </span>

                        <BaseBadge
                            v-if="userBet.joker_type"
                            variant="primary"
                            size="sm"
                        >
                            Joker
                        </BaseBadge>
                    </div>
                </div>

                <div
                    v-if="
                        isFinished &&
                        userBet.final_price !== null &&
                        userBet.final_price !== undefined
                    "
                    class="mt-2 text-right"
                >
                    <span :class="priceColorClass">
                        {{ formatPrice(userBet.final_price) }}
                    </span>
                </div>
            </div>

            <!-- CTA -->
            <slot name="action">
                <BaseButton
                    v-if="showBetButton"
                    variant="primary"
                    size="md"
                    full-width
                    @click.stop="$emit('bet', game)"
                >
                    {{ userBet ? "Tipp ändern" : "Jetzt tippen" }}
                </BaseButton>
            </slot>
        </div>
    </BaseCard>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import BaseCard from "@/components/BaseCard.vue";
import BaseBadge from "@/components/BaseBadge.vue";
import BaseButton from "@/components/BaseButton.vue";
import CountdownTimer from "@/components/CountdownTimer.vue";
import type { Bet, Game, Team } from "@/services/api";
import { getLogoUrl } from "@/services/api";

const router = useRouter();

const props = defineProps<{
    game: Game;
    userBet?: Bet | null;
    clickable?: boolean;
}>();

const emit = defineEmits<{
    (e: "click", game: Game): void;
    (e: "bet", game: Game): void;
}>();

const handleClick = () => {
    if (props.clickable) emit("click", props.game);
};

const isFinished = computed(() => props.game.status === "finished");

const showAdminGear = computed(() => {
    return localStorage.getItem("is_admin") === "1";
});

function goToAdminEdit() {
    router.push({
        name: "admin-game-edit",
        params: { id: props.game.id },
        query: {
            from: router.currentRoute.value.fullPath,
        },
    });
}

const statusVariant = computed(() => {
    const variants: Record<
        string,
        "info" | "success" | "default" | "warning" | "danger" | "primary"
    > = {
        scheduled: "info",
        live: "danger",
        finished: "default",
    };
    return variants[props.game.status] ?? "default";
});

const statusLabel = computed(() => {
    const labels: Record<string, string> = {
        scheduled: "Angesetzt",
        live: "Live",
        finished: "Beendet",
    };
    return labels[props.game.status] ?? props.game.status;
});

const gameDate = computed(() => {
    if (!props.game.kickoff_at) return "—";
    const date = new Date(props.game.kickoff_at);
    return date.toLocaleDateString("de-DE", {
        weekday: "short",
        day: "2-digit",
        month: "short",
    });
});

const gameTime = computed(() => {
    if (!props.game.kickoff_at) return "—";
    const date = new Date(props.game.kickoff_at);
    return date.toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
    });
});

const showCountdown = computed(() => {
    if (props.game.status !== "scheduled") return false;
    if (!props.game.can_bet) return false;
    if (!props.game.bet_deadline_at) return false;

    const deadline = new Date(props.game.bet_deadline_at).getTime();
    return Date.now() < deadline;
});

const showBetButton = computed(
    () => props.game.status === "scheduled" && props.game.can_bet,
);

const eisbaerenTeam = computed<Team>(() => ({
    id: 4,
    name: "Eisbären Berlin",
    short_name: "EBB",
    logo_url: "team_EBB.svg",
}));

const isHomeGame = computed(() => props.game.is_home);

const leftTeam = computed<Team>(() =>
    isHomeGame.value ? eisbaerenTeam.value : props.game.opponent,
);
const rightTeam = computed<Team>(() =>
    isHomeGame.value ? props.game.opponent : eisbaerenTeam.value,
);

const leftGoals = computed(() => {
    if (!isFinished.value) return null;
    return isHomeGame.value
        ? (props.game.eisbaeren_goals ?? 0)
        : (props.game.opponent_goals ?? 0);
});
const rightGoals = computed(() => {
    if (!isFinished.value) return null;
    return isHomeGame.value
        ? (props.game.opponent_goals ?? 0)
        : (props.game.eisbaeren_goals ?? 0);
});

const leftLabel = computed(
    () => leftTeam.value.short_name || leftTeam.value.name,
);
const rightLabel = computed(
    () => rightTeam.value.short_name || rightTeam.value.name,
);

const leftLogo = computed(() => getLogoUrl(leftTeam.value.logo_url));
const rightLogo = computed(() => getLogoUrl(rightTeam.value.logo_url));

const leftAlt = computed(() => leftTeam.value.name);
const rightAlt = computed(() => rightTeam.value.name);

const displayedUserBetLeftGoals = computed(() => {
    if (!props.userBet) return null;
    if (isHomeGame.value) return props.userBet.eisbaeren_goals;
    return props.userBet.opponent_goals;
});

const displayedUserBetRightGoals = computed(() => {
    if (!props.userBet) return null;
    if (isHomeGame.value) return props.userBet.opponent_goals;
    return props.userBet.eisbaeren_goals;
});

const priceColorClass = computed(() => {
    const priceRaw = props.userBet?.final_price;
    if (priceRaw === null || priceRaw === undefined) return "";

    const price = Number(priceRaw);
    if (Number.isNaN(price)) return "text-white/80 font-bold";

    if (price === 0) return "text-emerald-300 font-extrabold";
    if (price <= 0.5) return "text-amber-300 font-extrabold";
    return "text-rose-300 font-extrabold";
});

const formatPrice = (price: number) => {
    const value = Number(price);
    if (Number.isNaN(value)) return "";
    if (value === 0) return "0,00 € ✓";
    return `${value.toFixed(2).replace(".", ",")} €`;
};
</script>

<style scoped>
.admin-edit-btn {
    position: absolute;
    top: 0.75rem; /* 12px */
    right: 0.75rem; /* 12px */
    z-index: 10;

    height: 2.25rem; /* 36px */
    width: 2.25rem; /* 36px */
    border-radius: 9999px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    color: rgb(255 255 255 / 0.85);
    border: 1px solid rgb(255 255 255 / 0.14);
    background: linear-gradient(
        180deg,
        rgb(255 255 255 / 0.1),
        rgb(255 255 255 / 0.05)
    );
    backdrop-filter: blur(10px);

    box-shadow:
        0 10px 24px rgb(0 0 0 / 0.25),
        inset 0 1px 0 rgb(255 255 255 / 0.1);

    transition:
        transform 120ms ease,
        background-color 120ms ease,
        border-color 120ms ease,
        opacity 120ms ease;
}

.admin-edit-btn:hover {
    background: linear-gradient(
        180deg,
        rgb(255 255 255 / 0.14),
        rgb(255 255 255 / 0.08)
    );
    border-color: rgb(255 255 255 / 0.2);
}

.admin-edit-btn:active {
    transform: scale(0.96);
}

.admin-edit-btn:focus-visible {
    outline: none;
    box-shadow:
        0 10px 24px rgb(0 0 0 / 0.25),
        inset 0 1px 0 rgb(255 255 255 / 0.1),
        0 0 0 3px rgb(190 18 60 / 0.45);
}
</style>
