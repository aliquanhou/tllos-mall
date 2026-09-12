<template>
  <div class="result-page" :class="{ 'is-mobile': isMobile }">
    <!-- 移动端顶部导航 -->
    <div class="mobile-nav" v-if="isMobile">
      <div class="nav-back" @click="$router.push('/home')">
        <el-icon :size="22"><ArrowLeft /></el-icon>
      </div>
      <div class="nav-title">支付结果</div>
      <div class="nav-placeholder"></div>
    </div>

    <PageHeader v-if="!isMobile" title="支付结果" subtitle="订单支付状态查询" />

    <div class="result-container">
      <div class="result-card">
        <!-- 成功 -->
        <template v-if="status === 'success'">
          <div class="result-icon success">
            <el-icon :size="64"><CircleCheckFilled /></el-icon>
          </div>
          <h2 class="result-title success-text">支付成功</h2>
          <p class="result-desc">您的订单已支付完成，我们将尽快为您发货</p>
        </template>

        <!-- 失败 -->
        <template v-else-if="status === 'error'">
          <div class="result-icon error">
            <el-icon :size="64"><CircleCloseFilled /></el-icon>
          </div>
          <h2 class="result-title error-text">支付失败</h2>
          <p class="result-desc">支付未完成，请重新发起支付或联系客服</p>
        </template>

        <!-- 处理中 -->
        <template v-else>
          <div class="result-icon pending">
            <el-icon :size="64" class="rotating"><Loading /></el-icon>
          </div>
          <h2 class="result-title pending-text">支付确认中</h2>
          <p class="result-desc">正在确认支付结果，请稍候...</p>
        </template>

        <!-- 金额 -->
        <div class="result-amount" v-if="payAmount !== '0.00'">
          <span class="amount-label">支付金额</span>
          <span class="amount-value">¥{{ payAmount }}</span>
        </div>

        <!-- 订单信息 -->
        <div class="order-info">
          <div class="info-row">
            <span class="info-label">订单号</span>
            <span class="info-value">{{ orderNo }}</span>
          </div>
          <div class="info-row" v-if="orderTime">
            <span class="info-label">下单时间</span>
            <span class="info-value">{{ orderTime }}</span>
          </div>
          <div class="info-row" v-if="payTime">
            <span class="info-label">支付时间</span>
            <span class="info-value">{{ payTime }}</span>
          </div>
        </div>

        <!-- 操作按钮 -->
        <div class="result-actions">
          <button class="action-btn primary" @click="$router.push('/orders')">
            <el-icon><List /></el-icon> 查看订单
          </button>
          <button class="action-btn" @click="$router.push('/home')">
            <el-icon><HomeFilled /></el-icon> 继续购物
          </button>
        </div>

        <!-- 失败时显示重新支付 -->
        <div class="retry-section" v-if="status === 'error'">
          <button class="retry-btn" @click="retryPay">
            <el-icon><RefreshRight /></el-icon> 重新支付
          </button>
        </div>
      </div>

      <!-- 安全提示 -->
      <div class="security-tip">
        <el-icon><Lock /></el-icon>
        <span>本次支付由支付宝提供安全保障，请勿轻信任何要求转账的电话或短信</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  CircleCheckFilled, CircleCloseFilled, Loading, Lock,
  List, HomeFilled, RefreshRight, ArrowLeft
} from '@element-plus/icons-vue'
import PageHeader from "@/components/PageHeader.vue"
import { getPaymentStatus } from '@/api/payment'
import { getOrderDetail, getOrderList } from '@/api/order'

const route = useRoute()
const router = useRouter()
const orderNo = ref(route.params.orderNo || '')
const orderId = ref(null)
const status = ref(route.query.status || 'pending')
const payAmount = ref('0.00')
const orderTime = ref('')
const payTime = ref('')
const isMobile = ref(false)
let pollTimer = null
let pollCount = 0

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

const fetchOrderInfo = async () => {
  try {
    const res = await getOrderDetail(orderNo.value)
    const order = res.data || res
    if (order && order.id) {
      orderId.value = order.id
      payAmount.value = order.pay_amount || order.total_amount || '0.00'
      orderTime.value = order.created_at || ''
      payTime.value = order.pay_time || ''
      if (order.status === 1) {
        status.value = 'success'
      }
      return
    }
  } catch (e) {
    console.error('获取订单详情失败', e)
  }
  try {
    const listRes = await getOrderList({ page: 1, limit: 50 })
    const orderList = listRes.data?.list || listRes.data || []
    const order = orderList.find(o => o.order_no === orderNo.value)
    if (order) {
      orderId.value = order.id
      payAmount.value = order.pay_amount || order.total_amount || '0.00'
      orderTime.value = order.created_at || ''
      payTime.value = order.pay_time || ''
      if (order.status === 1) status.value = 'success'
    }
  } catch (e2) {
    console.error('获取订单列表失败', e2)
  }
}

