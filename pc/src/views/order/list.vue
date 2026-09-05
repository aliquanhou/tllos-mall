<template>
  <div class="order-list-page">
    <div class="container">
      <h2 class="page-title">我的订单</h2>
      <div class="order-tabs">
        <div class="tab" v-for="tab in tabs" :key="tab.value" :class="{active: activeTab === tab.value}" @click="activeTab = tab.value">
          {{ tab.label }}
          <span class="tab-count" v-if="tab.count">({{ tab.count }})</span>
        </div>
      </div>
      <div class="order-list" v-if="orders.length">
        <div class="order-card" v-for="order in orders" :key="order.id">
          <div class="order-header">
            <span class="order-time">{{ order.created_at }}</span>
            <span class="order-no">订单号：{{ order.order_no }}</span>
            <span class="order-status" :class="'status-' + order.status">{{ statusMap[order.status] || '未知' }}</span>
          </div>
          <div class="order-body">
            <div class="order-items">
              <div class="order-item" v-for="item in order.items?.slice(0, 3) || []" :key="item.id" @click="goDetail(order.id)">
                <div class="item-image"><img :src="item.main_image" :alt="item.name" /></div>
                <div class="item-info">
                  <div class="item-name">{{ item.name }}</div>
                  <div class="item-spec" v-if="item.specs">{{ item.specs }}</div>
                </div>
                <div class="item-price">¥{{ item.price }}</div>
                <div class="item-quantity">x{{ item.quantity }}</div>
              </div>
              <div class="more-items" v-if="order.items?.length > 3" @click="goDetail(order.id)">共{{ order.items.length }}件商品，查看详情 →</div>
            </div>
            <div class="order-actions">
              <div class="order-total">
                <span class="total-label">实付</span>
                <span class="total-amount">¥{{ order.pay_amount || order.total_amount }}</span>
              </div>
              <div class="action-buttons">
                <el-button size="small" @click="goDetail(order.id)">订单详情</el-button>
                <el-button type="primary" size="small" v-if="order.status == 0" @click="payOrder(order)">立即付款</el-button>
                <el-button type="warning" size="small" v-if="order.status == 2" @click="confirmReceive(order)">确认收货</el-button>
                <el-button type="danger" size="small" v-if="order.status == 1 || order.status == 2" @click="applyAfterSale(order)">申请售后</el-button>
                <el-button size="small" v-if="order.status == 3" @click="reviewOrder(order)">评价</el-button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="empty-orders" v-else>
        <el-icon size="80" color="#ddd"><List /></el-icon>
        <p>暂无订单</p>
        <el-button type="primary" size="large" @click="$router.push('/products')">去购物</el-button>
      </div>
      <div class="pagination-wrap" v-if="total > limit">
        <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" layout="prev, pager, next, jumper" @current-change="fetchOrders" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
