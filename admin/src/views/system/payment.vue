<template>
  <el-card shadow="never">
    <template #header><span>支付配置</span></template>
    <el-tabs v-model="activeTab">
      <!-- 微信支付 -->
      <el-tab-pane label="微信支付" name="wechat">
        <el-form :model="wechatForm" label-width="160px" style="max-width:750px">
          <el-form-item label="商户号(MCHID)"><el-input v-model="wechatForm.wechat_mch_id" placeholder="微信支付商户号" /></el-form-item>
          <el-form-item label="AppID"><el-input v-model="wechatForm.wechat_app_id" placeholder="微信应用AppID" /></el-form-item>
          <el-form-item label="API密钥"><el-input v-model="wechatForm.wechat_api_key" type="password" show-password placeholder="微信支付API密钥" /></el-form-item>
          <el-form-item label="API证书路径"><el-input v-model="wechatForm.wechat_cert_path" placeholder="证书文件路径" /></el-form-item>
          <el-form-item label="支付回调地址"><el-input v-model="wechatForm.wechat_notify_url" placeholder="https://mall.tllos.com/api/v1/payment/notify/wechat" /></el-form-item>
          <el-form-item label="是否启用"><el-switch v-model="wechatForm.status" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('wechat')" :loading="saving">保存微信配置</el-button></el-form-item>
        </el-form>
      </el-tab-pane>

      <!-- 支付宝 -->
      <el-tab-pane label="支付宝" name="alipay">
        <el-form :model="alipayForm" label-width="160px" style="max-width:750px">
          <el-form-item label="应用ID(APPID)"><el-input v-model="alipayForm.app_id" placeholder="支付宝应用AppID" /></el-form-item>
          <el-form-item label="应用私钥">
            <el-input v-model="alipayForm.merchant_private_key" type="textarea" :rows="5" placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;...&#10;-----END RSA PRIVATE KEY-----" />
          </el-form-item>
          <el-form-item label="支付宝公钥">
            <el-input v-model="alipayForm.alipay_public_key" type="textarea" :rows="4" placeholder="-----BEGIN PUBLIC KEY-----&#10;...&#10;-----END PUBLIC KEY-----" />
          </el-form-item>
          <el-form-item label="支付宝网关"><el-input v-model="alipayForm.gateway_url" placeholder="https://openapi.alipay.com/gateway.do" /></el-form-item>
          <el-form-item label="支付回调地址"><el-input v-model="alipayForm.notify_url" placeholder="https://mall.tllos.com/api/v1/payment/notify/alipay" /></el-form-item>
          <el-form-item label="同步跳转地址"><el-input v-model="alipayForm.return_url" placeholder="https://mall.tllos.com/user/orders" /></el-form-item>
          <el-form-item label="是否启用"><el-switch v-model="alipayForm.status" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item>
            <el-button type="primary" @click="handleSave('alipay')" :loading="saving">保存支付宝配置</el-button>
            <el-button @click="testAlipay" :loading="testing">测试支付连接</el-button>
          </el-form-item>
        </el-form>
      </el-tab-pane>

      <!-- 余额支付 -->
      <el-tab-pane label="余额支付" name="balance">
        <el-form :model="balanceForm" label-width="160px" style="max-width:750px">
          <el-form-item label="是否启用余额支付"><el-switch v-model="balanceForm.status" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item label="单笔最低金额"><el-input-number v-model="balanceForm.balance_min_amount" :min="0" :precision="2" /> 元</el-form-item>
          <el-form-item label="单笔最高金额"><el-input-number v-model="balanceForm.balance_max_amount" :min="0" :precision="2" /> 元</el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('balance')" :loading="saving">保存余额配置</el-button></el-form-item>
        </el-form>
      </el-tab-pane>

      <!-- 积分支付 -->
      <el-tab-pane label="积分支付" name="points">
        <el-form :model="pointsForm" label-width="160px" style="max-width:750px">
          <el-form-item label="是否启用积分支付"><el-switch v-model="pointsForm.status" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item label="积分汇率"><el-input-number v-model="pointsForm.points_rate" :min="0.01" :precision="2" /> 积分=1元</el-form-item>
          <el-form-item label="单笔最低积分"><el-input-number v-model="pointsForm.points_min" :min="0" :precision="1" /> 积分</el-form-item>
          <el-form-item label="单笔最高积分"><el-input-number v-model="pointsForm.points_max" :min="0" :precision="1" /> 积分</el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('points')" :loading="saving">保存积分配置</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getPayConfigList, updatePayConfig } from '@/api/payConfig'

