<template>
  <el-card shadow="never">
    <template #header><span>数据导出</span></template>
    <el-form :model="form" label-width="120px" style="max-width:500px">
      <el-form-item label="导出类型">
        <el-select v-model="form.type" style="width:100%">
          <el-option value="orders" label="订单数据" />
          <el-option value="users" label="用户数据" />
          <el-option value="products" label="商品数据" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="handleExport" :loading="loading">开始导出</el-button></el-form-item>
    </el-form>
    <el-result v-if="result" icon="success" title="导出成功" :sub-title="`共导出 ${result.export_count} 条数据`">
      <template #extra><el-button type="primary" @click="handleDownload">下载文件</el-button></template>
    </el-result>
  </el-card>
</template>
<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { exportData } from '@/api/tools'
const form = ref({ type:'orders' }); const loading = ref(false); const result = ref(null)
const handleExport = async () => { loading.value=true; try { const res = await exportData(form.value); result.value = res.data; ElMessage.success('导出成功') } finally { loading.value=false } }
const handleDownload = () => { ElMessage.info('下载功能(模拟)') }
</script>
