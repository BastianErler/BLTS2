<template>
  <div :class="cardClasses">
    <div v-if="$slots.header || title" class="px-4 py-3 border-b border-navy-700/50">
      <slot name="header">
        <h3 class="text-lg font-semibold text-navy-900">{{ title }}</h3>
      </slot>
    </div>
    
    <div :class="bodyClasses">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="px-4 py-3 border-t border-navy-700/50">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'elevated', 'outlined', 'glass'].includes(value)
  },
  title: String,
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  },
  hoverable: Boolean,
})

const cardClasses = computed(() => {
  const base = 'rounded-2xl transition-all duration-200'
  
  const variants = {
    default: 'bg-white shadow-md',
    elevated: 'bg-white shadow-xl',
    outlined: 'bg-white border-2 border-navy-200',
    glass: 'bg-white/90 backdrop-blur-md shadow-xl',
  }
  
  const hover = props.hoverable ? 'hover:scale-[1.02] hover:shadow-2xl cursor-pointer' : ''
  
  return [base, variants[props.variant], hover].join(' ')
})

const bodyClasses = computed(() => {
  const paddings = {
    none: '',
    sm: 'p-3',
    md: 'p-4',
    lg: 'p-6',
  }
  
  return paddings[props.padding]
})
</script>
