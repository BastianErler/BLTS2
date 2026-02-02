<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import BaseAvatar from "@/components/BaseAvatar.vue";
import BottomNav from "@/components/BottomNav.vue";

const route = useRoute();

defineProps<{
    user?: { name?: string; avatar?: string | null };
}>();

const pageTitle = computed(
    () => (route.meta?.pageTitle as string | null) ?? null,
);
const pageSubtitle = computed(
    () => (route.meta?.pageSubtitle as string | null) ?? null,
);

const isHome = computed(() => route.name === "home");
</script>

<template>
    <div class="min-h-screen bg-[#0b0f14] text-white relative overflow-hidden">
        <!-- Ambient glow (nur Desktop) -->
        <div
            class="pointer-events-none hidden lg:block absolute -top-40 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-bordeaux-800/20 blur-3xl"
        ></div>
        <div
            class="pointer-events-none hidden lg:block absolute bottom-[-220px] left-[-220px] h-[520px] w-[520px] rounded-full bg-white/5 blur-3xl"
        ></div>

        <!-- Centering wrapper -->
        <div
            class="min-h-screen lg:flex lg:items-center lg:justify-center lg:px-8"
        >
            <!-- App shell -->
            <div
                class="min-h-screen w-full lg:max-w-md lg:max-h-[92vh] lg:rounded-2xl lg:overflow-hidden lg:ring-1 lg:ring-white/10 lg:bg-[#0b0f14]"
            >
                <div class="min-h-screen flex flex-col">
                    <!-- TOP BAR -->
                    <header
                        class="relative z-30 overflow-hidden bg-white text-navy-900"
                    >
                        <div class="absolute inset-0 bg-white"></div>
                        <div
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-[url('/images/berlin.png')] bg-bottom bg-no-repeat bg-contain opacity-70 mask-image-[linear-gradient(to_top,black,transparent)]"
                        ></div>

                        <div
                            class="relative flex items-center justify-between px-4 py-2"
                        >
                            <img
                                src="/images/logo-removebg-small-tower.png"
                                alt="Blueliner Berlin"
                                class="h-12 w-auto max-w-[240px] object-contain"
                            />
                            <BaseAvatar
                                :name="user?.name"
                                :src="user?.avatar ?? undefined"
                                size="sm"
                            />
                        </div>
                    </header>

                    <!-- HERO -->
                    <section class="relative overflow-hidden min-h-[200px]">
                        <img
                            src="/images/hero.png"
                            alt=""
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/50 to-black/80"
                        ></div>

                        <div class="relative px-4 pt-6 pb-10">
                            <!-- Home Greeting -->
                            <template v-if="isHome">
                                <h1 class="text-2xl font-bold">
                                    Hallo {{ user?.name ?? "Bastian" }}!
                                </h1>
                                <p class="text-white/80 mt-1">
                                    Hier sind deine nächsten Tipps:
                                </p>
                            </template>

                            <!-- Page Title (non-home) -->
                            <template v-else-if="pageTitle">
                                <h1
                                    class="font-display text-4xl tracking-wide text-white"
                                >
                                    {{ pageTitle }}
                                </h1>
                                <p
                                    v-if="pageSubtitle"
                                    class="text-sm text-white/80 mt-1"
                                >
                                    {{ pageSubtitle }}
                                </p>
                            </template>
                        </div>
                    </section>

                    <!-- CONTENT -->
                    <main
                        class="relative z-10 flex-1 px-4 -mt-20 pb-24 lg:pb-0"
                    >
                        <slot />
                    </main>

                    <!-- BOTTOM NAV -->
                    <BottomNav />
                </div>
            </div>
        </div>
    </div>
</template>
