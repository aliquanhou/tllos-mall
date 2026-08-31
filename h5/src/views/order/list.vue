<template>
  <div class="order-list">
    <div class="list-header">
      <button class="back-btn" @click="$router.back()">←</button>
      <span>{{ t('order.title') }}</span>
    </div>
    <div class="order-tabs">
      <span v-for="tab in tabs" :key="tab.key" :class="{ active: currentTab === tab.key }" @click="currentTab = tab.key">{{ t(tab.name) }}</span>
    </div>
    <div class="order-cards">
      <div v-for="order in orders" :key="order.id" class="order-card card">
        <div class="order-header">
          <span class="order-no">{{ t('order.orderNo') }}: {{ order.orderNo }}</span>
          <span class="order-status" :class="order.statusClass">{{ order.status }}</span>
        </div>
        <div class="order-items">
          <div v-for="item in order.items" :key="item.id" class="order-item">
            <div class="item-img" :style="{ background: item.color }"></div>
            <div class="item-info">
              <div class="item-name ellipsis">{{ item.name }}</div>
              <div class="item-price">¥{{ item.price }} × {{ item.quantity }}</div>
            </div>
          </div>
        </div>
        <div class="order-footer">
          <span class="order-total">共{{ order.totalCount }}件 合计: <span class="price">¥{{ order.totalAmount }}</span></span>
        </div>
        <div class="order-actions">
          <button v-if="order.status === '待付款'" class="action-btn primary" @click="payOrder(order)">立即付款</button>
          <button v-if="order.status === '待发货'" class="action-btn" @click="remindOrder(order)">提醒发货</button>
          <button v-if="order.status === '待收货'" class="action-btn primary" @click="confirmOrder(order)">确认收货</button>
          <button class="action-btn" @click="viewDetail(order)">订单详情</button>
        </div>
      </div>
    </div>
    <div v-if="orders.length === 0" class="empty">{{ t('common.empty') }}</div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const currentTab = ref('all')
const tabs = [
  { key: 'all', name: 'order.all' },
  { key: 'pending', name: 'order.pending' },
  { key: 'paid', name: 'order.paid' },
  { key: 'shipped', name: 'order.shipped' },
  { key: 'completed', name: 'order.completed' }
]
const allOrders = [
  { id: 1, orderNo: 'TLL202608310001', status: '待付款', statusClass: 'pending', totalCount: 2, totalAmount: '1298.00',
    items: [{ id: 101, name: 'TLLOS 商城系统 标准版', price: '999.00', quantity: 1, color: '#e3f2fd' }, { id: 102, name: '秒杀拼团优惠券工具包', price: '299.00', quantity: 1, color: '#fff3e0' }] },
  { id: 2, orderNo: 'TLL202608300002', status: '待发货', statusClass: 'paid', totalCount: 1, totalAmount: '2999.00',
    items: [{ id: 201, name: '多商户 SaaS 解决方案', price: '2999.00', quantity: 1, color: '#fce4ec' }] },
  { id: 3, orderNo: 'TLL202608290003', status: '待收货', statusClass: 'shipped', totalCount: 1, totalAmount: '1999.00',
    items: [{ id: 301, name: '小程序+H5+APP 三端合一', price: '1999.00', quantity: 1, color: '#f3e5f5' }] },
  { id: 4, orderNo: 'TLL202608280004', status: '已完成', statusClass: 'completed', totalCount: 3, totalAmount: '1497.00',
    items: [{ id: 401, name: '分销裂变营销系统', price: '599.00', quantity: 1, color: '#e8f5e9' }, { id: 402, name: 'Vue3 管理后台模板', price: '299.00', quantity: 2, color: '#e0f7fa' }] }
]
const orders = computed(() => currentTab.value === 'all' ? allOrders : allOrders.filter(o => o.statusClass === currentTab.value))
const payOrder = order => alert('支付功能开发中')
const remindOrder = order => alert('已提醒商家发货')
const confirmOrder = order => alert('确认收货功能开发中')
const viewDetail = order => alert('订单详情开发中')
</script>
<style scoped>
.order-list { min-height: 100vh; background: var(--bg); }
.list-header { display: flex; align-items: center; padding: 12px 16px; background: #fff; font-size: 16px; font-weight: 500; }
.back-btn { background: none; border: none; font-size: 20px; cursor: pointer; margin-right: 12px; }
.order-tabs { display: flex; background: #fff; padding: 0 12px; border-bottom: 1px solid var(--border); overflow-x: auto; }
.order-tabs span { padding: 12px 14px; font-size: 13px; color: var(--text-secondary); white-space: nowrap; cursor: pointer; }
.order-tabs span.active { color: var(--primary); font-weight: 500; border-bottom: 2px solid var(--primary); }
.order-cards { padding: 10px; }
.order-card { margin-bottom: 10px; padding: 0; }
.order-header { display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid var(--border); font-size: 12px; }
.order-no { color: var(--text-secondary); }
.order-status { font-weight: 500; }
.order-status.pending { color: var(--danger); }
.order-status.paid { color: var(--warning); }
.order-status.shipped { color: var(--primary); }
.order-status.completed { color: var(--success); }
.order-items { padding: 12px; }
.order-item { display: flex; gap: 10px; margin-bottom: 10px; }
.order-item:last-child { margin-bottom: 0; }
.item-img { width: 60px; height: 60px; border-radius: 6px; flex-shrink: 0; }
.item-info { flex: 1; min-width: 0; }
.item-name { font-size: 13px; margin-bottom: 4px; }
.item-price { font-size: 12px; color: var(--text-secondary); }
.order-footer { padding: 10px 12px; border-top: 1px solid var(--border); text-align: right; font-size: 13px; }
.order-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 12px; border-top: 1px solid var(--border); }
.action-btn { padding: 6px 16px; border: 1px solid var(--border); background: #fff; border-radius: 16px; font-size: 12px; cursor: pointer; color: var(--text); }
.action-btn.primary { background: var(--primary); color: #fff; border-color: var(--primary); }
.empty { text-align: center; padding: 60px; color: var(--text-secondary); font-size: 14px; }
</style>
