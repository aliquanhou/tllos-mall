<template>
  <div class="pay-page" :class="{ 'is-mobile': isMobile }">
    <!-- 移动端顶部导航 -->
    <div class="mobile-nav" v-if="isMobile">
      <div class="nav-back" @click="$router.back()">
        <el-icon :size="22"><ArrowLeft /></el-icon>
      </div>
      <div class="nav-title">订单支付</div>
      <div class="nav-placeholder"></div>
    </div>

    <!-- PC端顶部 -->
    <PageHeader v-if="!isMobile" title="订单支付" subtitle="安全支付 · 极速到账" />

    <div class="pay-container">
      <!-- 左侧主区域 -->
      <div class="pay-main">
        <!-- 金额卡片 -->
        <div class="amount-card" :class="activeTheme">
          <div class="amount-label">应付金额</div>
          <div class="amount-value">
            <span class="currency">¥</span>{{ payAmount }}
          </div>
          <div class="countdown-wrap" v-if="countdown > 0">
            <el-icon><Clock /></el-icon>
            <span>支付剩余 {{ formatCountdown }}</span>
          </div>
          <div class="countdown-wrap expired" v-else>
            <el-icon><Warning /></el-icon>
            <span>支付已超时，请重新下单</span>
          </div>
        </div>

        <!-- 支付方式 -->
        <div class="method-section">
          <div class="section-title">
            <span>选择支付方式</span>
            <span class="secure-badge">
              <el-icon><Lock /></el-icon> 安全加密
            </span>
          </div>
          <div class="method-list">
            <div
              class="method-item"
              v-for="m in methods"
              :key="m.value"
              :class="{ active: payMethod === m.value, disabled: m.disabled }"
              @click="!m.disabled && (payMethod = m.value)"
            >
              <div class="method-icon" :style="{ background: m.gradient }">
                <el-icon :size="26"><component :is="m.icon" /></el-icon>
              </div>
              <div class="method-info">
                <div class="method-name">{{ m.label }}</div>
                <div class="method-desc">{{ m.desc }}</div>
              </div>
              <div class="method-radio" :class="{ checked: payMethod === m.value }">
                <el-icon v-if="payMethod === m.value" :size="14"><Check /></el-icon>
              </div>
            </div>
          </div>
        </div>

        <!-- PC端操作按钮 -->
        <div class="pay-actions" v-if="!isMobile">
          <el-button
            type="primary"
            size="large"
            class="confirm-btn"
            :class="activeTheme"
            :loading="paying"
            :disabled="countdown <= 0"
            @click="handlePay"
          >
            {{ paying ? '支付处理中...' : '确认支付 ¥' + payAmount }}
          </el-button>
          <el-button size="large" class="cancel-btn" @click="$router.push('/orders')">
            返回订单列表
          </el-button>
        </div>
      </div>

      <!-- 右侧订单摘要 -->
      <div class="pay-sidebar">
        <div class="order-card">
          <div class="card-title">订单信息</div>
          <div class="order-row">
            <span class="label">订单号</span>
            <span class="value order-no">{{ orderNo }}</span>
          </div>
          <div class="order-row">
            <span class="label">下单时间</span>
            <span class="value">{{ orderTime }}</span>
          </div>
          <div class="order-row">
            <span class="label">商品件数</span>
            <span class="value">{{ itemCount }} 件</span>
          </div>
          <div class="order-divider"></div>
          <div class="order-row">
            <span class="label">商品总额</span>
            <span class="value">¥{{ totalAmount }}</span>
          </div>
          <div class="order-row">
            <span class="label">运费</span>
            <span class="value free">免运费</span>
          </div>
          <div class="order-row total">
            <span class="label">实付金额</span>
            <span class="value total-price">¥{{ payAmount }}</span>
          </div>
        </div>

        <!-- 商品列表 -->
        <div class="goods-card" v-if="orderItems.length">
          <div class="card-title">商品清单</div>
          <div class="goods-item" v-for="item in orderItems" :key="item.id || item.product_id">
            <div class="goods-img">
              <img :src="item.main_image || item.image || '/placeholder.svg'" :alt="item.name" @error="handleImgError" />
            </div>
            <div class="goods-info">
              <div class="goods-name">{{ item.name }}</div>
              <div class="goods-spec" v-if="item.specs">{{ item.specs }}</div>
            </div>
            <div class="goods-price">¥{{ item.price }} <span class="qty">x{{ item.quantity }}</span></div>
          </div>
        </div>

        <div class="tips-card">
          <div class="tips-title">
            <el-icon><InfoFilled /></el-icon> 支付提示
          </div>
          <ul>
            <li>请在30分钟内完成支付，超时订单将自动取消</li>
            <li>支付完成后，订单状态将自动更新</li>
            <li>如遇支付问题，请联系客服处理</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- 移动端底部固定支付栏 -->
    <div class="mobile-pay-bar" v-if="isMobile">
      <div class="bar-amount">
        <span class="bar-label">实付</span>
        <span class="bar-value">¥{{ payAmount }}</span>
      </div>
      <button
        class="bar-btn"
        :class="activeTheme"
        :disabled="paying || countdown <= 0"
        @click="handlePay"
      >
        <span v-if="paying">支付中...</span>
        <span v-else>立即支付</span>
      </button>
    </div>

    <!-- 支付成功弹窗 -->
    <el-dialog v-model="showSuccess" width="420px" :show-close="false" class="success-dialog">
      <div class="success-content">
        <div class="success-icon">
          <el-icon :size="56"><CircleCheckFilled /></el-icon>
        </div>
        <h3>支付成功</h3>
        <p class="success-amount">¥{{ payAmount }}</p>
        <p class="success-order">订单号：{{ orderNo }}</p>
        <div class="success-actions">
          <button class="action-btn primary" @click="$router.push('/orders')">查看订单</button>
          <button class="action-btn" @click="$router.push('/home')">继续购物</button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  Clock, Warning, Lock, Check, InfoFilled, CircleCheckFilled, ArrowLeft
} from '@element-plus/icons-vue'
import PageHeader from "@/components/PageHeader.vue"
import { payOrder, getPaymentStatus } from '@/api/payment'
import { getOrderDetail, getOrderList } from '@/api/order'

