<template>
  <div :class="avatarClasses">
    <img v-if="src" :src="src" :alt="alt" class="h-full w-full object-cover" />
    <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-bordeaux-600 to-bordeaux-800 text-white font-bold">
      {{ initials }}
    </div>
    
    <div v-if="status" class="absolute bottom-0 right-0 rounded-full border-2 border-white" :class="statusColor" />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  src: String,
  alt: String,
  name: String,
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl'].includes(value)
  },
  status: {
    type: String,
    validator: (value) => ['online', 'offline', 'busy'].includes(value)
  }
})

const avatarClasses = computed(() => {
  const base = 'relative inline-flex items-center justify-center overflow-hidden rounded-full flex-shrink-0'
  
  const sizes = {
    xs: 'h-6 w-6 text-xs',
    sm: 'h-8 w-8 text-sm',
    md: 'h-10 w-10 text-base',
    lg: 'h-12 w-12 text-lg',
    xl: 'h-16 w-16 text-xl',
    '2xl': 'h-20 w-20 text-2xl',
  }
  
  return [base, sizes[props.size]].join(' ')
})

const initials = computed(() => {
  if (!props.name) return '?'
  
  const names = props.name.split(' ')
  if (names.length >= 2) {
    return `${names[0][0]}${names[1][0]}`.toUpperCase()
  }
  return names[0].substring(0, 2).toUpperCase()
})

const statusColor = computed(() => {
  const colors = {
    online: 'h-3 w-3 bg-green-500',
    offline: 'h-3 w-3 bg-gray-400',
    busy: 'h-3 w-3 bg-red-500',
  }
  
  return colors[props.status] || ''
})
</script>