const router = useRouter()
const activeTab = ref('all')
const orders = ref([])
const page = ref(1)
const limit = ref(10)
const total = ref(0)
const statusMap = { 0: '待付款', 1: '待发货', 2: '待收货', 3: '已完成', 4: '已取消', 5: '退款中', 6: '已退款' }
const tabs = [
  { value: 'all', label: '全部订单', count: 0 },
  { value: '0', label: '待付款', count: 0 },
  { value: '1', label: '待发货', count: 0 },
  { value: '2', label: '待收货', count: 0 },
  { value: '3', label: '已完成', count: 0 },
  { value: 'after_sale', label: '售后', count: 0 },
]
const fetchOrders = async () => {
  try {
    orders.value = []
    total.value = 0
  } catch (e) { console.error(e) }
}
const goDetail = (id) => router.push(`/order/${id}`)
const payOrder = (order) => { router.push(`/pay/${order.order_no}`) }
const confirmReceive = async (order) => { try { await ElMessageBox.confirm('确认已收到商品？', '提示', { type: 'warning' }); ElMessage.success('已确认收货'); fetchOrders() } catch (e) {} }
const applyAfterSale = (order) => { ElMessage.info('售后功能开发中') }
const reviewOrder = (order) => { ElMessage.info('评价功能开发中') }
onMounted(fetchOrders)
</script>
<style scoped>
.order-list-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.page-title { font-size: 22px; color: #333; margin: 0 0 20px 0; }
.order-tabs { display: flex; gap: 0; background: #fff; border-radius: 8px; padding: 0 20px; margin-bottom: 16px; }
.tab { padding: 16px 20px; font-size: 14px; color: #666; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
.tab:hover { color: #e6a23c; }
.tab.active { color: #e6a23c; border-bottom-color: #e6a23c; font-weight: bold; }
.tab-count { font-size: 12px; color: #999; }
.order-card { background: #fff; border-radius: 8px; margin-bottom: 16px; overflow: hidden; }
.order-header { display: flex; align-items: center; gap: 20px; padding: 12px 20px; background: #fafafa; border-bottom: 1px solid #f0f0f0; font-size: 13px; color: #666; }
.order-time { }
.order-no { }
.order-status { margin-left: auto; font-weight: bold; }
.status-0 { color: #e6a23c; }
.status-1 { color: #409eff; }
.status-2 { color: #e6a23c; }
.status-3 { color: #67c23a; }
.status-4 { color: #999; }
.status-5 { color: #f56c6c; }
.status-6 { color: #999; }
.order-body { display: flex; padding: 16px 20px; }
.order-items { flex: 1; }
.order-item { display: grid; grid-template-columns: 60px 1fr 80px 50px; gap: 12px; padding: 8px 0; align-items: center; cursor: pointer; }
.item-image { width: 60px; height: 60px; border-radius: 4px; overflow: hidden; }
.item-image img { width: 100%; height: 100%; object-fit: cover; }
.item-name { font-size: 13px; color: #333; margin-bottom: 4px; }
.item-spec { font-size: 11px; color: #999; }
.item-price { font-size: 13px; color: #333; text-align: right; }
.item-quantity { font-size: 13px; color: #666; text-align: center; }
.more-items { font-size: 12px; color: #999; padding: 8px 0; cursor: pointer; }
.more-items:hover { color: #e6a23c; }
.order-actions { width: 200px; flex-shrink: 0; border-left: 1px solid #f0f0f0; padding-left: 20px; display: flex; flex-direction: column; justify-content: space-between; align-items: flex-end; }
.order-total { text-align: right; margin-bottom: 12px; }
.total-label { font-size: 12px; color: #999; margin-right: 8px; }
.total-amount { font-size: 20px; color: #f56c6c; font-weight: bold; }
.action-buttons { display: flex; flex-direction: column; gap: 8px; width: 100%; }
.action-buttons .el-button { width: 100%; }
.empty-orders { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-orders p { color: #999; margin: 16px 0; }
.pagination-wrap { display: flex; justify-content: center; margin-top: 20px; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .order-list-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .page-title { font-size: 18px; margin-bottom: 12px; }
  
  /* Tab导航横向滚动 */
  .order-tabs { gap: 0; margin-bottom: 10px; overflow-x: auto; padding: 0 12px; border-radius: 6px; }
  .tab { padding: 10px 14px; font-size: 13px; white-space: nowrap; }
  .tab-count { font-size: 11px; }
  
  /* 订单卡片 */
  .order-card { margin-bottom: 10px; border-radius: 6px; }
  .order-header { padding: 10px 12px; flex-wrap: wrap; gap: 6px; }
  .order-no { font-size: 12px; }
  .order-status { font-size: 12px; }
  .order-time { font-size: 11px; color: #999; }
  
  /* 订单商品 - grid改flex卡片 */
  .order-body { padding: 0 12px; }
  .order-items { }
  .order-item {
    display: flex !important;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px 0;
    align-items: flex-start;
  }
  .item-image { width: 60px; height: 60px; flex-shrink: 0; border-radius: 4px; }
  .item-info { flex: 1; min-width: 0; }
  .item-name { font-size: 13px; line-height: 1.4; }
  .item-spec { font-size: 11px; color: #999; }
  .item-price { font-size: 12px; color: #666; }
  .item-price::before { content: "¥"; color: #f56c6c; }
  .item-quantity { font-size: 12px; color: #999; }
  .item-quantity::before { content: "x"; }
  .more-items { font-size: 12px; color: #999; padding: 8px 0; text-align: center; }
  
  /* 订单底部 */
  .order-total { padding: 10px 12px; flex-wrap: wrap; gap: 8px; }
  .total-label { font-size: 13px; color: #666; }
  .total-amount { font-size: 16px; color: #f56c6c; font-weight: bold; }
  .order-actions { padding: 0 12px 12px; gap: 8px; flex-wrap: wrap; }
  .action-buttons { gap: 8px; }
  .action-buttons .el-button { font-size: 12px !important; padding: 6px 12px !important; }
  
  .pagination-wrap { margin-top: 10px; overflow-x: auto; }
  .empty-orders { padding: 40px 16px; border-radius: 6px; text-align: center; }
  .empty-orders p { font-size: 13px; margin: 12px 0; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .item-image { width: 50px; height: 50px; }
  .item-name { font-size: 12px; }
  .page-title { font-size: 16px; }
}
</style>