const route = useRoute()
const orderNo = ref(route.params.orderNo || '')
const orderId = ref(null)
const payAmount = ref('0.00')
const totalAmount = ref('0.00')
const itemCount = ref(0)
const orderTime = ref('')
const orderItems = ref([])
const payMethod = ref('alipay')
const paying = ref(false)
const showSuccess = ref(false)
const countdown = ref(1800)
const isMobile = ref(false)
let timer = null

const methods = [
  { value: 'alipay', label: '支付宝', desc: '推荐有支付宝账户的用户使用', icon: 'Wallet', gradient: 'linear-gradient(135deg, #1677ff, #4096ff)', pay_type: 2 },
  // { value: 'wechat', label: '微信支付', desc: '暂未开通', icon: 'ChatDotRound', gradient: 'linear-gradient(135deg, #07c160, #10d46e)', pay_type: 1 },
  { value: 'balance', label: '余额支付', desc: '账户余额直接支付', icon: 'CreditCard', gradient: 'linear-gradient(135deg, #e6a23c, #f5c26b)', pay_type: 3 },
]

const activeTheme = computed(() => {
  const map = { alipay: 'theme-alipay', wechat: 'theme-wechat', balance: 'theme-balance' }
  return map[payMethod.value] || 'theme-alipay'
})

const getPayType = (method) => methods.find(m => m.value === method)?.pay_type || 2

const formatCountdown = computed(() => {
  const m = Math.floor(countdown.value / 60)
  const s = countdown.value % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

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
        ElMessage.success('正在跳转到支付宝...')
        setTimeout(() => { window.location.href = payUrl }, 600)
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

    // 微信支付走轮询
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
      itemCount.value = order.items?.length || order.item_count || 0
      orderItems.value = order.items || []
      orderTime.value = order.created_at || new Date().toLocaleString()
      return
    }
  } catch (e) {
    console.error('通过订单号获取失败，尝试列表查找', e)
  }
  try {
    const listRes = await getOrderList({ page: 1, limit: 50 })
    const orderList = listRes.data?.list || listRes.data || []
    const order = orderList.find(o => o.order_no === orderNo.value)
    if (order) {
      orderId.value = order.id
      payAmount.value = order.pay_amount || order.total_amount || '0.00'
      totalAmount.value = order.total_amount || '0.00'
      itemCount.value = order.item_count || 0
      orderTime.value = order.created_at || new Date().toLocaleString()
    }
  } catch (e2) {
    console.error('获取订单列表失败', e2)
  }
}

