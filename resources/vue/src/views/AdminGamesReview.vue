<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";

import BaseCard from "@/components/BaseCard.vue";
import BaseButton from "@/components/BaseButton.vue";
import BaseInput from "@/components/BaseInput.vue";
import BaseBadge from "@/components/BaseBadge.vue";
import Icon from "@/components/Icon.vue";

type Game = {
    id: number;
    season_id: number;
    matchday: number | null;
    opponent_id: number;
    is_home: boolean | number;
    kickoff_at: string | null;
    status: string;
    needs_review: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const router = useRouter();

const loading = ref(false);
const savingId = ref<number | null>(null);
const error = ref<string | null>(null);

const page = ref(1);
const perPage = ref(50);

const games = ref<Paginated<Game> | null>(null);

const hasPrev = computed(() =>
    games.value ? games.value.current_page > 1 : false,
);
const hasNext = computed(() =>
    games.value ? games.value.current_page < games.value.last_page : false,
);

async function load() {
    loading.value = true;
    error.value = null;
    try {
        const res = await axios.get("/api/admin/games/review", {
            params: { page: page.value, per_page: perPage.value },
        });
        games.value = res.data.games;
    } catch (e: any) {
        error.value = e?.response?.data?.message || e?.message || "Load failed";
    } finally {
        loading.value = false;
    }
}

async function markReviewed(g: Game) {
    savingId.value = g.id;
    error.value = null;
    try {
        await axios.patch(`/api/admin/games/${g.id}/review`, {
            kickoff_at: g.kickoff_at || null,
            matchday: g.matchday ?? null,
            needs_review: false,
        });
        await load();
    } catch (e: any) {
        error.value = e?.response?.data?.message || e?.message || "Save failed";
    } finally {
        savingId.value = null;
    }
}

function goBack() {
    router.push({ name: "profile" });
}

onMounted(load);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-4xl tracking-wide text-navy-900">
                    Spiele prüfen
                </h1>
                <div class="text-sm text-navy-600">
                    Verlegte/unklare Spiele korrigieren und als geprüft
                    markieren.
                </div>
            </div>

            <BaseButton variant="ghost" @click="goBack">
                <span class="flex items-center gap-2">
                    <Icon name="arrow-right" :size="18" />
                    Zurück
                </span>
            </BaseButton>
        </div>

        <BaseCard variant="outlined" title="Übersicht">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-navy-700">
                    <span v-if="games">Offen: {{ games.total }}</span>
                    <span v-else>Offen: –</span>
                </div>

                <div class="flex items-center gap-2">
                    <BaseButton
                        variant="outline"
                        :disabled="!hasPrev || loading"
                        @click="
                            page--;
                            load();
                        "
                    >
                        Prev
                    </BaseButton>
                    <BaseBadge variant="default" rounded="full">
                        {{ games?.current_page ?? 1 }} /
                        {{ games?.last_page ?? 1 }}
                    </BaseBadge>
                    <BaseButton
                        variant="outline"
                        :disabled="!hasNext || loading"
                        @click="
                            page++;
                            load();
                        "
                    >
                        Next
                    </BaseButton>
                </div>
            </div>

            <div
                v-if="error"
                class="mt-3 rounded-xl border border-bordeaux-200 bg-bordeaux-50 p-3 text-sm text-bordeaux-800"
            >
                {{ error }}
            </div>
        </BaseCard>

        <div v-if="loading" class="text-sm text-navy-600">Laden…</div>

        <div v-else class="space-y-3">
            <BaseCard
                v-for="g in games?.data ?? []"
                :key="g.id"
                variant="outlined"
                :title="`Game #${g.id} · ${g.status}`"
            >
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="text-sm text-navy-700 space-y-1">
                        <div><b>Saison:</b> {{ g.season_id }}</div>
                        <div><b>Spieltag:</b> {{ g.matchday ?? "—" }}</div>
                        <div><b>Opponent ID:</b> {{ g.opponent_id }}</div>
                        <div>
                            <b>Home?</b>
                            {{ Boolean(g.is_home) ? "Ja" : "Nein" }}
                        </div>
                    </div>

                    <BaseInput
                        v-model="g.kickoff_at"
                        label="Kickoff (DB)"
                        type="text"
                        placeholder="2026-03-01 19:30:00"
                    />

                    <BaseInput
                        v-model="g.matchday"
                        label="Spieltag"
                        type="number"
                        placeholder="z. B. 45"
                    />
                </div>

                <template #footer>
                    <div class="flex justify-end">
                        <BaseButton
                            variant="primary"
                            :loading="savingId === g.id"
                            @click="markReviewed(g)"
                        >
                            Als geprüft markieren
                        </BaseButton>
                    </div>
                </template>
            </BaseCard>

            <BaseCard
                v-if="(games?.data?.length ?? 0) === 0"
                variant="outlined"
                title="Keine offenen Prüfungen"
            >
                <div class="text-sm text-navy-700">
                    Aktuell gibt es keine Spiele mit <code>needs_review</code>.
                </div>
            </BaseCard>
        </div>
    </div>
</template>
