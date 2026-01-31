<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="absolute inset-0 flex items-center justify-center">
      <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </span>
    <span :class="{ 'opacity-0': loading }">
      <slot />
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'outline', 'ghost', 'danger'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  type: {
    type: String,
    default: 'button'
  },
  disabled: Boolean,
  loading: Boolean,
  fullWidth: Boolean,
})

defineEmits(['click'])

const buttonClasses = computed(() => {
  const base = 'relative inline-flex items-center justify-center font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2'
  
  const variants = {
    primary: 'bg-bordeaux-800 hover:bg-bordeaux-700 active:bg-bordeaux-900 text-white shadow-lg hover:shadow-xl focus:ring-bordeaux-500',
    secondary: 'bg-navy-700 hover:bg-navy-600 active:bg-navy-800 text-white shadow-lg hover:shadow-xl focus:ring-navy-500',
    outline: 'border-2 border-bordeaux-800 text-bordeaux-800 hover:bg-bordeaux-800 hover:text-white focus:ring-bordeaux-500',
    ghost: 'text-navy-900 hover:bg-navy-100 active:bg-navy-200 focus:ring-navy-500',
    danger: 'bg-red-600 hover:bg-red-700 active:bg-red-800 text-white shadow-lg hover:shadow-xl focus:ring-red-500',
  }
  
  const sizes = {
    sm: 'px-3 py-1.5 text-sm rounded-lg',
    md: 'px-4 py-2.5 text-base rounded-xl',
    lg: 'px-6 py-3 text-lg rounded-xl',
    xl: 'px-8 py-4 text-xl rounded-2xl',
  }
  
  const width = props.fullWidth ? 'w-full' : ''
  
  return [
    base,
    variants[props.variant],
    sizes[props.size],
    width
  ].join(' ')
})
</script>
