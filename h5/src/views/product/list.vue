<template>
  <div class="product-list">
    <div class="list-header">
      <button class="back-btn" @click="$router.back()">←</button>
      <input class="search-input" v-model="keyword" placeholder="搜索商品" />
    </div>
    <div class="filter-tabs">
      <span v-for="tab in tabs" :key="tab.key" :class="{ active: currentTab === tab.key }" @click="currentTab = tab.key">{{ tab.name }}</span>
    </div>
    <div class="product-grid">
      <div v-for="p in products" :key="p.id" class="product-card" @click="$router.push(`/product/${p.id}`)">
        <div class="product-img" :style="{ background: p.color }"></div>
        <div class="product-info">
          <div class="product-name ellipsis-2">{{ p.name }}</div>
          <div class="product-bottom">
            <span class="price">{{ p.price }}</span>
            <span class="sales">{{ p.sales }}人付款</span>
          </div>
        </div>
      </div>
    </div>
    <div class="no-more">{{ t('common.noMore') }}</div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const keyword = ref('')
const currentTab = ref('all')
const tabs = [
  { key: 'all', name: '综合' },
  { key: 'sales', name: '销量' },
  { key: 'price', name: '价格' },
  { key: 'new', name: '新品' }
]
const products = [
  { id: 301, name: 'TLLOS 商城系统 标准版', price: '999.00', sales: 1286, color: '#e3f2fd' },
  { id: 302, name: '多商户 SaaS 解决方案', price: '2999.00', sales: 89, color: '#fce4ec' },
  { id: 303, name: '小程序+H5+APP 三端', price: '1999.00', sales: 234, color: '#f3e5f5' },
  { id: 304, name: '分销裂变营销系统', price: '599.00', sales: 445, color: '#e8f5e9' },
  { id: 305, name: '秒杀拼团优惠券工具包', price: '399.00', sales: 678, color: '#fff3e0' },
  { id: 306, name: 'Laravel 11 高性能后端', price: '499.00', sales: 356, color: '#e0f7fa' },
  { id: 307, name: 'Vue3 管理后台模板', price: '299.00', sales: 789, color: '#f9fbe7' },
  { id: 308, name: 'Flutter APK 端开发', price: '1599.00', sales: 67, color: '#eceff1' }
]
</script>
<style scoped>
.product-list { min-height: 100vh; background: var(--bg); }
.list-header { display: flex; align-items: center; padding: 10px 12px; background: #fff; gap: 10px; }
.back-btn { background: none; border: none; font-size: 20px; cursor: pointer; }
.search-input { flex: 1; padding: 8px 14px; background: var(--bg); border: none; border-radius: 18px; font-size: 13px; outline: none; }
.filter-tabs { display: flex; background: #fff; padding: 0 12px 10px; gap: 20px; border-bottom: 1px solid var(--border); }
.filter-tabs span { font-size: 13px; color: var(--text-secondary); padding-bottom: 8px; cursor: pointer; }
.filter-tabs span.active { color: var(--primary); font-weight: 500; border-bottom: 2px solid var(--primary); }
.product-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 8px; }
.product-card { background: #fff; border-radius: 8px; overflow: hidden; }
.product-img { width: 100%; height: 160px; }
.product-info { padding: 8px 10px; }
.product-name { font-size: 13px; line-height: 1.4; margin-bottom: 6px; height: 36px; }
.product-bottom { display: flex; justify-content: space-between; align-items: center; }
.sales { font-size: 11px; color: var(--text-secondary); }
.no-more { text-align: center; padding: 20px; font-size: 12px; color: var(--text-secondary); }
</style>
