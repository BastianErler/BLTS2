<template>
    <div class="space-y-1.5">
        <label
            v-if="label"
            :for="inputId"
            class="block text-sm font-medium text-white/80"
        >
            {{ label }}
            <span v-if="required" class="text-bordeaux-200">*</span>
        </label>

        <div class="relative">
            <div
                v-if="hasPrefix"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-white/50"
            >
                <slot name="prefix" />
            </div>

            <input
                :id="inputId"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :min="min"
                :max="max"
                :step="step"
                :class="inputClasses"
                @input="
                    $emit(
                        'update:modelValue',
                        ($event.target as HTMLInputElement).value,
                    )
                "
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
            />

            <div
                v-if="hasSuffix"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50"
            >
                <slot name="suffix" />
            </div>
        </div>

        <p v-if="error" class="text-sm text-rose-300">
            {{ error }}
        </p>
        <p v-else-if="hint" class="text-sm text-white/60">
            {{ hint }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, useId, useSlots } from "vue";

const props = defineProps({
    modelValue: [String, Number],
    type: { type: String, default: "text" },
    label: String,
    placeholder: String,
    error: String,
    hint: String,
    disabled: Boolean,
    required: Boolean,
    min: [String, Number],
    max: [String, Number],
    step: [String, Number],
    size: {
        type: String,
        default: "md",
        validator: (value: string) => ["sm", "md", "lg"].includes(value),
    },
});

defineEmits(["update:modelValue", "blur", "focus"]);

const slots = useSlots();
const uid = useId();
const inputId = computed(() => `input-${uid}`);

const hasPrefix = computed(() => Boolean(slots.prefix));
const hasSuffix = computed(() => Boolean(slots.suffix));

const inputClasses = computed(() => {
    const base =
        "block w-full rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2";

    const sizes: Record<string, string> = {
        sm: "px-3 py-2 text-sm",
        md: "px-4 py-2.5 text-base",
        lg: "px-5 py-3 text-lg",
    };

    // Dark / Glass default styling
    const theme = props.disabled
        ? "bg-white/5 text-white/50 placeholder-white/30 border-white/10 cursor-not-allowed"
        : "bg-white/10 text-white placeholder-white/35 border-white/10 hover:border-white/20";

    const state = props.error
        ? "border-rose-500/60 focus:border-rose-400 focus:ring-rose-400/25"
        : "focus:border-bordeaux-800/70 focus:ring-bordeaux-800/25";

    const padding = [
        hasPrefix.value ? "pl-10" : "",
        hasSuffix.value ? "pr-10" : "",
    ]
        .filter(Boolean)
        .join(" ");

    return [base, sizes[props.size], theme, state, padding]
        .filter(Boolean)
        .join(" ");
});
</script>
