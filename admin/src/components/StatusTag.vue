<template>
  <el-tag :type="tagType" size="small">{{ label }}</el-tag>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({
  status: { type: [Number, String], required: true },
  typeMap: { type: Object, default: () => ({ 1: 'success', 0: 'info' }) },
  labelMap: { type: Object, default: () => ({ 1: '启用', 0: '禁用' }) }
})
const tagType = computed(() => props.typeMap[props.status] || 'info')
const label = computed(() => props.labelMap[props.status] || props.status)
</script>
