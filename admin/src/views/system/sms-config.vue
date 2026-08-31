<template>
  <el-card shadow="never">
    <template #header><span>短信配置</span></template>
    <el-form :model="form" label-width="120px" style="max-width:700px">
      <el-form-item label="短信平台"><el-select v-model="form.platform" style="width:100%"><el-option value="aliyun" label="阿里云短信" /><el-option value="tencent" label="腾讯云短信" /></el-select></el-form-item>
      <el-form-item label="AccessKey"><el-input v-model="form.access_key" /></el-form-item>
      <el-form-item label="SecretKey"><el-input v-model="form.secret_key" type="password" show-password /></el-form-item>
      <el-form-item label="签名"><el-input v-model="form.sign_name" /></el-form-item>
      <el-form-item label="模板编码"><el-input v-model="form.template_code" /></el-form-item>
      <el-form-item label="是否启用"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      <el-form-item><el-button type="primary" @click="handleSave">保存配置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSmsConfig, saveSmsConfig } from '@/api/systemExtra'
const form = ref({})
const fetchConfig = async () => { const res = await getSmsConfig(); form.value = res.data||{} }
const handleSave = async () => { await saveSmsConfig(form.value); ElMessage.success('保存成功') }
onMounted(fetchConfig)
</script>
