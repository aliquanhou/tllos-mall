<template>
  <div class="order-list-page">
    <div class="order-tabs">
      <div class="tab" :class="{active: currentTab === ''}" @click="switchTab('')">全部</div>
      <div class="tab" :class="{active: currentTab === '0'}" @click="switchTab('0')">待支付</div>
      <div class="tab" :class="{active: currentTab === '1'}" @click="switchTab('1')">待发货</div>
      <div class="tab" :class="{active: currentTab === '2'}" @click="switchTab('2')">待收货</div>
      <div class="tab" :class="{active: currentTab === '3'}" @click="switchTab('3')">已完成</div>
    </div>

    <div class="order-list" v-if="orders.length">
      <div class="order-card" v-for="order in orders" :key="order.id" @click="goDetail(order.id)">
        <div class="order-header">
          <span class="order-no">订单号: {{ order.order_no }}</span>
          <span class="order-status" :class="statusClass[order.status]">{{ statusMap[order.status] }}</span>
        </div>
        <div class="order-items">
          <div class="order-item" v-for="item in order.items" :key="item.id">
            <img :src="item.product_image" :alt="item.product_name" />
            <div class="item-info">
              <div class="item-name">{{ item.product_name }}</div>
              <div class="item-sku" v-if="item.sku_text">{{ item.sku_text }}</div>
            </div>
            <div class="item-right">
              <div class="item-price">¥{{ item.price }}</div>
              <div class="item-qty">x{{ item.quantity }}</div>
            </div>
          </div>
        </div>
        <div class="order-footer">
          <span class="total-label">共{{ totalQty(order) }}件商品 合计:</span>
          <span class="total-price">¥{{ order.pay_amount }}</span>
          <div class="order-actions" @click.stop>
            <button v-if="order.status === 0" class="action-btn danger" @click="cancelOrder(order)">取消订单</button>
            <button v-if="order.status === 0" class="action-btn primary" @click="payOrder(order)">去支付</button>
            <button v-if="order.status === 2" class="action-btn primary" @click="confirmOrder(order)">确认收货</button>
            <button v-if="order.status === 3" class="action-btn" @click="buyAgain(order)">再次购买</button>
          </div>
        </div>
      </div>
    </div>

    <div class="empty" v-else-if="!loading">
      <div class="empty-icon">📦</div>
      <div class="empty-text">暂无订单</div>
      <button class="go-shop-btn" @click="goHome">去逛逛</button>
    </div>
    <div class="loading" v-if="loading">加载中...</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getOrderList, cancelOrder as cancelApi, confirmOrder as confirmApi } from '@/api/order'

const route = useRoute()
const router = useRouter()
const orders = ref([])
const currentTab = ref(route.query.status || '')
const loading = ref(false)

const statusMap = { 0: '待支付', 1: '待发货', 2: '待收货', 3: '已完成', 4: '已取消', 5: '退款中', 6: '已退款' }
const statusClass = { 0: 'wait-pay', 1: 'wait-ship', 2: 'wait-confirm', 3: 'completed', 4: 'cancelled', 5: 'refunding', 6: 'refunded' }

const fetchOrders = async () => {
  loading.value = true
  try {
    const params = { page: 1, limit: 20 }
    if (currentTab.value !== '') params.status = currentTab.value
    const res = await getOrderList(params)
    orders.value = res.data.list || []
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const switchTab = tab => { currentTab.value = tab; fetchOrders() }
const totalQty = order => order.items?.reduce((sum, i) => sum + i.quantity, 0) || 0
const goDetail = id => router.push(`/order/${id}`)
const goHome = () => router.push('/')

const cancelOrder = async order => {
  if (!confirm('确定取消该订单？')) return
  try { await cancelApi(order.id); alert('订单已取消'); fetchOrders() } catch (e) { alert(e.message || '取消失败') }
}

const confirmOrder = async order => {
  if (!confirm('确定确认收货？')) return
  try { await confirmApi(order.id); alert('已确认收货'); fetchOrders() } catch (e) { alert(e.message || '操作失败') }
}

const payOrder = order => alert('即将跳转到支付页')
const buyAgain = order => router.push('/')

onMounted(fetchOrders)
</script>

<style scoped>
.order-list-page { padding-bottom: 20px; background: #f5f5f5; min-height: 100vh; }
.order-tabs { display: flex; background: #fff; position: sticky; top: 0; z-index: 10; }
.tab { flex: 1; text-align: center; padding: 12px 0; font-size: 14px; color: #666; position: relative; }
.tab.active { color: #ff4444; font-weight: bold; }
.tab.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 30px; height: 2px; background: #ff4444; }
.order-list { padding: 10px 15px; }
.order-card { background: #fff; border-radius: 8px; margin-bottom: 10px; overflow: hidden; }
.order-header { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f5f5f5; }
.order-no { font-size: 12px; color: #999; }
.order-status { font-size: 13px; font-weight: 500; }
.order-status.wait-pay { color: #ff9500; }
.order-status.wait-ship { color: #007aff; }
.order-status.wait-confirm { color: #ff4444; }
.order-status.completed { color: #34c759; }
.order-status.cancelled, .order-status.refunded { color: #999; }
.order-status.refunding { color: #ff3b30; }
.order-items { padding: 12px; }
.order-item { display: flex; gap: 10px; padding: 6px 0; }
.order-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; flex-shrink: 0; }
.item-info { flex: 1; min-width: 0; }
.item-name { font-size: 13px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.item-sku { font-size: 11px; color: #999; margin-top: 4px; }
.item-right { text-align: right; flex-shrink: 0; }
.item-price { font-size: 14px; color: #333; }
.item-qty { font-size: 12px; color: #999; margin-top: 4px; }
.order-footer { display: flex; align-items: center; padding: 12px; border-top: 1px solid #f5f5f5; flex-wrap: wrap; gap: 8px; }
.total-label { font-size: 13px; color: #666; margin-left: auto; }
.total-price { font-size: 16px; color: #ff4444; font-weight: bold; }
.order-actions { display: flex; gap: 8px; width: 100%; justify-content: flex-end; }
.action-btn { padding: 6px 16px; border: 1px solid #ddd; border-radius: 16px; font-size: 13px; color: #666; background: #fff; }
.action-btn.primary { background: #ff4444; color: #fff; border-color: #ff4444; }
.action-btn.danger { color: #ff4444; border-color: #ff4444; }
.empty, .loading { text-align: center; padding: 60px 20px; }
.empty-icon { font-size: 50px; margin-bottom: 12px; }
.empty-text { color: #999; font-size: 14px; margin-bottom: 16px; }
.go-shop-btn { padding: 8px 24px; background: #ff4444; color: #fff; border: none; border-radius: 16px; font-size: 13px; }
</style>
