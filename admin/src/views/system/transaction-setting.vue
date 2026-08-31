<template>
  <el-card shadow="never">
    <template #header><span>交易设置</span></template>
    <el-form :model="form" label-width="160px" style="max-width:700px">
      <el-form-item label="最低下单金额(元)"><el-input-number v-model="form.min_order_amount" :min="0" :precision="2" /></el-form-item>
      <el-form-item label="最高下单金额(元)"><el-input-number v-model="form.max_order_amount" :min="0" :precision="2" /></el-form-item>
      <el-form-item label="允许使用优惠券"><el-switch v-model="form.allow_coupon" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="允许余额支付"><el-switch v-model="form.allow_balance" :active-value="'1'" :inactive-value="'0'" /></el-form-item>
      <el-form-item label="积分抵扣比例"><el-input-number v-model="form.point_deduct_rate" :min="1" :max="1000" /><span style="margin-left:8px;color:#909399">积分=1元</span></el-form-item>
      <el-form-item><el-button type="primary" @click="handleSave">保存设置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getTransactionSetting, saveTransactionSetting } from '@/api/systemExtra'
const form = ref({})
const fetchConfig = async () => { const res = await getTransactionSetting(); form.value = res.data||{} }
const handleSave = async () => { await saveTransactionSetting(form.value); ElMessage.success('保存成功') }
onMounted(fetchConfig)
</script>
