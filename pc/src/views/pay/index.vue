<template>
  <div class="pay-page">
    <PageHeader title="订单支付" subtitle="安全支付 极速到账" />
    <div class="container">
      <div class="pay-header">
        <h2>订单支付</h2>
        <p class="order-no">订单号：{{ orderNo }}</p>
      </div>
      <div class="pay-wrapper">
        <div class="pay-left">
          <div class="pay-amount-card">
            <div class="amount-label">应付金额</div>
            <div class="amount-value">¥{{ payAmount }}</div>
            <div class="countdown" v-if="countdown > 0">
              <el-icon><Clock /></el-icon>
              支付剩余时间：{{ formatCountdown }}
            </div>
            <div class="countdown expired" v-else>
              <el-icon><Warning /></el-icon>
              支付已超时，请重新下单
            </div>
          </div>
          <div class="payment-methods">
            <h3>选择支付方式</h3>
            <div class="method-list">
              <div class="method-item" v-for="m in methods" :key="m.value" :class="{active: payMethod === m.value, disabled: m.disabled}" @click="!m.disabled && (payMethod = m.value)">
                <div class="method-icon" :style="{background: m.color}"><el-icon size="28"><component :is="m.icon" /></el-icon></div>
                <div class="method-info">
                  <div class="method-name">{{ m.label }}</div>
                  <div class="method-desc">{{ m.desc }}</div>
                </div>
                <el-radio :model-value="payMethod === m.value" />
              </div>
            </div>
          </div>
          <div class="pay-actions desktop-actions">
            <el-button type="primary" size="large" class="pay-btn" :loading="paying" :disabled="countdown <= 0" @click="handlePay">
              {{ paying ? '支付中...' : '确认支付' }}
            </el-button>
            <el-button size="large" @click="$router.push('/orders')">返回订单列表</el-button>
          </div>
        </div>
        <div class="pay-right">
          <div class="order-summary">
            <h3>订单信息</h3>
            <div class="summary-row"><span>订单号</span><span class="order-no-text">{{ orderNo }}</span></div>
            <div class="summary-row"><span>下单时间</span><span>{{ orderTime }}</span></div>
            <div class="summary-row"><span>商品件数</span><span>{{ itemCount }} 件</span></div>
            <div class="summary-row"><span>商品总额</span><span>¥{{ totalAmount }}</span></div>
            <div class="summary-row"><span>运费</span><span class="free">免运费</span></div>
            <div class="summary-total"><span>实付金额</span><span class="total-price">¥{{ payAmount }}</span></div>
          </div>
          <div class="pay-tips">
            <h4>支付提示</h4>
            <ul>
              <li>请在30分钟内完成支付，超时订单将自动取消</li>
              <li>支付完成后，订单状态将自动更新</li>
              <li>如遇支付问题，请联系客服</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <!-- 移动端底部固定支付栏 -->
    <div class="mobile-pay-bar">
      <div class="mobile-amount">
        <span class="label">实付</span>
        <span class="value">¥{{ payAmount }}</span>
      </div>
      <el-button type="primary" class="mobile-pay-btn" :loading="paying" :disabled="countdown <= 0" @click="handlePay">
        {{ paying ? '支付中...' : '立即支付' }}
      </el-button>
    </div>
    <!-- 支付成功弹窗 -->
    <el-dialog v-model="showSuccess" title="支付成功" width="400px" :close-on-click-modal="false">
      <div class="pay-success">
        <el-icon size="64" color="#67c23a"><CircleCheckFilled /></el-icon>
        <h3>支付成功</h3>
        <p>订单号：{{ orderNo }}</p>
        <p>支付金额：¥{{ payAmount }}</p>
        <div class="success-actions">
          <el-button type="primary" @click="$router.push('/orders')">查看订单</el-button>
          <el-button @click="$router.push('/home')">继续购物</el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>
<script setup>
import PageHeader from "@/components/PageHeader.vue"
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Clock, Warning, ChatDotRound, Wallet, CreditCard, CircleCheckFilled } from '@element-plus/icons-vue'
import { payOrder, getPaymentStatus } from '@/api/payment'
import { getOrderDetail, getOrderList } from '@/api/order'
const route = useRoute()
const orderNo = ref(route.params.orderNo || '')
const orderId = ref(null)
const payAmount = ref('0.00')
const totalAmount = ref('0.00')
const itemCount = ref(0)
const orderTime = ref('')
const payMethod = ref('alipay')
const paying = ref(false)
const showSuccess = ref(false)
const countdown = ref(1800)
let timer = null
const methods = [
  { value: 'wechat', label: '微信支付', desc: '推荐使用微信扫码支付', icon: ChatDotRound, color: '#07c160', pay_type: 1 },
  { value: 'alipay', label: '支付宝', desc: '支付宝安全支付', icon: Wallet, color: '#1677ff', pay_type: 2 },
  { value: 'balance', label: '余额支付', desc: '账户余额支付', icon: CreditCard, color: '#e6a23c', pay_type: 3 },
]
const getPayType = (method) => methods.find(m => m.value === method)?.pay_type || 2
const formatCountdown = computed(() => {
  const m = Math.floor(countdown.value / 60)
  const s = countdown.value % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})