const handleImgError = (e) => {
  e.target.src = '/placeholder.svg'
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchOrderInfo()
  timer = setInterval(() => { if (countdown.value > 0) countdown.value-- }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
.pay-page {
  background: #f5f7fa;
  min-height: calc(100vh - 200px);
  padding: 24px 0 40px 0;
}

.pay-container {
  max-width: 1080px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

/* ===== 左侧主区域 ===== */
.pay-main {
  flex: 1;
  min-width: 0;
}

.amount-card {
  border-radius: 16px;
  padding: 36px 32px;
  text-align: center;
  color: #fff;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
  transition: background 0.3s;
}
.amount-card.theme-alipay { background: linear-gradient(135deg, #1677ff 0%, #0958d9 50%, #4096ff 100%); }
.amount-card.theme-wechat { background: linear-gradient(135deg, #07c160 0%, #06ad56 50%, #10d46e 100%); }
.amount-card.theme-balance { background: linear-gradient(135deg, #e6a23c 0%, #cf8b1e 50%, #f5c26b 100%); }

.amount-card::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 300px;
  height: 300px;
  background: rgba(255,255,255,0.08);
  border-radius: 50%;
}
.amount-card::after {
  content: '';
  position: absolute;
  bottom: -40%;
  left: -10%;
  width: 200px;
  height: 200px;
  background: rgba(255,255,255,0.06);
  border-radius: 50%;
}

.amount-label {
  font-size: 15px;
  opacity: 0.9;
  margin-bottom: 10px;
  position: relative;
  z-index: 1;
}
.amount-value {
  font-size: 48px;
  font-weight: 700;
  margin-bottom: 14px;
  position: relative;
  z-index: 1;
  letter-spacing: -1px;
}
.amount-value .currency {
  font-size: 24px;
  margin-right: 4px;
  font-weight: 500;
}
.countdown-wrap {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  background: rgba(255,255,255,0.18);
  padding: 6px 16px;
  border-radius: 20px;
  position: relative;
  z-index: 1;
}
.countdown-wrap.expired { background: rgba(0,0,0,0.2); }

/* 支付方式 */
.method-section {
  background: #fff;
  border-radius: 14px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.section-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 18px;
}
.secure-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #67c23a;
  font-weight: 400;
  background: #f0f9eb;
  padding: 4px 10px;
  border-radius: 12px;
}
.method-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.method-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 20px;
  border: 2px solid #eef0f3;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.25s;
  background: #fff;
}
.method-item:hover {
  border-color: #c0c4cc;
  background: #fafbfc;
}
.method-item.active {
  border-color: #1677ff;
  background: #f0f7ff;
}
.method-item.theme-alipay.active { border-color: #1677ff; background: #f0f7ff; }
.method-item.theme-wechat.active { border-color: #07c160; background: #f0faf3; }
.method-item.disabled { opacity: 0.5; cursor: not-allowed; }

.method-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(0,0,0,0.12);
}
.method-info { flex: 1; min-width: 0; }
.method-name { font-size: 16px; font-weight: 600; color: #1a1a1a; }
.method-desc { font-size: 12px; color: #909399; margin-top: 3px; }
.method-radio {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #dcdfe6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
}
.method-radio.checked {
  background: #1677ff;
  border-color: #1677ff;
  color: #fff;
}

/* PC操作按钮 */
.pay-actions {
  display: flex;
  gap: 14px;
}
.confirm-btn {
  flex: 1;
  height: 52px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  border: none;
}
.confirm-btn.theme-alipay { background: #1677ff; }
.confirm-btn.theme-alipay:hover { background: #4096ff; }
.confirm-btn.theme-wechat { background: #07c160; }
.confirm-btn.theme-wechat:hover { background: #10d46e; }
.confirm-btn.theme-balance { background: #e6a23c; }
.confirm-btn.theme-balance:hover { background: #f5c26b; }
.cancel-btn {
  height: 52px;
  padding: 0 28px;
  border-radius: 12px;
  font-size: 15px;
}

/* ===== 右侧边栏 ===== */
.pay-sidebar {
  width: 340px;
  flex-shrink: 0;
}
.order-card, .goods-card, .tips-card {
  background: #fff;
  border-radius: 14px;
  padding: 22px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.card-title {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f2f5;
}
.order-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  color: #606266;
  margin-bottom: 11px;
}
.order-row .label { color: #909399; }
.order-row .value { color: #303133; }
.order-row .free { color: #67c23a; }
.order-row .order-no {
  font-size: 12px;
  word-break: break-all;
  max-width: 200px;
  text-align: right;
  font-family: monospace;
}
.order-divider {
  height: 1px;
  background: #f0f2f5;
  margin: 12px 0;
}
.order-row.total {
  margin-bottom: 0;
  padding-top: 4px;
}
.order-row.total .label { font-size: 14px; font-weight: 500; color: #303133; }
.total-price {
  font-size: 24px;
  font-weight: 700;
  color: #f56c6c;
}

/* 商品清单 */
.goods-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #f7f8fa;
}
.goods-item:last-child { border-bottom: none; }
.goods-img {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: #f5f7fa;
}
.goods-img img { width: 100%; height: 100%; object-fit: cover; }
.goods-info { flex: 1; min-width: 0; }
.goods-name {
  font-size: 13px;
  color: #303133;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.goods-spec { font-size: 11px; color: #909399; margin-top: 2px; }
.goods-price {
  font-size: 13px;
  color: #303133;
  font-weight: 500;
  white-space: nowrap;
}
.goods-price .qty { color: #909399; font-weight: 400; font-size: 12px; }

/* 提示 */
.tips-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  margin-bottom: 10px;
}
.tips-card ul {
  margin: 0;
  padding-left: 18px;
}
.tips-card li {
  font-size: 12px;
  color: #909399;
  line-height: 1.9;
}

/* 成功弹窗 */
.success-content {
  text-align: center;
  padding: 16px 8px 8px;
}
.success-icon {
  color: #67c23a;
  margin-bottom: 12px;
}
.success-content h3 {
  font-size: 22px;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}
.success-amount {
  font-size: 32px;
  font-weight: 700;
  color: #f56c6c;
  margin: 8px 0;
}
.success-order {
  font-size: 13px;
  color: #909399;
  margin: 4px 0 20px 0;
}
.success-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}
.action-btn {
  padding: 10px 28px;
  border-radius: 10px;
  border: 1px solid #dcdfe6;
  background: #fff;
  color: #606266;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}
.action-btn:hover { border-color: #1677ff; color: #1677ff; }
.action-btn.primary {
  background: #1677ff;
  border-color: #1677ff;
  color: #fff;
}
.action-btn.primary:hover { background: #4096ff; color: #fff; }

/* ===== 移动端适配 ===== */
.mobile-nav {
  display: none;
}

@media (max-width: 768px) {
  .pay-page {
    padding: 0 0 100px 0;
    min-height: 100vh;
    background: #f5f7fa;
  }
  .pay-page.is-mobile { padding-top: 0; }

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
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #303133;
    cursor: pointer;
  }
  .nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #1a1a1a;
  }
  .nav-placeholder { width: 36px; }

  .pay-container {
    flex-direction: column;
    padding: 12px;
    gap: 12px;
  }

  .amount-card {
    padding: 28px 20px;
    border-radius: 14px;
    margin-bottom: 12px;
  }
  .amount-value { font-size: 40px; }
  .amount-value .currency { font-size: 20px; }
  .countdown-wrap { font-size: 13px; padding: 5px 14px; }

  .method-section {
    padding: 18px 16px;
    border-radius: 12px;
    margin-bottom: 12px;
  }
  .section-title { font-size: 15px; margin-bottom: 14px; }
  .method-item {
    padding: 16px;
    gap: 14px;
    border-radius: 10px;
  }
  .method-icon { width: 46px; height: 46px; border-radius: 10px; }
  .method-name { font-size: 15px; }
  .method-desc { font-size: 11px; }

  .pay-actions { display: none; }

  .pay-sidebar {
    width: 100%;
    order: -1;
  }
  .order-card, .goods-card, .tips-card {
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 12px;
  }
  .card-title { font-size: 14px; margin-bottom: 12px; padding-bottom: 10px; }
  .order-row { font-size: 13px; margin-bottom: 9px; }
  .order-row .order-no { max-width: 180px; font-size: 11px; }
  .total-price { font-size: 20px; }
  .goods-card { display: none; }
  .tips-card { display: none; }

  /* 移动端底部支付栏 */
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
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
    box-shadow: 0 -4px 16px rgba(0,0,0,0.1);
    z-index: 200;
  }
  .bar-amount {
    display: flex;
    align-items: baseline;
    gap: 6px;
  }
  .bar-label { font-size: 13px; color: #909399; }
  .bar-value {
    font-size: 24px;
    font-weight: 700;
    color: #f56c6c;
  }
  .bar-btn {
    background: #1677ff;
    color: #fff;
    border: none;
    padding: 12px 40px;
    border-radius: 26px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(22,119,255,0.35);
  }
  .bar-btn.theme-alipay { background: linear-gradient(135deg, #1677ff, #4096ff); box-shadow: 0 4px 12px rgba(22,119,255,0.35); }
  .bar-btn.theme-wechat { background: linear-gradient(135deg, #07c160, #10d46e); box-shadow: 0 4px 12px rgba(7,193,96,0.35); }
  .bar-btn.theme-balance { background: linear-gradient(135deg, #e6a23c, #f5c26b); box-shadow: 0 4px 12px rgba(230,162,60,0.35); }
  .bar-btn:disabled { opacity: 0.5; cursor: not-allowed; }
  .bar-btn:active { transform: scale(0.97); }

  .success-dialog :deep(.el-dialog) {
    border-radius: 16px;
    margin: 0 !important;
    width: calc(100vw - 48px) !important;
    max-width: 380px;
  }
}

@media (max-width: 480px) {
  .pay-container { padding: 10px; }
  .amount-card { padding: 24px 16px; }
  .amount-value { font-size: 36px; }
  .method-item { padding: 14px; }
  .bar-value { font-size: 22px; }
  .bar-btn { padding: 11px 32px; font-size: 15px; }
}
</style>
