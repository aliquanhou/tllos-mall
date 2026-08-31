<template>
  <el-card shadow="never">
    <template #header><span>系统缓存</span></template>
    <el-alert title="清理系统缓存将清除所有缓存数据，包括配置缓存、数据缓存等。建议在系统空闲时操作。" type="warning" :closable="false" style="margin-bottom:20px" />
    <el-button type="danger" size="large" @click="handleClear" :loading="clearing">清理系统缓存</el-button>
  </el-card>
</template>
<script setup>
import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { clearCache } from '@/api/systemExtra'
const clearing = ref(false)
const handleClear = async () => { await ElMessageBox.confirm('确定要清理系统缓存吗？','提示',{type:'warning'}); clearing.value=true; try { await clearCache(); ElMessage.success('缓存清理成功') } finally { clearing.value=false } }
</script>
