<template>
  <div class="order-detail-page">
    <div class="container">
      <div class="page-header">
        <h2>订单详情</h2>
        <el-button @click="$router.back()">返回</el-button>
      </div>
      <div class="detail-wrapper">
        <!-- 订单状态 -->
        <div class="status-card">
          <div class="status-info">
            <el-icon size="48" :color="statusColor"><component :is="statusIcon" /></el-icon>
            <div>
              <h3>{{ statusText }}</h3>
              <p>{{ statusDesc }}</p>
            </div>
          </div>
          <div class="status-actions">
            <el-button type="primary" v-if="order?.status == 0" @click="handlePay">立即付款</el-button>
            <el-button type="warning" v-if="order?.status == 2" @click="handleConfirm">确认收货</el-button>
            <el-button v-if="order?.status == 1 || order?.status == 2" @click="handleAfterSale">申请售后</el-button>
            <el-button v-if="order?.status == 3" @click="handleReview">评价商品</el-button>
          </div>
        </div>
        <!-- 物流信息 -->
        <div class="info-card" v-if="order?.express_no">
          <h3>物流信息</h3>
          <div class="logistics-info">
            <div class="logistics-row"><span>物流公司</span><span>{{ order.express_company || '未知' }}</span></div>
            <div class="logistics-row"><span>物流单号</span><span>{{ order.express_no }}</span></div>
            <div class="logistics-row"><span>发货时间</span><span>{{ order.ship_time || '-' }}</span></div>
          </div>
        </div>
        <!-- 收货地址 -->
        <div class="info-card">
          <h3>收货地址</h3>
          <div class="address-info">
            <div class="address-row">
              <span class="receiver">{{ order?.receiver_name }}</span>
              <span class="mobile">{{ order?.receiver_mobile }}</span>
            </div>
            <div class="address-detail">{{ order?.receiver_province }}{{ order?.receiver_city }}{{ order?.receiver_district }}{{ order?.receiver_address }}</div>
          </div>
        </div>
        <!-- 商品清单 -->
        <div class="info-card">
          <h3>商品清单</h3>
          <div class="order-items">
            <div class="order-item" v-for="item in order?.items || []" :key="item.id" @click="$router.push(`/product/${item.product_id}`)">
              <div class="item-image"><img :src="item.main_image" :alt="item.name" /></div>
              <div class="item-info">
                <div class="item-name">{{ item.name }}</div>
                <div class="item-spec" v-if="item.specs">{{ item.specs }}</div>
              </div>
              <div class="item-price">¥{{ item.price }}</div>
              <div class="item-quantity">x{{ item.quantity }}</div>
              <div class="item-subtotal">¥{{ (item.price * item.quantity).toFixed(2) }}</div>
            </div>
          </div>
          <div class="order-total">
            <div class="total-row"><span>商品总额</span><span>¥{{ order?.total_amount || '0.00' }}</span></div>
            <div class="total-row"><span>运费</span><span class="free">免运费</span></div>
            <div class="total-row discount" v-if="order?.discount_amount"><span>优惠</span><span>-¥{{ order.discount_amount }}</span></div>
            <div class="total-row final"><span>实付金额</span><span class="pay-amount">¥{{ order?.pay_amount || order?.total_amount || '0.00' }}</span></div>
          </div>
        </div>
        <!-- 订单信息 -->
        <div class="info-card">
          <h3>订单信息</h3>
          <div class="order-meta">
            <div class="meta-row"><span>订单号</span><span>{{ order?.order_no }}</span></div>
            <div class="meta-row"><span>下单时间</span><span>{{ order?.created_at }}</span></div>
            <div class="meta-row"><span>支付时间</span><span>{{ order?.pay_time || '-' }}</span></div>
            <div class="meta-row"><span>支付方式</span><span>{{ order?.pay_method || '在线支付' }}</span></div>
            <div class="meta-row"><span>订单备注</span><span>{{ order?.remark || '无' }}</span></div>
          </div>
        </div>
        <!-- 操作日志 -->
        <div class="info-card">
          <h3>订单日志</h3>
          <el-timeline>
            <el-timeline-item v-for="(log, idx) in orderLogs" :key="idx" :timestamp="log.time" placement="top">
              {{ log.action }}
            </el-timeline-item>
          </el-timeline>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