const activeTab = ref('alipay')
const saving = ref(false)
const testing = ref(false)

// 配置表单
const wechatForm = ref({
  id: null,
  wechat_mch_id: '',
  wechat_app_id: '',
  wechat_api_key: '',
  wechat_cert_path: '',
  wechat_notify_url: '',
  status: 0
})

const alipayForm = ref({
  id: null,
  app_id: '',
  merchant_private_key: '',
  alipay_public_key: '',
  gateway_url: 'https://openapi.alipay.com/gateway.do',
  notify_url: 'https://mall.tllos.com/api/v1/payment/notify/alipay',
  return_url: 'https://mall.tllos.com/user/orders',
  status: 0
})

const balanceForm = ref({
  id: null,
  balance_min_amount: 0,
  balance_max_amount: 99999,
  status: 0
})

const pointsForm = ref({
  id: null,
  points_rate: 1,
  points_min: 0,
  points_max: 99999,
  status: 0
})

// 配置ID映射
const configIds = {}

// 获取配置
const fetchConfig = async () => {
  try {
    const res = await getPayConfigList()
    const list = res.data?.list || res.data?.data || []
    list.forEach(item => {
      const config = typeof item.config === 'string' ? JSON.parse(item.config) : item.config
      configIds[item.code] = item.id
      
      if (item.code === 'wechat') {
        wechatForm.value = { ...wechatForm.value, ...config, id: item.id, status: item.status }
      } else if (item.code === 'alipay') {
        alipayForm.value = { ...alipayForm.value, ...config, id: item.id, status: item.status }
      } else if (item.code === 'balance') {
        balanceForm.value = { ...balanceForm.value, ...config, id: item.id, status: item.status }
      } else if (item.code === 'points') {
        pointsForm.value = { ...pointsForm.value, ...config, id: item.id, status: item.status }
      }
    })
  } catch (e) {
    console.error('获取支付配置失败', e)
  }
}

// 保存配置
const handleSave = async (type) => {
  saving.value = true
  try {
    let formData, id
    if (type === 'wechat') {
      formData = { ...wechatForm.value }
      id = formData.id
    } else if (type === 'alipay') {
      formData = { ...alipayForm.value }
      id = formData.id
    } else if (type === 'balance') {
      formData = { ...balanceForm.value }
      id = formData.id
    } else if (type === 'points') {
      formData = { ...pointsForm.value }
      id = formData.id
    }
    
    // 移除id字段，只保存配置
    const { id: _, status, ...config } = formData
    const payload = {
      code: type,
      name: type === 'wechat' ? '微信支付' : type === 'alipay' ? '支付宝' : type === 'balance' ? '余额支付' : '积分支付',
      config: JSON.stringify(config),
      status: status
    }
    
    if (id) {
      await updatePayConfig(id, payload)
    }
    ElMessage.success('保存成功')
  } catch (e) {
    ElMessage.error('保存失败: ' + (e.message || '未知错误'))
  } finally {
    saving.value = false
  }
}

// 测试支付宝连接
const testAlipay = async () => {
  testing.value = true
  try {
    // 简单验证密钥格式
    const privateKey = alipayForm.value.merchant_private_key
    const publicKey = alipayForm.value.alipay_public_key
    
    if (!privateKey || !publicKey || !alipayForm.value.app_id) {
      ElMessage.warning('请先填写AppID、应用私钥和支付宝公钥')
      return
    }
    
    if (!privateKey.includes('BEGIN RSA PRIVATE KEY') && !privateKey.includes('BEGIN PRIVATE KEY')) {
      ElMessage.warning('应用私钥格式不正确，需要包含 -----BEGIN RSA PRIVATE KEY-----')
      return
    }
    
    if (!publicKey.includes('BEGIN PUBLIC KEY')) {
      ElMessage.warning('支付宝公钥格式不正确，需要包含 -----BEGIN PUBLIC KEY-----')
      return
    }
    
    ElMessage.success('配置格式验证通过，可以进行支付测试')
  } catch (e) {
    ElMessage.error('测试失败: ' + e.message)
  } finally {
    testing.value = false
  }
}

onMounted(fetchConfig)
</script>
