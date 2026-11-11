<template>
    <RouterLink v-if="count" to="/admin/users/seasons" class="block rounded-xl border border-amber-400/40 bg-navy-800 p-4 mb-4 text-amber-200">
        {{ count }} offene Abmeldeanfrage(n) – jetzt bearbeiten
    </RouterLink>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import api from '@/services/api';
const route = useRoute();
const count = ref(0);
watch(() => route.name, async () => {
    count.value = 0;
    if (route.name === 'login' || !localStorage.getItem('auth_token')) return;
    try {
        const me = await api.get('/me');
        if (me.data.is_admin) {
            const response = await api.get('/admin/withdrawal-requests');
            count.value = response.data.data.length;
        }
    } catch { /* API errors are displayed by the active screen. */ }
}, { immediate: true });
</script>