const route = useRoute()
const router = useRouter()
const order = ref(null)
const statusMap = { 0: { text: '待付款', desc: '请尽快完成支付', icon: 'Wallet', color: '#e6a23c' }, 1: { text: '待发货', desc: '商家正在准备发货', icon: 'Box', color: '#409eff' }, 2: { text: '待收货', desc: '商品已发出，请注意查收', icon: 'Van', color: '#e6a23c' }, 3: { text: '已完成', desc: '交易已完成，感谢您的购买', icon: 'CircleCheck', color: '#67c23a' }, 4: { text: '已取消', desc: '订单已取消', icon: 'Close', color: '#999' }, 5: { text: '退款中', desc: '售后处理中', icon: 'RefreshLeft', color: '#f56c6c' }, 6: { text: '已退款', desc: '退款已完成', icon: 'CircleCheck', color: '#999' } }
const statusText = computed(() => statusMap[order.value?.status]?.text || '未知')
const statusDesc = computed(() => statusMap[order.value?.status]?.desc || '')
const statusIcon = computed(() => statusMap[order.value?.status]?.icon || 'InfoFilled')
const statusColor = computed(() => statusMap[order.value?.status]?.color || '#999')
const orderLogs = ref([
  { time: '2026-09-01 10:00:00', action: '用户提交订单' },
  { time: '2026-09-01 10:01:00', action: '用户支付成功' },
  { time: '2026-09-01 14:00:00', action: '商家发货' },
])
const fetchOrder = async () => { order.value = { id: route.params.id, order_no: 'ORD' + Date.now(), status: 2, total_amount: '299.00', pay_amount: '299.00', created_at: '2026-09-01 10:00:00', pay_time: '2026-09-01 10:01:00', receiver_name: '张三', receiver_mobile: '138****8888', receiver_province: '广东省', receiver_city: '深圳市', receiver_district: '南山区', receiver_address: '科技园路1号', express_company: '顺丰速运', express_no: 'SF1234567890', ship_time: '2026-09-01 14:00:00', items: [{ id: 1, product_id: 4, name: '示例商品', price: '299.00', quantity: 1, main_image: '' }] } }
const handlePay = () => { router.push(`/pay/${order.value.order_no}`) }
const handleConfirm = async () => { try { await ElMessageBox.confirm('确认已收到商品？', '提示', { type: 'warning' }); ElMessage.success('已确认收货'); order.value.status = 3 } catch (e) {} }
const handleAfterSale = () => { ElMessage.info('售后功能开发中') }
const handleReview = () => { ElMessage.info('评价功能开发中') }
onMounted(fetchOrder)
</script>
<style scoped>
.order-detail-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h2 { font-size: 22px; color: #333; margin: 0; }
.status-card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
.status-info { display: flex; align-items: center; gap: 16px; }
.status-info h3 { font-size: 20px; color: #333; margin: 0 0 4px 0; }
.status-info p { font-size: 13px; color: #999; margin: 0; }
.status-actions { display: flex; gap: 10px; }
.info-card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
.info-card h3 { font-size: 16px; color: #333; margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
.logistics-info, .address-info { }
.logistics-row, .address-row, .meta-row { display: flex; justify-content: space-between; font-size: 14px; color: #666; padding: 6px 0; }
.logistics-row span:first-child, .address-row span:first-child, .meta-row span:first-child { color: #999; }
.receiver { font-weight: bold; color: #333; }
.mobile { color: #666; }
.address-detail { font-size: 14px; color: #666; margin-top: 8px; line-height: 1.6; }
.order-items { }
.order-item { display: grid; grid-template-columns: 60px 1fr 80px 50px 80px; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f5f5f5; align-items: center; cursor: pointer; }
.order-item:last-child { border-bottom: none; }
.item-image { width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f5f5f5; }
.item-image img { width: 100%; height: 100%; object-fit: cover; }
.item-name { font-size: 14px; color: #333; margin-bottom: 4px; }
.item-spec { font-size: 12px; color: #999; }
.item-price { font-size: 14px; color: #333; text-align: right; }
.item-quantity { font-size: 14px; color: #666; text-align: center; }
.item-subtotal { font-size: 15px; color: #f56c6c; font-weight: bold; text-align: right; }
.order-total { margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0f0f0; }
.total-row { display: flex; justify-content: flex-end; gap: 40px; font-size: 14px; color: #666; padding: 4px 0; }
.total-row .free { color: #67c23a; }
.total-row.discount { color: #67c23a; }
.total-row.final { font-size: 16px; color: #333; padding-top: 8px; }
.pay-amount { font-size: 24px; color: #f56c6c; font-weight: bold; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .order-detail-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .page-header { flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
  .page-header h2 { font-size: 18px; }
  
  /* 状态卡片 */
  .status-card { padding: 16px; margin-bottom: 10px; border-radius: 6px; flex-wrap: wrap; gap: 12px; }
  .status-info { gap: 12px; }
  .status-info h3 { font-size: 16px; }
  .status-info p { font-size: 12px; }
  .status-actions { gap: 8px; flex-wrap: wrap; }
  .status-actions .el-button { font-size: 12px !important; padding: 6px 12px !important; }
  
  /* 信息卡片 */
  .info-card { padding: 14px; margin-bottom: 10px; border-radius: 6px; }
  .info-card h3 { font-size: 15px; margin-bottom: 10px; padding-bottom: 8px; }
  .logistics-row, .address-row, .meta-row { font-size: 13px; padding: 4px 0; flex-wrap: wrap; gap: 4px; }
  .receiver { font-size: 14px; }
  .mobile { font-size: 13px; }
  .address-detail { font-size: 13px; margin-top: 6px; line-height: 1.5; }
  
  /* 订单商品 - grid改flex卡片 */
  .order-item {
    display: flex !important;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px 0;
    align-items: flex-start;
  }
  .item-image { width: 60px; height: 60px; flex-shrink: 0; border-radius: 4px; }
  .item-name { font-size: 13px; margin-bottom: 2px; }
  .item-spec { font-size: 11px; color: #999; }
  .item-price { font-size: 12px; color: #666; text-align: left; }
  .item-quantity { font-size: 12px; color: #999; text-align: left; }
  .item-subtotal { font-size: 14px; text-align: left; }
  
  /* 订单总计 */
  .order-total { margin-top: 10px; padding-top: 10px; }
  .total-row { justify-content: space-between; gap: 8px; font-size: 13px; padding: 3px 0; }
  .total-row.final { font-size: 14px; padding-top: 6px; }
  .pay-amount { font-size: 20px; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .item-image { width: 50px; height: 50px; }
  .item-name { font-size: 12px; }
  .page-header h2 { font-size: 16px; }
}
</style>
