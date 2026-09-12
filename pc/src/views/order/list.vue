<template>
  <div class="order-list-page">
    <PageHeader title="我的订单" subtitle="订单管理 售后无忧" />
    <div class="container">
      <!-- Tab切换 -->
      <div class="order-tabs">
        <div class="tab" v-for="tab in tabs" :key="tab.value" :class="{active: activeTab === tab.value}" @click="switchTab(tab.value)">
          {{ tab.label }}
        </div>
      </div>

      <!-- 订单列表 -->
      <div class="order-list" v-if="orders.length">
        <div class="order-card" v-for="order in orders" :key="order.id">
          <!-- 订单头部：时间+单号+状态 -->
          <div class="order-header">
            <span class="order-no">{{ order.order_no }}</span>
            <span class="order-status" :class="'status-' + order.status">{{ statusMap[order.status] || '未知' }}</span>
          </div>

          <!-- 商品列表 -->
          <div class="order-items" @click="goDetail(order.id)">
            <div class="order-item" v-for="item in order.items?.slice(0, 3) || []" :key="item.id">
              <div class="item-image">
                <img loading="lazy" :src="item.product_image" :alt="item.product_name" @error="onImgError" />
              </div>
              <div class="item-info">
                <div class="item-name">{{ item.product_name }}</div>
                <div class="item-spec" v-if="item.sku_text">{{ item.sku_text }}</div>
              </div>
              <div class="item-right">
                <div class="item-price">¥{{ item.price }}</div>
                <div class="item-qty">×{{ item.quantity }}</div>
              </div>
            </div>
            <div class="more-items" v-if="order.items?.length > 3">共{{ order.items.length }}件商品 ›</div>
          </div>

          <!-- 订单底部：实付+操作按钮 -->
          <div class="order-footer">
            <div class="order-total">
              <span>共{{ totalQty(order) }}件 实付：</span>
              <span class="total-amount">¥{{ order.pay_amount || order.total_amount }}</span>
            </div>
            <div class="action-buttons">
              <!-- 待付款 -->
              <template v-if="order.status == 0">
                <button class="btn btn-default" @click.stop="cancelOrderAction(order)">取消订单</button>
                <button class="btn btn-primary" @click.stop="payOrder(order)">去付款</button>
              </template>
              <!-- 待发货 -->
              <template v-else-if="order.status == 1">
                <button class="btn btn-default" @click.stop="cancelOrderAction(order)">取消订单</button>
                <button class="btn btn-default" @click.stop="remindShip(order)">提醒发货</button>
              </template>
              <!-- 待收货 -->
              <template v-else-if="order.status == 2">
                <button class="btn btn-default" @click.stop="goDetail(order.id)">查看物流</button>
                <button class="btn btn-primary" @click.stop="confirmReceive(order)">确认收货</button>
              </template>
              <!-- 已完成 -->
              <template v-else-if="order.status == 3">
                <button class="btn btn-default" @click.stop="buyAgain(order)">再次购买</button>
                <button class="btn btn-default" @click.stop="goReview(order)">评价</button>
              </template>
              <!-- 已取消 -->
              <template v-else-if="order.status == 4">
                <button class="btn btn-primary" @click.stop="buyAgain(order)">再次购买</button>
              </template>
              <!-- 售后状态 -->
              <template v-else-if="order.status >= 5">
                <button class="btn btn-default" @click.stop="goDetail(order.id)">查看详情</button>
              </template>
              <!-- 默认 -->
              <button class="btn btn-default" @click.stop="goDetail(order.id)">订单详情</button>
            </div>
          </div>
        </div>
      </div>

      <!-- 空状态 -->
      <div class="empty-orders" v-else>
        <div class="empty-icon">📦</div>
        <p>暂无订单</p>
        <button class="btn btn-primary" @click="$router.push('/products')">去购物</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import PageHeader from "@/components/PageHeader.vue"
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getOrderList, cancelOrder, confirmOrder } from '@/api/order'

const router = useRouter()
const activeTab = ref('all')
const orders = ref([])
const page = ref(1)
const limit = ref(10)
const total = ref(0)

const statusMap = { 0: '待付款', 1: '待发货', 2: '待收货', 3: '已完成', 4: '已取消', 5: '退款中', 6: '已退款' }
const tabs = [
  { value: 'all', label: '全部' },
  { value: '0', label: '待付款' },
  { value: '1', label: '待发货' },
  { value: '2', label: '待收货' },
  { value: '3', label: '已完成' },
  { value: 'after_sale', label: '售后' },
]

const totalQty = (order) => {
  return order.items?.reduce((sum, i) => sum + (i.quantity || 0), 0) || 0
}

