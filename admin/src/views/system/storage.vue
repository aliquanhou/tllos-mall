<template>
  <el-card shadow="never">
    <template #header><span>存储设置</span></template>
    <el-form :model="form" label-width="140px" style="max-width:700px">
      <el-form-item label="存储方式"><el-select v-model="form.storage_type" style="width:100%"><el-option value="local" label="本地存储" /><el-option value="aliyun" label="阿里云OSS" /><el-option value="tencent" label="腾讯云COS" /><el-option value="qiniu" label="七牛云" /></el-select></el-form-item>
      <el-form-item label="AccessKey"><el-input v-model="form.aliyun_access_key" /></el-form-item>
      <el-form-item label="SecretKey"><el-input v-model="form.aliyun_secret_key" type="password" show-password /></el-form-item>
      <el-form-item label="Bucket"><el-input v-model="form.aliyun_bucket" /></el-form-item>
      <el-form-item label="Endpoint"><el-input v-model="form.aliyun_endpoint" /></el-form-item>
      <el-form-item><el-button type="primary" @click="handleSave">保存配置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getStorageConfig, saveStorageConfig } from '@/api/systemExtra'
const form = ref({})
const fetchConfig = async () => { const res = await getStorageConfig(); form.value = res.data||{} }
const handleSave = async () => { await saveStorageConfig(form.value); ElMessage.success('保存成功') }
onMounted(fetchConfig)
</script>
