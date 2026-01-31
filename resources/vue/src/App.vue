<template>
    <AppLayout :user="user">
        <RouterView />
    </AppLayout>
</template>

<script setup lang="ts">
import { RouterView } from "vue-router";
import AppLayout from "@/components/AppLayout.vue";

const user = {
    name: "Bastian",
    balance: "12,50",
    avatar: null,
};
</script>