const fetchOrders = async () => {
  try {
    const params = { page: page.value, limit: limit.value }
    if (activeTab.value !== 'all' && activeTab.value !== 'after_sale') {
      params.status = activeTab.value
    }
    const res = await getOrderList(params)
    orders.value = res.data?.list || []
    total.value = res.data?.total || 0
  } catch (e) { console.error(e) }
}

const goDetail = (id) => router.push(`/order/${id}`)
const payOrder = (order) => router.push(`/pay/${order.order_no}`)
const buyAgain = (order) => { router.push('/products') }
const goReview = (order) => { router.push('/review') }
const remindShip = (order) => { ElMessage.success('已提醒商家发货') }
const onImgError = (e) => { e.target.style.background = '#f5f5f5' }

const confirmReceive = async (order) => {
  try {
    await ElMessageBox.confirm('确认已收到商品？', '提示', { type: 'warning' })
    await confirmOrder(order.id)
    ElMessage.success('已确认收货')
    fetchOrders()
  } catch (e) {
    if (e !== 'cancel') ElMessage.error(e.response?.data?.message || '操作失败')
  }
}

const cancelOrderAction = async (order) => {
  try {
    await ElMessageBox.confirm('确认取消该订单？', '提示', { type: 'warning' })
    await cancelOrder(order.id)
    ElMessage.success('订单已取消')
    fetchOrders()
  } catch (e) {
    if (e !== 'cancel') ElMessage.error(e.response?.data?.message || '操作失败')
  }
}

const switchTab = (val) => { activeTab.value = val; page.value = 1; fetchOrders() }
onMounted(fetchOrders)
</script>

<style scoped>
.order-list-page { background: #f5f5f5; min-height: 100vh; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 12px; }

/* Tab */
.order-tabs {
  display: flex; background: #fff; border-radius: 10px; padding: 0 8px;
  margin-bottom: 12px; overflow-x: auto;
}
.tab {
  padding: 14px 16px; font-size: 14px; color: #666; cursor: pointer;
  white-space: nowrap; border-bottom: 2px solid transparent;
}
.tab.active { color: #ff6a00; border-bottom-color: #ff6a00; font-weight: 600; }

/* 订单卡片 */
.order-card {
  background: #fff; border-radius: 10px; margin-bottom: 10px; overflow: hidden;
}
.order-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 14px; border-bottom: 1px solid #f5f5f5;
}
.order-no { font-size: 12px; color: #999; }
.order-status { font-size: 13px; font-weight: 600; }
.status-0 { color: #ff9500; }
.status-1 { color: #409eff; }
.status-2 { color: #ff6a00; }
.status-3 { color: #67c23a; }
.status-4 { color: #999; }
.status-5, .status-6 { color: #f56c6c; }

/* 商品 */
.order-items { padding: 8px 14px; cursor: pointer; }
.order-item {
  display: flex; gap: 10px; padding: 8px 0; align-items: center;
}
.item-image {
  width: 64px; height: 64px; border-radius: 6px; overflow: hidden;
  background: #f5f5f5; flex-shrink: 0;
}
.item-image img { width: 100%; height: 100%; object-fit: cover; }
.item-info { flex: 1; min-width: 0; }
.item-name {
  font-size: 14px; color: #333; line-height: 1.4;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.item-spec { font-size: 12px; color: #999; margin-top: 4px; }
.item-right { text-align: right; flex-shrink: 0; }
.item-price { font-size: 14px; color: #f56c6c; font-weight: 600; }
.item-qty { font-size: 12px; color: #999; margin-top: 4px; }
.more-items { font-size: 12px; color: #999; padding: 6px 0; }

/* 底部 */
.order-footer {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 14px; border-top: 1px solid #f5f5f5;
}
.order-total { font-size: 13px; color: #666; }
.total-amount { color: #f56c6c; font-weight: 700; font-size: 16px; }
.action-buttons { display: flex; gap: 8px; }
.btn {
  padding: 6px 16px; border-radius: 16px; font-size: 13px; cursor: pointer;
  border: 1px solid #ddd; background: #fff; color: #666;
}
.btn-primary { background: #ff6a00; color: #fff; border-color: #ff6a00; }
.btn-default { background: #fff; color: #666; border-color: #ddd; }

/* 空状态 */
.empty-orders { text-align: center; padding: 80px 20px; }
.empty-icon { font-size: 60px; margin-bottom: 16px; }
.empty-orders p { color: #999; margin: 0 0 20px; }

/* PC端 */
@media (min-width: 769px) {
  .order-card { border-radius: 8px; }
  .item-name { white-space: normal; }
}
</style>
