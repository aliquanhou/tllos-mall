<template>
  <el-card shadow="never">
    <template #header><span>订单设置</span></template>
    <el-form :model="form" label-width="180px" style="max-width:700px">
      <el-form-item label="订单自动取消时间(小时)"><el-input-number v-model="form.auto_cancel_hours" :min="0" :max="72" /></el-form-item>
      <el-form-item label="订单自动收货天数"><el-input-number v-model="form.auto_receive_days" :min="0" :max="30" /></el-form-item>
      <el-form-item label="订单自动完成天数"><el-input-number v-model="form.auto_finish_days" :min="0" :max="60" /></el-form-item>
      <el-form-item label="退款超时时间(小时)"><el-input-number v-model="form.refund_timeout_hours" :min="0" :max="168" /></el-form-item>
      <el-form-item label="售后申请有效期(天)"><el-input-number v-model="form.after_sale_days" :min="0" :max="30" /></el-form-item>
      <el-form-item><el-button type="primary" @click="handleSave" :loading="saving">保存设置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getOrderSetting, saveOrderSetting } from '@/api/systemExtra'
const form = ref({}); const saving = ref(false)
const fetchConfig = async () => { const res = await getOrderSetting(); form.value = res.data||{} }
const handleSave = async () => { saving.value=true; try { await saveOrderSetting(form.value); ElMessage.success('保存成功') } finally { saving.value=false } }
onMounted(fetchConfig)
</script>
