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

const routes: RouteRecordRaw[] = [
    { path: "/", name: "home", component: HomeView },
    { path: "/games", name: "games", component: GameView },
    { path: "/bets", name: "bets", component: BetsView },
    { path: "/leaderboard", name: "leaderboard", component: LeaderboardView },
    { path: "/profile", name: "profile", component: ProfileView },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
