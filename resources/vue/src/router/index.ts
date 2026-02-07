import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from "vue-router";

import HomeView from "@/views/HomeView.vue";
import GameView from "@/views/GameView.vue";
import BetsView from "@/views/BetsView.vue";
import LeaderboardView from "@/views/LeaderboardView.vue";
import ProfileView from "@/views/ProfileView.vue";
import NotificationSettingsView from "@/views/NotificationSettingsView.vue";
import AdminGamesReview from "@/views/AdminGamesReview.vue";
import LoginView from "@/views/Login.vue";

const routes: RouteRecordRaw[] = [
    {
        path: "/login",
        name: "login",
        component: LoginView,
        meta: { pageTitle: "Login", pageSubtitle: null, public: true },
    },
    {
        path: "/",
        name: "home",
        component: HomeView,
        meta: { pageTitle: null, pageSubtitle: null },
    },
    {
        path: "/games",
        name: "games",
        component: GameView,
        meta: { pageTitle: "Spiele", pageSubtitle: null },
    },
    {
        path: "/bets",
        name: "bets",
        component: BetsView,
        meta: {
            pageTitle: "Tipps",
            pageSubtitle: "Deine abgegebenen Tipps inkl. Ergebnis & Kosten",
        },
    },
    {
        path: "/leaderboard",
        name: "leaderboard",
        component: LeaderboardView,
        meta: {
            pageTitle: "Rangliste",
            pageSubtitle: "Saison 25/26 · Gesamtwertung",
        },
    },
    {
        path: "/profile",
        name: "profile",
        component: ProfileView,
        meta: {
            pageTitle: "Profil",
            pageSubtitle:
                "Überblick über Guthaben, Zahlungen und deine Tipp-Statistiken.",
        },
    },

    // Notifications as "submenu" under profile
    {
        path: "/profile/notifications",
        name: "profile-notifications",
        component: NotificationSettingsView,
        meta: {
            pageTitle: "Benachrichtigungen",
            pageSubtitle: "Erinnerungen & Ergebnis-Infos verwalten",
        },
    },
    {
        path: "/admin/games/review",
        name: "admin-games-review",
        component: AdminGamesReview,
        meta: { pageTitle: "Spiele prüfen", admin: true },
    },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach((to) => {
    const token = localStorage.getItem("auth_token");

    if (!token && to.name !== "login") {
        return { name: "login" };
    }

    if (to.meta?.admin) {
        const isAdmin = localStorage.getItem("is_admin") === "1";
        if (!isAdmin) return { name: "home" };
    }

    return true;
});

export default router;
