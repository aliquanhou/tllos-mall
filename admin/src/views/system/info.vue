<template>
  <el-card shadow="never">
    <template #header><span>系统信息</span></template>
    <el-descriptions :column="2" border v-loading="loading">
      <el-descriptions-item label="操作系统">{{ info.system?.os }}</el-descriptions-item>
      <el-descriptions-item label="PHP版本">{{ info.system?.php_version }}</el-descriptions-item>
      <el-descriptions-item label="Laravel版本">{{ info.system?.laravel_version }}</el-descriptions-item>
      <el-descriptions-item label="Web服务器">{{ info.system?.server_software }}</el-descriptions-item>
      <el-descriptions-item label="时区">{{ info.system?.timezone }}</el-descriptions-item>
      <el-descriptions-item label="环境">{{ info.system?.env }}</el-descriptions-item>
      <el-descriptions-item label="数据库">{{ info.database?.connection }}</el-descriptions-item>
      <el-descriptions-item label="数据库名">{{ info.database?.database }}</el-descriptions-item>
      <el-descriptions-item label="内存限制">{{ info.server?.memory_limit }}</el-descriptions-item>
      <el-descriptions-item label="最大执行时间">{{ info.server?.max_execution_time }}s</el-descriptions-item>
      <el-descriptions-item label="上传大小限制">{{ info.server?.upload_max_filesize }}</el-descriptions-item>
      <el-descriptions-item label="POST大小限制">{{ info.server?.post_max_size }}</el-descriptions-item>
      <el-descriptions-item label="服务器时间" :span="2">{{ info.time }}</el-descriptions-item>
    </el-descriptions>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { getSystemInfo } from '@/api/tools'
const info = ref({}); const loading = ref(false)
const fetchData = async () => { loading.value=true; try { const res = await getSystemInfo(); info.value = res.data||{} } finally { loading.value=false } }
onMounted(fetchData)
</script>
