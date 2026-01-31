<template>
    <nav
        class="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-navy-200 safe-area-bottom lg:static lg:border-0"
    >
        <div class="flex items-center justify-around px-2 py-2">
            <RouterLink
                v-for="item in resolvedItems"
                :key="item.name"
                :to="item.to"
                class="relative flex flex-col items-center justify-center flex-1 py-2 px-1 transition-all duration-200"
                :class="
                    isActive(item)
                        ? 'text-bordeaux-800'
                        : 'text-navy-400 hover:text-navy-600'
                "
            >
                <Icon :name="item.icon" :className="iconClasses(item)" />
                <span class="text-xs font-medium mt-1">{{ item.label }}</span>

                <span
                    v-if="item.badge"
                    class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center bg-bordeaux-800 text-white text-xs rounded-full"
                >
                    {{ item.badge }}
                </span>
            </RouterLink>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute, type RouteLocationRaw } from "vue-router";
import Icon from "@/components/Icon.vue";

type NavItem = {
    name: string;
    label: string;
    icon: string;
    to: RouteLocationRaw;
    badge?: string | number;
};

const props = defineProps<{
    items?: NavItem[];
}>();

const defaultItems: NavItem[] = [
    { name: "home", label: "Home", icon: "home", to: { name: "home" } },
    { name: "games", label: "Spiele", icon: "calendar", to: { name: "games" } },
    { name: "bets", label: "Tipps", icon: "hockey", to: { name: "bets" } },
    {
        name: "leaderboard",
        label: "Tabelle",
        icon: "trophy",
        to: { name: "leaderboard" },
    },
    { name: "profile", label: "Profil", icon: "user", to: { name: "profile" } },
];

const resolvedItems = computed(() =>
    props.items?.length ? props.items : defaultItems,
);

const route = useRoute();

const isActive = (item: NavItem) => {
    // match by route name
    return route.name === item.name;
};

const iconClasses = (item: NavItem) => {
    const base = "w-6 h-6 transition-transform duration-200";
    const state = isActive(item) ? "scale-110" : "";
    return [base, state].join(" ");
};
</script>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