const startPolling = () => {
  if (status.value !== 'pending' || !orderId.value) return
  pollTimer = setInterval(async () => {
    pollCount++
    if (pollCount > 20) {
      clearInterval(pollTimer)
      return
    }
    try {
      const res = await getPaymentStatus(orderId.value)
      if (res.data?.status === 1 || res.data?.status === 'paid') {
        clearInterval(pollTimer)
        status.value = 'success'
        payTime.value = new Date().toLocaleString()
        ElMessage.success('支付成功！')
      }
    } catch (e) { console.error(e) }
  }, 2500)
}

const retryPay = () => {
  if (orderId.value) {
    router.push(`/pay/${orderNo.value}`)
  } else {
    router.push('/orders')
  }
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchOrderInfo().then(() => {
    if (status.value === 'pending') startPolling()
  })
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
.result-page {
  background: #f5f7fa;
  min-height: calc(100vh - 200px);
  padding: 24px 0 40px;
}

.result-container {
  max-width: 560px;
  margin: 0 auto;
  padding: 0 20px;
}

.result-card {
  background: #fff;
  border-radius: 16px;
  padding: 40px 32px 32px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.result-icon {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}
.result-icon.success {
  background: linear-gradient(135deg, #f0f9eb, #e1f3d8);
  color: #67c23a;
}
.result-icon.error {
  background: linear-gradient(135deg, #fef0f0, #fde2e2);
  color: #f56c6c;
}
.result-icon.pending {
  background: linear-gradient(135deg, #ecf5ff, #d9ecff);
  color: #409eff;
}
.rotating {
  animation: rotate 1.2s linear infinite;
}
@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.result-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px 0;
}
.success-text { color: #67c23a; }
.error-text { color: #f56c6c; }
.pending-text { color: #409eff; }

.result-desc {
  font-size: 14px;
  color: #909399;
  margin: 0 0 24px 0;
}

.result-amount {
  background: #fafbfc;
  border-radius: 12px;
  padding: 18px;
  margin-bottom: 24px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.result-amount .amount-label {
  font-size: 13px;
  color: #909399;
}
.result-amount .amount-value {
  font-size: 32px;
  font-weight: 700;
  color: #f56c6c;
}

.order-info {
  text-align: left;
  background: #fafbfc;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 28px;
}
.info-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  padding: 6px 0;
}
.info-label { color: #909399; }
.info-value { color: #303133; font-family: monospace; font-size: 12px; }

.result-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 12px 28px;
  border-radius: 10px;
  border: 1px solid #dcdfe6;
  background: #fff;
  color: #606266;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}
.action-btn:hover {
  border-color: #1677ff;
  color: #1677ff;
}
.action-btn.primary {
  background: #1677ff;
  border-color: #1677ff;
  color: #fff;
}
.action-btn.primary:hover {
  background: #4096ff;
  color: #fff;
}

.retry-section {
  margin-top: 16px;
}
.retry-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 24px;
  border-radius: 10px;
  border: 1px solid #1677ff;
  background: #fff;
  color: #1677ff;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}
.retry-btn:hover {
  background: #f0f7ff;
}

.security-tip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 20px;
  font-size: 12px;
  color: #909399;
  text-align: center;
}

/* 移动端 */
.mobile-nav {
  display: none;
}

@media (max-width: 768px) {
  .result-page {
    padding: 0 0 40px;
    min-height: 100vh;
  }
  .mobile-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    padding: 12px 16px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 6px rgba(0,0,0,0.06);
  }
  .nav-back {
    width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    color: #303133; cursor: pointer;
  }
  .nav-title { font-size: 17px; font-weight: 600; color: #1a1a1a; }
  .nav-placeholder { width: 36px; }

  .result-container { padding: 12px; }
  .result-card {
    padding: 32px 20px 24px;
    border-radius: 14px;
  }
  .result-icon { width: 80px; height: 80px; }
  .result-icon :deep(.el-icon) { font-size: 48px !important; }
  .result-title { font-size: 20px; }
  .result-desc { font-size: 13px; }
  .result-amount .amount-value { font-size: 28px; }
  .result-actions { flex-direction: column; }
  .action-btn { width: 100%; justify-content: center; padding: 14px; }
  .security-tip { padding: 0 20px; font-size: 11px; line-height: 1.6; }
}
</style>
