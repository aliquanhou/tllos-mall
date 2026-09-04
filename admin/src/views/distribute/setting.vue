<template>
  <el-card shadow="never">
    <template #header><span>分销设置</span></template>
    <el-form :model="form" label-width="140px" style="max-width:700px">
      <el-form-item label="是否开启分销"><el-switch v-model="form.enabled" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="是否允许自购返利"><el-switch v-model="form.self_buy" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="最低提现金额(元)"><el-input-number v-model="form.min_withdraw" :min="0" :precision="2" /></el-form-item>
      <el-form-item label="提现手续费率"><el-input-number v-model="form.withdraw_fee" :min="0" :max="1" :precision="4" step="0.01" /><span style="margin-left:8px;color:#909399">如0.01=1%</span></el-form-item>
      <el-form-item label="结算天数"><el-input-number v-model="form.settle_days" :min="0" :max="30" /><span style="margin-left:8px;color:#909399">订单完成后N天结算</span></el-form-item>
      <el-form-item><el-button type="primary" @click="handleSave" :loading="saving">保存设置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSettings, saveSettings } from '@/api/distribute'
const form = ref({}); const saving = ref(false)
const fetchSettings = async () => { const res = await getSettings(); form.value = res.data||{} }
const handleSave = async () => { saving.value=true; try { await saveSettings(form.value); ElMessage.success('保存成功') } finally { saving.value=false } }
onMounted(fetchSettings)
</script>
