<template>
  <BaseCard :hoverable="clickable" variant="elevated" @click="handleClick">
    <div class="space-y-3">
      <!-- Game Status Badge -->
      <div class="flex items-center justify-between">
        <BaseBadge :variant="statusVariant" size="sm">
          {{ statusLabel }}
        </BaseBadge>
        <span v-if="game.is_derby" class="text-xs font-bold text-bordeaux-800 uppercase tracking-wide">
          🔥 Derby
        </span>
      </div>
      
      <!-- Teams & Score -->
      <div class="flex items-center justify-between gap-4">
        <!-- Eisbären Berlin -->
        <div class="flex-1 text-center">
          <div class="text-2xl mb-1">🐻‍❄️</div>
          <div class="font-semibold text-navy-900">Eisbären</div>
          <div v-if="game.status === 'finished'" class="text-3xl font-bold text-navy-900 mt-2">
            {{ game.eisbaeren_goals }}
          </div>
        </div>
        
        <!-- VS / Time -->
        <div class="flex-shrink-0 text-center px-4">
          <div v-if="game.status === 'finished'" class="text-xl font-bold text-navy-400">:</div>
          <div v-else class="space-y-1">
            <div class="text-sm text-navy-600 font-medium">{{ gameDate }}</div>
            <div class="text-xs text-navy-500">{{ gameTime }}</div>
            <CountdownTimer v-if="showCountdown" :target-date="game.kickoff_at" class="text-xs" />
          </div>
        </div>
        
        <!-- Opponent -->
        <div class="flex-1 text-center">
          <div class="text-2xl mb-1">{{ opponentEmoji }}</div>
          <div class="font-semibold text-navy-900 truncate">{{ game.opponent.short_name || game.opponent.name }}</div>
          <div v-if="game.status === 'finished'" class="text-3xl font-bold text-navy-900 mt-2">
            {{ game.opponent_goals }}
          </div>
        </div>
      </div>
      
      <!-- User Bet Info -->
      <div v-if="userBet" class="border-t border-navy-100 pt-3">
        <div class="flex items-center justify-between text-sm">
          <span class="text-navy-600">Dein Tipp:</span>
          <div class="flex items-center gap-2">
            <span class="font-bold text-navy-900">{{ userBet.eisbaeren_goals }} : {{ userBet.opponent_goals }}</span>
            <BaseBadge v-if="userBet.joker_type" variant="primary" size="sm">
              {{ jokerEmoji(userBet.joker_type) }} Joker
            </BaseBadge>
          </div>
        </div>
        <div v-if="game.status === 'finished' && userBet.final_price !== null" class="mt-2 text-right">
          <span :class="priceColorClass">{{ formatPrice(userBet.final_price) }}</span>
        </div>
      </div>
      
      <!-- Action Button -->
      <slot name="action">
        <BaseButton
          v-if="showBetButton"
          variant="primary"
          size="md"
          full-width
          @click.stop="$emit('bet', game)"
        >
          {{ userBet ? 'Tipp ändern' : 'Jetzt tippen' }}
        </BaseButton>
      </slot>
    </div>
  </BaseCard>
</template>

<script setup>
import { computed } from 'vue'
import BaseCard from './BaseCard.vue'
import BaseBadge from './BaseBadge.vue'
import BaseButton from './BaseButton.vue'
import CountdownTimer from './CountdownTimer.vue'

const props = defineProps({
  game: {
    type: Object,
    required: true
  },
  userBet: Object,
  clickable: Boolean
})

const emit = defineEmits(['click', 'bet'])

const handleClick = () => {
  if (props.clickable) {
    emit('click', props.game)
  }
}

const statusVariant = computed(() => {
  const variants = {
    scheduled: 'info',
    live: 'success',
    finished: 'default',
  }
  return variants[props.game.status] || 'default'
})

const statusLabel = computed(() => {
  const labels = {
    scheduled: 'Angesetzt',
    live: '🔴 Live',
    finished: 'Beendet',
  }
  return labels[props.game.status] || props.game.status
})

const gameDate = computed(() => {
  const date = new Date(props.game.kickoff_at)
  return date.toLocaleDateString('de-DE', { 
    day: '2-digit', 
    month: 'short' 
  })
})

const gameTime = computed(() => {
  const date = new Date(props.game.kickoff_at)
  return date.toLocaleTimeString('de-DE', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
})

const showCountdown = computed(() => {
  return props.game.status === 'scheduled' && props.game.can_bet
})

const showBetButton = computed(() => {
  return props.game.status === 'scheduled' && props.game.can_bet
})

const opponentEmoji = computed(() => {
  // Map team names to emojis (can be extended)
  const emojis = {
    'Adler Mannheim': '🦅',
    'EHC München': '🦁',
    'Kölner Haie': '🦈',
    'Red Bull München': '🐂',
  }
  return emojis[props.game.opponent.name] || '🏒'
})

const jokerEmoji = (type) => {
  const emojis = {
    safety: '🛡️',
    precision: '🎯',
    insurance: '💰',
    comeback: '⚡',
  }
  return emojis[type] || '⭐'
}

const priceColorClass = computed(() => {
  if (!props.userBet?.final_price) return ''
  
  const price = parseFloat(props.userBet.final_price)
  if (price === 0) return 'text-green-600 font-bold'
  if (price <= 0.50) return 'text-yellow-600 font-bold'
  return 'text-red-600 font-bold'
})

const formatPrice = (price) => {
  const value = parseFloat(price)
  if (value === 0) return '0,00 € ✓'
  return `${value.toFixed(2).replace('.', ',')} €`
}
</script>
