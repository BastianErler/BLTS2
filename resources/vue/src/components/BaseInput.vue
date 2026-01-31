<template>
  <div class="space-y-1.5">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-navy-800">
      {{ label }}
      <span v-if="required" class="text-bordeaux-800">*</span>
    </label>
    
    <div class="relative">
      <div v-if="$slots.prefix" class="absolute left-3 top-1/2 -translate-y-1/2 text-navy-400">
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
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
      />
      
      <div v-if="$slots.suffix" class="absolute right-3 top-1/2 -translate-y-1/2 text-navy-400">
        <slot name="suffix" />
      </div>
    </div>
    
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="text-sm text-navy-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  type: {
    type: String,
    default: 'text'
  },
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
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

defineEmits(['update:modelValue', 'blur', 'focus'])

const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`)

const inputClasses = computed(() => {
  const base = 'block w-full rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2'
  
  const sizes = {
    sm: 'px-3 py-2 text-sm',
    md: 'px-4 py-2.5 text-base',
    lg: 'px-5 py-3 text-lg',
  }
  
  const state = props.error
    ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
    : 'border-navy-300 focus:border-bordeaux-800 focus:ring-bordeaux-800/20'
  
  const disabled = props.disabled
    ? 'bg-navy-50 cursor-not-allowed opacity-50'
    : 'bg-white hover:border-navy-400'
  
  const padding = {
    prefix: props.$slots?.prefix ? 'pl-10' : '',
    suffix: props.$slots?.suffix ? 'pr-10' : '',
  }
  
  return [
    base,
    sizes[props.size],
    state,
    disabled,
    padding.prefix,
    padding.suffix
  ].join(' ')
})
</script>
