<template>
  <el-card shadow="never">
    <template #header><span>基础配置</span></template>
    <el-form :model="form" label-width="120px" style="max-width:700px">
      <el-form-item label="网站名称"><el-input v-model="form.site_name" /></el-form-item>
      <el-form-item label="网站LOGO"><el-input v-model="form.site_logo" placeholder="LOGO图片URL" /></el-form-item>
      <el-form-item label="客服电话"><el-input v-model="form.contact_phone" /></el-form-item>
      <el-form-item label="客服邮箱"><el-input v-model="form.contact_email" /></el-form-item>
      <el-form-item label="备案号"><el-input v-model="form.site_icp" /></el-form-item>
      <el-form-item label="网站描述"><el-input v-model="form.site_desc" type="textarea" :rows="3" /></el-form-item>
      <el-form-item label="用户协议"><el-input v-model="form.user_agreement" type="textarea" :rows="3" /></el-form-item>
      <el-form-item label="隐私政策"><el-input v-model="form.privacy_policy" type="textarea" :rows="3" /></el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSave" :loading="saving">保存配置</el-button>
      </el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSystemConfig, saveSystemConfig } from '@/api/system'
const form = ref({})
const saving = ref(false)
const fetchConfig = async () => { const res = await getSystemConfig(); form.value = res.data || {} }
const handleSave = async () => { saving.value = true; try { await saveSystemConfig(form.value); ElMessage.success('保存成功') } finally { saving.value = false } }
onMounted(fetchConfig)
</script>