const handlePay = async () => {
  if (!orderId.value) {
    ElMessage.error('订单信息加载中，请稍后重试')
    return
  }
  if (countdown.value <= 0) {
    ElMessage.warning('支付已超时，请重新下单')
    return
  }
  paying.value = true
  try {
    const res = await payOrder({ order_id: orderId.value, pay_type: getPayType(payMethod.value) })
    const payResult = res.data || res
    
    if (payMethod.value === 'balance') {
      paying.value = false
      showSuccess.value = true
      if (timer) clearInterval(timer)
      return
    }
    
    if (payMethod.value === 'alipay') {
      const payUrl = payResult?.pay_params?.pay_url || payResult?.pay_url
      if (payUrl) {
        ElMessage.success('正在跳转到支付宝支付...')
        paying.value = false
        window.location.href = payUrl
        return
      }
      if (payResult?.sandbox || payResult?.message?.includes('成功')) {
        paying.value = false
        showSuccess.value = true
        if (timer) clearInterval(timer)
        return
      }
      ElMessage.warning('支付宝支付链接获取失败，请稍后重试')
      paying.value = false
      return
    }
    
    startPaymentPolling()
  } catch (e) {
    paying.value = false
    ElMessage.error(e.response?.data?.message || e.message || '支付失败')
  }
}
const startPaymentPolling = () => {
  let pollCount = 0
  const pollTimer = setInterval(async () => {
    pollCount++
    if (pollCount > 30) {
      clearInterval(pollTimer)
      paying.value = false
      ElMessage.warning('支付结果确认中，请稍后查看订单状态')
      return
    }
    try {
      const res = await getPaymentStatus(orderId.value)
      if (res.data?.status === 1 || res.data?.status === 'paid') {
        clearInterval(pollTimer)
        paying.value = false
        showSuccess.value = true
        if (timer) clearInterval(timer)
        ElMessage.success('支付成功！')
      }
    } catch (e) { console.error(e) }
  }, 2000)
}
const fetchOrderInfo = async () => {
  try {
    const res = await getOrderDetail(orderNo.value)
    const order = res.data || res
    if (order && order.id) {
      orderId.value = order.id
      payAmount.value = order.pay_amount || order.total_amount || '0.00'
      totalAmount.value = order.total_amount || '0.00'
      itemCount.value = order.items?.length || 0
      orderTime.value = order.created_at || new Date().toLocaleString()
      return
    }
  } catch (e) {
    console.error('通过订单号获取订单信息失败，尝试从订单列表查找', e)
  }
  try {
    const listRes = await getOrderList({ page: 1, limit: 50 })
    const orderList = listRes.data?.list || listRes.data || []
    const order = orderList.find(o => o.order_no === orderNo.value || o.orderNo === orderNo.value)
    if (order) {
      orderId.value = order.id
      payAmount.value = order.pay_amount || order.total_amount || '0.01'
      totalAmount.value = order.total_amount || '0.01'
      itemCount.value = order.item_count || order.items?.length || 0
      orderTime.value = order.created_at || new Date().toLocaleString()
    } else {
      payAmount.value = '0.01'
      totalAmount.value = '0.01'
      console.error('未找到订单:', orderNo.value)
    }
  } catch (e2) {
    console.error('获取订单列表也失败', e2)
    payAmount.value = '0.01'
    totalAmount.value = '0.01'
  }
}
onMounted(() => {
  fetchOrderInfo()
  timer = setInterval(() => { if (countdown.value > 0) countdown.value-- }, 1000)
})
onUnmounted(() => { if (timer) clearInterval(timer) })
</script>
<style scoped>
.pay-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0 100px 0; }
.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
.pay-header { text-align: center; margin-bottom: 24px; }
.pay-header h2 { font-size: 24px; color: #333; margin: 0 0 8px 0; }
.order-no { font-size: 14px; color: #999; margin: 0; }
.pay-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.pay-left { flex: 1; }
.pay-amount-card { background: linear-gradient(135deg, #1677ff, #4096ff); color: #fff; border-radius: 12px; padding: 32px; text-align: center; margin-bottom: 20px; }
.amount-label { font-size: 14px; opacity: 0.9; margin-bottom: 8px; }
.amount-value { font-size: 42px; font-weight: bold; margin-bottom: 12px; }
.countdown { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; }
.countdown.expired { color: #fff; opacity: 0.8; }
.payment-methods { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 20px; }
.payment-methods h3 { font-size: 16px; color: #333; margin: 0 0 16px 0; }
.method-list { display: flex; flex-direction: column; gap: 12px; }
.method-item { display: flex; align-items: center; gap: 16px; padding: 16px; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.method-item:hover { border-color: #1677ff; }
.method-item.active { border-color: #1677ff; background: #e6f4ff; }
.method-item.disabled { opacity: 0.5; cursor: not-allowed; }
.method-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
.method-info { flex: 1; }
.method-name { font-size: 15px; color: #333; font-weight: 500; }
.method-desc { font-size: 12px; color: #999; margin-top: 2px; }
.pay-actions { display: flex; gap: 12px; }
.pay-btn { flex: 1; background: #1677ff; border-color: #1677ff; }
.pay-btn:hover { background: #4096ff; border-color: #4096ff; }
.pay-right { width: 320px; flex-shrink: 0; }
.order-summary { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
.order-summary h3 { font-size: 16px; color: #333; margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
.summary-row { display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 10px; }
.summary-row .free { color: #67c23a; }
.order-no-text { font-size: 12px; word-break: break-all; max-width: 180px; text-align: right; }
.summary-total { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f0f0f0; margin-top: 8px; }
.total-price { font-size: 22px; color: #f56c6c; font-weight: bold; }
.pay-tips { background: #fff; border-radius: 8px; padding: 20px; }
.pay-tips h4 { font-size: 14px; color: #333; margin: 0 0 12px 0; }
.pay-tips ul { margin: 0; padding-left: 16px; }
.pay-tips li { font-size: 12px; color: #999; line-height: 1.8; }
.pay-success { text-align: center; padding: 20px; }
.pay-success h3 { font-size: 20px; color: #333; margin: 16px 0 8px 0; }
.pay-success p { font-size: 14px; color: #666; margin: 4px 0; }
.success-actions { display: flex; gap: 12px; justify-content: center; margin-top: 20px; }

/* 移动端底部支付栏 */
.mobile-pay-bar { display: none; }

@media (max-width: 768px) {
  .pay-page { padding: 10px 0 80px 0; min-height: calc(100vh - 120px); }
  .container { padding: 0 12px; }
  .pay-header { margin-bottom: 16px; }
  .pay-header h2 { font-size: 20px; }
  .order-no { font-size: 12px; }
  .pay-wrapper { flex-direction: column; gap: 12px; }
  .pay-left { width: 100%; }
  .pay-right { width: 100%; order: -1; }
  .pay-amount-card { padding: 24px 16px; border-radius: 10px; margin-bottom: 12px; }
  .amount-value { font-size: 36px; }
  .countdown { font-size: 13px; }
  .payment-methods { padding: 16px; border-radius: 8px; margin-bottom: 12px; }
  .payment-methods h3 { font-size: 15px; margin-bottom: 12px; }
  .method-item { padding: 14px; gap: 12px; border-radius: 8px; }
  .method-icon { width: 42px; height: 42px; }
  .method-name { font-size: 14px; }
  .method-desc { font-size: 11px; }
  .desktop-actions { display: none; }
  .order-summary { padding: 16px; border-radius: 8px; margin-bottom: 12px; }
  .order-summary h3 { font-size: 15px; margin-bottom: 12px; padding-bottom: 8px; }
  .summary-row { font-size: 13px; margin-bottom: 8px; }
  .order-no-text { max-width: 160px; font-size: 11px; }
  .summary-total { padding-top: 10px; margin-top: 6px; }
  .total-price { font-size: 20px; }
  .pay-tips { padding: 16px; border-radius: 8px; }
  .pay-tips h4 { font-size: 13px; margin-bottom: 8px; }
  .pay-tips li { font-size: 11px; line-height: 1.6; }
  
  /* 移动端底部固定支付栏 */
  .mobile-pay-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 12px 16px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 100;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
  }
  .mobile-amount { display: flex; align-items: baseline; gap: 6px; }
  .mobile-amount .label { font-size: 13px; color: #666; }
  .mobile-amount .value { font-size: 22px; color: #f56c6c; font-weight: bold; }
  .mobile-pay-btn {
    background: #1677ff !important;
    border-color: #1677ff !important;
    padding: 10px 32px !important;
    font-size: 15px !important;
    border-radius: 24px !important;
  }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .amount-value { font-size: 32px; }
  .method-item { padding: 12px; }
  .mobile-amount .value { font-size: 20px; }
  .mobile-pay-btn { padding: 8px 24px !important; font-size: 14px !important; }
}
</style>
