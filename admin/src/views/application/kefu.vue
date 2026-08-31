<template>
  <el-card shadow="never">
    <template #header><span>客服设置</span></template>
    <el-form :model="form" label-width="140px" style="max-width:700px">
      <el-form-item label="是否启用客服"><el-switch v-model="form.kefu_enabled" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="客服电话"><el-input v-model="form.kefu_phone" /></el-form-item>
      <el-form-item label="客服微信"><el-input v-model="form.kefu_wechat" /></el-form-item>
      <el-form-item label="客服QQ"><el-input v-model="form.kefu_qq" /></el-form-item>
      <el-form-item label="工作时间"><el-input v-model="form.kefu_worktime" placeholder="如: 周一至周日 9:00-21:00" /></el-form-item>
      <el-form-item><el-button type="primary" @click="handleSave" :loading="saving">保存设置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getKefuSetting, saveKefuSetting } from '@/api/application'
const form = ref({}); const saving = ref(false)
const fetchSetting = async () => { const res = await getKefuSetting(); form.value = res.data||{} }
const handleSave = async () => { saving.value=true; try { await saveKefuSetting(form.value); ElMessage.success('保存成功') } finally { saving.value=false } }
onMounted(fetchSetting)
</script>
