<template>
    <section class="mt-4 space-y-4">
        <div class="glass p-4">
            <div class="text-xs text-white/70">Heute</div>
            <div class="mt-1 text-lg font-extrabold">
                Eisbären Berlin vs Adler Mannheim
            </div>
            <div class="mt-2 flex items-center justify-between text-white/80">
                <span>Anstoß in 12:04:35</span>
                <span
                    class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold"
                    >DEL</span
                >
            </div>

            <button
                class="mt-4 w-full rounded-xl bg-bordeaux-800 py-3 text-sm font-extrabold tracking-wide text-white shadow-[0_16px_40px_rgba(139,35,50,0.45)] active:scale-[0.99]"
                type="button"
            >
                TIPPS ABGEBEN
            </button>
        </div>

        <div class="glass p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-white/70">Kurzlinks</div>
                    <div class="text-base font-extrabold">Schnell rein</div>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-3">
                <button class="mini" type="button">Spielplan</button>
                <button class="mini" type="button">Rangliste</button>
                <button class="mini" type="button">Meine Tipps</button>
                <button class="mini" type="button">Profil</button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.glass {
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
}
.mini {
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 12px;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.92);
}
</style>
