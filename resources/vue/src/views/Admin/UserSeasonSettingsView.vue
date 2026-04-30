<template>
  <div class="space-y-4">
    <section class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md">
      <div class="flex items-center justify-between gap-3">
        <div>
          <div class="text-sm font-semibold text-white">User-Verwaltung</div>
          <div class="text-xs text-white/60">Season-spezifisch: Rangliste & Beiträge</div>
        </div>
        <button type="button" class="btn-secondary" :disabled="loading" @click="loadUsers">{{ loading ? 'Lädt…' : 'Neu laden' }}</button>
      </div>
      <div class="my-4 h-px bg-white/10"></div>
      <div v-if="error" class="text-xs text-rose-200">{{ error }}</div>
      <div class="space-y-2 text-xs">
        <div v-for="u in users" :key="u.id" class="rounded-xl border border-white/10 bg-white/5 p-3">
          <div class="flex items-center justify-between gap-2">
            <div>
              <div class="font-semibold text-white">{{ u.name }} <span v-if="u.is_admin" class="text-bordeaux-100">· Admin</span></div>
              <div class="text-white/60">{{ u.email }}</div>
            </div>
            <div class="flex gap-2">
              <button class="btn-secondary" :disabled="saving[u.id]" @click="toggle(u, 'exclude_from_leaderboard')">{{ u.season_setting.exclude_from_leaderboard ? 'Rangliste: Aus' : 'Rangliste: An' }}</button>
              <button class="btn-secondary" :disabled="saving[u.id]" @click="toggle(u, 'fee_exempt')">{{ u.season_setting.fee_exempt ? 'Beiträge: Frei' : 'Beiträge: Aktiv' }}</button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { adminApi, seasonsApi, type AdminUserRow } from '@/services/api';
const users = ref<AdminUserRow[]>([]);
const loading = ref(false);
const saving = ref<Record<number, boolean>>({});
const seasonId = ref<number | null>(null);
const error = ref<string | null>(null);

async function loadUsers() {
  if (!seasonId.value) return;
  loading.value = true; error.value = null;
  try {
    const res = await adminApi.getUsers(seasonId.value);
    users.value = (res as any)?.data?.data ?? [];
  } catch (e: any) {
    error.value = e?.message || 'User-Liste konnte nicht geladen werden';
  } finally { loading.value = false; }
}

async function toggle(user: AdminUserRow, key: 'exclude_from_leaderboard' | 'fee_exempt') {
  if (!seasonId.value) return;
  const next = { ...user.season_setting, [key]: !user.season_setting[key] };
  saving.value[user.id] = true;
  try {
    await adminApi.updateUserSeasonSetting(user.id, seasonId.value, next);
    user.season_setting = next;
  } finally { saving.value[user.id] = false; }
}

onMounted(async () => {
  const res = await seasonsApi.getAll();
  const seasons = (res as any)?.data?.data ?? [];
  const active = seasons.find((s: any) => s.is_active) ?? seasons[0];
  seasonId.value = active?.id ?? null;
  await loadUsers();
});
</script>
