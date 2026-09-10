<template>
  <el-pagination
    v-model:current-page="currentPage"
    v-model:page-size="pageSize"
    :total="total"
    :page-sizes="[10, 20, 50, 100]"
    :layout="layout"
    @size-change="handleSizeChange"
    @current-change="handleCurrentChange"
    style="margin-top: 16px; justify-content: flex-end"
  />
</template>
<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  total: { type: Number, default: 0 },
  page: { type: Number, default: 1 },
  limit: { type: Number, default: 20 },
  layout: { type: String, default: 'total, sizes, prev, pager, next, jumper' }
})
const emit = defineEmits(['update:page', 'update:limit', 'change'])
const currentPage = ref(props.page)
const pageSize = ref(props.limit)
watch(() => props.page, v => currentPage.value = v)
watch(() => props.limit, v => pageSize.value = v)
const handleSizeChange = val => { emit('update:limit', val); emit('change', { page: currentPage.value, limit: val }) }
const handleCurrentChange = val => { emit('update:page', val); emit('change', { page: val, limit: pageSize.value }) }
</script>
