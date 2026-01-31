<template>
  <span :class="badgeClasses">
    <span v-if="dot" class="absolute -left-1 -top-1 h-3 w-3 rounded-full" :class="dotColor" />
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'success', 'warning', 'danger', 'info'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  dot: Boolean,
  rounded: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'full'].includes(value)
  }
})

const badgeClasses = computed(() => {
  const base = 'relative inline-flex items-center justify-center font-semibold whitespace-nowrap'
  
  const variants = {
    default: 'bg-navy-100 text-navy-800',
    primary: 'bg-bordeaux-100 text-bordeaux-900',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    danger: 'bg-red-100 text-red-800',
    info: 'bg-ice-100 text-ice-800',
  }
  
  const sizes = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
    lg: 'px-3 py-1.5 text-base',
  }
  
  const roundness = {
    sm: 'rounded',
    md: 'rounded-lg',
    lg: 'rounded-xl',
    full: 'rounded-full',
  }
  
  return [
    base,
    variants[props.variant],
    sizes[props.size],
    roundness[props.rounded]
  ].join(' ')
})

const dotColor = computed(() => {
  const colors = {
    default: 'bg-navy-400',
    primary: 'bg-bordeaux-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    danger: 'bg-red-500',
    info: 'bg-ice-500',
  }
  
  return colors[props.variant]
})
</script>
