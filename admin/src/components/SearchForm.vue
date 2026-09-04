<template>
  <el-form :inline="true" :model="form" class="search-form" @submit.prevent>
    <slot />
    <el-form-item>
      <el-button type="primary" @click="handleSearch">搜索</el-button>
      <el-button @click="handleReset">重置</el-button>
    </el-form-item>
  </el-form>
</template>
<script setup>
import { reactive } from 'vue'
const props = defineProps({ model: { type: Object, default: () => ({}) } })
const emit = defineEmits(['search', 'reset'])
const form = reactive({ ...props.model })
const handleSearch = () => emit('search', { ...form })
const handleReset = () => { Object.keys(form).forEach(k => form[k] = ''); emit('reset') }
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
