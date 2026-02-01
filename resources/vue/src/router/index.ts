import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from "vue-router";

import HomeView from "../views/HomeView.vue";
import GameView from "../views/GameView.vue";
import BetsView from "../views/BetsView.vue";
import LeaderboardView from "../views/LeaderboardView.vue";
import ProfileView from "../views/ProfileView.vue";
import LoginView from "../views/Login.vue";

const routes: RouteRecordRaw[] = [
    { path: "/login", name: "login", component: LoginView },
    { path: "/", name: "home", component: HomeView },
    { path: "/games", name: "games", component: GameView },
    { path: "/bets", name: "bets", component: BetsView },
    { path: "/leaderboard", name: "leaderboard", component: LeaderboardView },
    { path: "/profile", name: "profile", component: ProfileView },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach((to) => {
    const token = localStorage.getItem("auth_token");

    // nur login ist öffentlich
    if (!token && to.name !== "login") {
        return { name: "login" };
    }
});

export default router;
