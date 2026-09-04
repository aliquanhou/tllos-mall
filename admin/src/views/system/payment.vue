<template>
  <el-card shadow="never">
    <template #header><span>支付配置</span></template>
    <el-tabs v-model="activeTab">
      <el-tab-pane label="微信支付" name="wechat">
        <el-form :model="wechatForm" label-width="140px" style="max-width:700px">
          <el-form-item label="商户号(MCHID)"><el-input v-model="wechatForm.wechat_mch_id" /></el-form-item>
          <el-form-item label="AppID"><el-input v-model="wechatForm.wechat_app_id" /></el-form-item>
          <el-form-item label="API密钥"><el-input v-model="wechatForm.wechat_api_key" type="password" show-password /></el-form-item>
          <el-form-item label="API证书路径"><el-input v-model="wechatForm.wechat_cert_path" /></el-form-item>
          <el-form-item label="支付回调地址"><el-input v-model="wechatForm.wechat_notify_url" /></el-form-item>
          <el-form-item label="是否启用"><el-switch v-model="wechatForm.wechat_enabled" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('wechat')">保存微信配置</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="支付宝" name="alipay">
        <el-form :model="alipayForm" label-width="140px" style="max-width:700px">
          <el-form-item label="应用ID(APPID)"><el-input v-model="alipayForm.alipay_app_id" /></el-form-item>
          <el-form-item label="商户私钥"><el-input v-model="alipayForm.alipay_private_key" type="textarea" :rows="3" /></el-form-item>
          <el-form-item label="支付宝公钥"><el-input v-model="alipayForm.alipay_public_key" type="textarea" :rows="3" /></el-form-item>
          <el-form-item label="支付回调地址"><el-input v-model="alipayForm.alipay_notify_url" /></el-form-item>
          <el-form-item label="是否启用"><el-switch v-model="alipayForm.alipay_enabled" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('alipay')">保存支付宝配置</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="余额支付" name="balance">
        <el-form :model="balanceForm" label-width="140px" style="max-width:700px">
          <el-form-item label="是否启用余额支付"><el-switch v-model="balanceForm.balance_enabled" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item label="单笔最低金额"><el-input-number v-model="balanceForm.balance_min_amount" :min="0" :precision="2" /> 元</el-form-item>
          <el-form-item label="单笔最高金额"><el-input-number v-model="balanceForm.balance_max_amount" :min="0" :precision="2" /> 元</el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('balance')">保存余额配置</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSystemConfig, saveSystemConfig } from '@/api/system'
const activeTab = ref('wechat')
const wechatForm = ref({})
const alipayForm = ref({})
const balanceForm = ref({})
const fetchConfig = async () => { const res = await getSystemConfig(); const d = res.data || {}; wechatForm.value = d; alipayForm.value = d; balanceForm.value = d }
const handleSave = async (type) => { const data = type === 'wechat' ? wechatForm.value : type === 'alipay' ? alipayForm.value : balanceForm.value; await saveSystemConfig(data); ElMessage.success('保存成功') }
onMounted(fetchConfig)
</script>
