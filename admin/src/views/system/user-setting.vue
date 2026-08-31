<template>
  <el-card shadow="never">
    <template #header><span>用户设置</span></template>
    <el-form :model="form" label-width="160px" style="max-width:700px">
      <el-form-item label="允许用户注册"><el-switch v-model="form.register_enabled" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="注册需短信验证"><el-switch v-model="form.register_verify" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="登录需短信验证"><el-switch v-model="form.login_verify" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="默认用户等级"><el-input-number v-model="form.default_level" :min="1" /></el-form-item>
      <el-form-item label="邀请奖励积分"><el-input-number v-model="form.invite_reward" :min="0" /></el-form-item>
      <el-form-item><el-button type="primary" @click="handleSave">保存设置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getUserSetting, saveUserSetting } from '@/api/systemExtra'
const form = ref({})
const fetchConfig = async () => { const res = await getUserSetting(); form.value = res.data||{} }
const handleSave = async () => { await saveUserSetting(form.value); ElMessage.success('保存成功') }
onMounted(fetchConfig)
</script>
