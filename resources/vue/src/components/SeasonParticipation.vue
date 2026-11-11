<template>
    <section v-if="season && !dismissed" class="rounded-xl border border-white/10 bg-navy-800 p-4 mb-4 text-white">
        <template v-if="!participation">
            <p class="font-semibold">An {{ season.name }} teilnehmen?</p>
            <p class="text-sm mt-2">Für jedes verpasste Spiel der Saison wird 1 € berechnet.</p>
            <p v-if="finishedGames" class="text-sm mt-2 text-amber-200">Die Saison hat bereits {{ finishedGames }} abgeschlossene Spiele. Auch dafür zählen fehlende Tipps.</p>
            <div class="flex gap-2 mt-3">
                <button class="btn-secondary" :disabled="busy" @click="join">Teilnehmen</button>
                <button class="btn-secondary" @click="dismissed = true">Später</button>
            </div>
        </template>
        <template v-else-if="!participation.withdrawn_at">
            <p class="text-sm">Teilnahme an {{ season.name }} aktiv.</p>
            <p v-if="participation.withdrawal_requested_at" class="text-sm mt-2">Deine Abmeldeanfrage wartet auf eine Admin-Entscheidung.</p>
            <button v-else class="btn-secondary mt-2" :disabled="busy" @click="withdraw">Abmeldung beantragen</button>
        </template>
        <p v-else class="text-sm">Deine Teilnahme an {{ season.name }} wurde beendet.</p>
        <p v-if="error" role="alert" class="text-rose-300 text-sm mt-2">{{ error }}</p>
    </section>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';

type Participation = { withdrawn_at: string | null; withdrawal_requested_at: string | null };
const route = useRoute();
const season = ref<{ id: number; name: string } | null>(null);
const participation = ref<Participation | null>(null);
const finishedGames = ref(0);
const dismissed = ref(false);
const busy = ref(false);
const error = ref('');

async function load() {
    if (route.name === 'login') { season.value = null; return; }
    try {
        const { data } = await api.get('/season-participation');
        season.value = data.season;
        participation.value = data.participation;
        finishedGames.value = data.finished_games ?? 0;
    } catch { error.value = 'Saisonstatus konnte nicht geladen werden.'; }
}
async function join() {
    busy.value = true; error.value = '';
    try { await api.post('/season-participation', { accept_previous_games: true }); await load(); }
    catch { error.value = 'Teilnahme konnte nicht gespeichert werden.'; }
    finally { busy.value = false; }
}
async function withdraw() {
    if (!window.confirm('Abmeldung bei einem Administrator beantragen?')) return;
    busy.value = true; error.value = '';
    try { await api.post('/season-participation/withdrawal'); await load(); }
    catch { error.value = 'Abmeldung konnte nicht beantragt werden.'; }
    finally { busy.value = false; }
}
watch(() => route.name, () => { void load(); }, { immediate: true });
</script>
