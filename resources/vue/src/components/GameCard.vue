<template>
    <BaseCard
        :hoverable="clickable"
        variant="glass"
        class="select-none"
        @click="handleClick"
    >
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
                        class="h-11 w-11 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center overflow-hidden"
                    >
                        <img
                            :src="leftLogo"
                            :alt="leftAlt"
                            class="h-8 w-8 object-contain"
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
                    <div
                        v-if="isFinished"
                        class="text-xl font-bold text-white/50"
                    >
                        :
                    </div>

                    <div v-else class="space-y-1">
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
                            <CountdownTimer :target-date="game.kickoff_at" />
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
                        class="h-11 w-11 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center overflow-hidden"
                    >
                        <img
                            :src="rightLogo"
                            :alt="rightAlt"
                            class="h-8 w-8 object-contain"
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
                            {{ userBet.eisbaeren_goals }} :
                            {{ userBet.opponent_goals }}
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
import BaseCard from "@/components/BaseCard.vue";
import BaseBadge from "@/components/BaseBadge.vue";
import BaseButton from "@/components/BaseButton.vue";
import CountdownTimer from "@/components/CountdownTimer.vue";
import type { Bet, Game, Team } from "@/services/api";
import { getLogoUrl } from "@/services/api";

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
    const date = new Date(props.game.kickoff_at);
    return date.toLocaleDateString("de-DE", { day: "2-digit", month: "short" });
});

const gameTime = computed(() => {
    const date = new Date(props.game.kickoff_at);
    return date.toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
    });
});

const showCountdown = computed(
    () => props.game.status === "scheduled" && props.game.can_bet,
);
const showBetButton = computed(
    () => props.game.status === "scheduled" && props.game.can_bet,
);

// ---- Team placement logic ----
// is_home: Eisbären links (home), opponent rechts
// away: opponent links, Eisbären rechts
const eisbaerenTeam = computed<Team>(() => ({
    id: 1,
    name: "Eisbären Berlin",
    short_name: "EBB",
    logo_url: "1.svg",
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
