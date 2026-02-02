<template>
    <div class="space-y-4">
        <!-- Main panel (same vibe as Spiele/Tipps) -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
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
                            <div class="text-xs text-white/60">Dein Stand</div>
                            <div class="mt-0.5 flex items-baseline gap-2">
                                <div class="text-lg font-semibold text-white">
                                    Platz {{ me.rank }}
                                </div>
                                <div class="text-sm text-white/60">
                                    · {{ me.points }} Punkte
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-xs text-white/60">
                            seit letzter Woche
                        </div>

                        <div
                            class="mt-1 inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold"
                            :class="
                                me.delta > 0
                                    ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-200'
                                    : me.delta < 0
                                      ? 'border-rose-300/30 bg-rose-400/10 text-rose-200'
                                      : 'border-white/10 bg-white/5 text-white/70'
                            "
                        >
                            <span v-if="me.delta > 0">▲</span>
                            <span v-else-if="me.delta < 0">▼</span>
                            <span v-else>•</span>

                            <span v-if="me.delta > 0">+{{ me.delta }}</span>
                            <span v-else-if="me.delta < 0">{{ me.delta }}</span>
                            <span v-else>0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 3 -->
            <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <div class="text-sm font-semibold text-white">Top 3</div>
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
                            {{ podium[1].name }}
                        </div>
                        <div class="mt-1 text-xs text-white/60">
                            {{ podium[1].points }} P
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
                            {{ podium[0].name }}
                        </div>
                        <div class="mt-1 text-xs text-bordeaux-100/90">
                            {{ podium[0].points }} P
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
                            {{ podium[2].name }}
                        </div>
                        <div class="mt-1 text-xs text-white/60">
                            {{ podium[2].points }} P
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
                        v-for="row in rows"
                        :key="row.rank"
                        class="flex items-center justify-between gap-3 p-4"
                        :class="row.isMe ? 'bg-ice-400/10' : ''"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <!-- Rank bubble -->
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
                                <div class="flex items-center gap-2 min-w-0">
                                    <div
                                        class="truncate text-sm font-semibold text-white"
                                    >
                                        {{ row.name }}
                                    </div>

                                    <span
                                        v-if="row.isMe"
                                        class="shrink-0 rounded-full border border-ice-300/30 bg-ice-400/10 px-2 py-0.5 text-[11px] font-semibold text-ice-100"
                                    >
                                        Du
                                    </span>
                                </div>

                                <div class="text-xs text-white/60">
                                    {{ row.points }} Punkte
                                </div>
                            </div>
                        </div>

                        <!-- Trend -->
                        <div
                            class="shrink-0 inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold"
                            :class="
                                row.delta > 0
                                    ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-200'
                                    : row.delta < 0
                                      ? 'border-rose-300/30 bg-rose-400/10 text-rose-200'
                                      : 'border-white/10 bg-white/5 text-white/70'
                            "
                        >
                            <span v-if="row.delta > 0">▲</span>
                            <span v-else-if="row.delta < 0">▼</span>
                            <span v-else>•</span>

                            <span v-if="row.delta > 0">+{{ row.delta }}</span>
                            <span v-else-if="row.delta < 0">{{
                                row.delta
                            }}</span>
                            <span v-else>0</span>
                        </div>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
type Row = {
    rank: number;
    name: string;
    points: number;
    delta: number;
    isMe?: boolean;
};

const lastUpdated = "heute 19:42";

const me = {
    rank: 12,
    points: 87,
    delta: +2,
};

const podium = [
    { name: "Jens", points: 132 },
    { name: "Susi", points: 125 },
    { name: "Tim", points: 118 },
];

const rows: Row[] = [
    { rank: 1, name: "Jens", points: 132, delta: +1 },
    { rank: 2, name: "Susi", points: 125, delta: 0 },
    { rank: 3, name: "Tim", points: 118, delta: -1 },
    { rank: 4, name: "Nina", points: 110, delta: +2 },
    { rank: 5, name: "Chris", points: 104, delta: -1 },
    { rank: 6, name: "Micha", points: 100, delta: 0 },
    { rank: 7, name: "Lars", points: 96, delta: +1 },
    { rank: 8, name: "Karo", points: 93, delta: 0 },
    { rank: 9, name: "Olli", points: 91, delta: -2 },
    { rank: 10, name: "Alex", points: 89, delta: +1 },
    { rank: 11, name: "Sven", points: 88, delta: 0 },
    { rank: 12, name: "Bastian", points: 87, delta: +2, isMe: true },
    { rank: 13, name: "Pia", points: 86, delta: -1 },
];
</script>
