<template>
  <span :style="{ color: color }">{{ prefix }}{{ Number(amount).toFixed(2) }}</span>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({
  amount: { type: [Number, String], required: true },
  prefix: { type: String, default: '¥' },
  type: { type: String, default: '' }
})
const color = computed(() => {
  if (props.type === 'income') return '#f56c6c'
  if (props.type === 'expense') return '#67c23a'
  return ''
})
</script>
