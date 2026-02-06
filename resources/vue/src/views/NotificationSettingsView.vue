<template>
    <div class="space-y-4">
        <!-- Submenu Header -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="flex items-center justify-between gap-3">
                <button
                    type="button"
                    class="btn-secondary w-auto shrink-0 whitespace-nowrap px-4"
                    @click="goBack"
                >
                    ← Zurück
                </button>

                <div class="min-w-0 text-right">
                    <div class="text-sm font-semibold text-white">
                        Benachrichtigungen
                    </div>
                    <div class="text-xs text-white/60">
                        Erinnerungen & Ergebnis-Infos
                    </div>
                </div>
            </div>
        </section>

        <!-- Status -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-white">Status</div>
                    <div class="text-xs text-white/60">
                        Push-Berechtigung & Gerätefähigkeit
                    </div>
                </div>

                <span
                    class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80"
                >
                    {{ pushSupported ? "unterstützt" : "nicht verfügbar" }}
                </span>
            </div>

            <div class="my-4 h-px bg-white/10"></div>

            <div class="flex items-center justify-between">
                <div class="text-sm text-white/80">Push-Benachrichtigungen</div>

                <Toggle
                    :model-value="form.push_enabled"
                    :disabled="!pushSupported"
                    @update:model-value="(v) => onChange('push_enabled', v)"
                />
            </div>
        </section>

        <!-- Erinnerungen -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="text-sm font-semibold text-white">Erinnerungen</div>
            <div class="text-xs text-white/60">
                Vor Spielstart & Tipp-Sperre
            </div>

            <div class="my-4 h-px bg-white/10"></div>

            <div class="space-y-4">
                <!-- Deadline -->
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm text-white/80">
                            Vor Tipp-Sperre erinnern
                        </div>
                        <div class="text-xs text-white/60">
                            Nur wenn noch kein Tipp abgegeben wurde
                        </div>
                    </div>

                    <Toggle
                        :model-value="form.remind_before_deadline"
                        :disabled="!form.push_enabled"
                        @update:model-value="
                            (v) => onChange('remind_before_deadline', v)
                        "
                    />
                </div>

                <div
                    v-if="form.remind_before_deadline"
                    class="flex items-center justify-between pl-4"
                >
                    <div class="text-xs text-white/70">Zeitpunkt</div>

                    <select
                        :value="form.remind_before_deadline_minutes"
                        :disabled="!form.push_enabled"
                        class="rounded-lg border border-white/10 bg-navy-900/80 px-2 py-1 text-xs text-white focus:outline-none focus:ring-2 focus:ring-white/20"
                        @change="
                            (e) =>
                                onChange(
                                    'remind_before_deadline_minutes',
                                    Number(
                                        (e.target as HTMLSelectElement).value,
                                    ),
                                )
                        "
                    >
                        <option :value="30">30 Minuten vorher</option>
                        <option :value="60">1 Stunde vorher</option>
                        <option :value="120">2 Stunden vorher</option>
                    </select>
                </div>

                <!-- Game start -->
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm text-white/80">
                            Spiel startet ohne Tipp
                        </div>
                        <div class="text-xs text-white/60">
                            Erinnerung bei Spielbeginn
                        </div>
                    </div>

                    <Toggle
                        :model-value="form.remind_on_game_start_if_no_bet"
                        :disabled="!form.push_enabled"
                        @update:model-value="
                            (v) => onChange('remind_on_game_start_if_no_bet', v)
                        "
                    />
                </div>
            </div>
        </section>

        <!-- Ergebnisse -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="text-sm font-semibold text-white">Ergebnisse</div>
            <div class="text-xs text-white/60">Auswertung deiner Tipps</div>

            <div class="my-4 h-px bg-white/10"></div>

            <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-white/80">
                    Wenn mein Tipp ausgewertet wurde
                </div>

                <Toggle
                    :model-value="form.notify_on_bet_result"
                    :disabled="!form.push_enabled"
                    @update:model-value="
                        (v) => onChange('notify_on_bet_result', v)
                    "
                />
            </div>
        </section>

        <!-- Rangliste -->
        <section
            class="rounded-[28px] border border-white/10 bg-navy-800/70 p-4 backdrop-blur-md"
        >
            <div class="text-sm font-semibold text-white">Rangliste</div>
            <div class="text-xs text-white/60">
                Nur bei deutlichen Änderungen
            </div>

            <div class="my-4 h-px bg-white/10"></div>

            <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-white/80">Rang-Änderung melden</div>

                <Toggle
                    :model-value="form.notify_on_rank_change"
                    :disabled="!form.push_enabled"
                    @update:model-value="
                        (v) => onChange('notify_on_rank_change', v)
                    "
                />
            </div>

            <div
                v-if="form.notify_on_rank_change"
                class="mt-3 flex items-center justify-between pl-4"
            >
                <div class="text-xs text-white/70">Ab wie vielen Plätzen</div>

                <select
                    :value="form.rank_change_threshold"
                    :disabled="!form.push_enabled"
                    class="rounded-lg border border-white/10 bg-navy-900/80 px-2 py-1 text-xs text-white focus:outline-none focus:ring-2 focus:ring-white/20"
                    @change="
                        (e) =>
                            onChange(
                                'rank_change_threshold',
                                Number((e.target as HTMLSelectElement).value),
                            )
                    "
                >
                    <option :value="1">1 Platz</option>
                    <option :value="3">3 Plätze</option>
                    <option :value="5">5 Plätze</option>
                </select>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { defineComponent, h, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import {
    notificationSettingsApi,
    type NotificationSettings,
} from "@/services/api";

const router = useRouter();

function goBack() {
    router.push("/profile");
}

/* Toggle Component (NO JSX) */
const Toggle = defineComponent({
    name: "Toggle",
    props: {
        modelValue: { type: Boolean, required: true },
        disabled: { type: Boolean, default: false },
    },
    emits: ["update:modelValue"],
    setup(props, { emit }) {
        return () =>
            h(
                "button",
                {
                    type: "button",
                    class: [
                        "relative inline-flex h-8 w-14 items-center rounded-full border transition",
                        props.disabled
                            ? "cursor-not-allowed opacity-60"
                            : "cursor-pointer",
                        props.modelValue
                            ? "border-emerald-400/30 bg-emerald-500/20"
                            : "border-white/10 bg-white/5",
                    ],
                    onClick: () => {
                        if (props.disabled) return;
                        emit("update:modelValue", !props.modelValue);
                    },
                },
                [
                    h("span", {
                        class: [
                            "inline-block h-6 w-6 transform rounded-full bg-white/90 transition",
                            props.modelValue
                                ? "translate-x-7"
                                : "translate-x-1",
                        ],
                    }),
                ],
            );
    },
});

const pushSupported = "Notification" in window;

const form = reactive<NotificationSettings>({
    push_enabled: false,

    remind_before_deadline: true,
    remind_before_deadline_minutes: 120,

    remind_on_game_start_if_no_bet: true,

    notify_on_bet_result: true,

    notify_on_rank_change: false,
    rank_change_threshold: 3,
});

const saving = ref(false);

async function load() {
    const res = await notificationSettingsApi.get();
    Object.assign(form, res.data);
}

async function persist(patch: Partial<NotificationSettings>) {
    if (saving.value) return;
    saving.value = true;
    try {
        await notificationSettingsApi.update(patch);
    } finally {
        saving.value = false;
    }
}

function onChange<K extends keyof NotificationSettings>(
    key: K,
    value: NotificationSettings[K],
) {
    (form as any)[key] = value;
    void persist({ [key]: value } as any);
}

onMounted(() => {
    void load();
});
</script>

<style>
/* Native select dropdowns in dark UI */
select {
    color-scheme: dark;
}

select option {
    background-color: #0b1b2a;
    color: #ffffff;
}
</style>
