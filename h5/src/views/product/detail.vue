<template>
  <div class="product-detail">
    <div class="detail-header">
      <button class="back-btn" @click="$router.back()">←</button>
      <span>{{ t('product.detail') }}</span>
    </div>
    <div class="product-gallery">
      <div class="main-image" :style="{ background: product.color }"></div>
      <div class="thumb-list">
        <div v-for="i in 4" :key="i" class="thumb" :style="{ background: product.color, opacity: i === 1 ? 1 : 0.5 }"></div>
      </div>
    </div>
    <div class="product-info card">
      <div class="price-row">
        <span class="price-large">¥{{ product.price }}</span>
        <span class="original-price">¥{{ product.originalPrice }}</span>
        <span class="sales">{{ product.sales }}{{ t('product.sold') }}</span>
      </div>
      <h1 class="product-title">{{ product.name }}</h1>
      <p class="product-desc">{{ product.desc }}</p>
    </div>
    <div class="spec-section card">
      <div class="spec-title">{{ t('product.specs') }}</div>
      <div class="spec-tags">
        <span v-for="spec in product.specs" :key="spec" class="spec-tag">{{ spec }}</span>
      </div>
      <div class="stock-info">
        <span>{{ t('product.stock') }}: {{ product.stock }}</span>
        <span>{{ t('product.shipping') }}: 包邮</span>
      </div>
    </div>
    <div class="desc-section card">
      <div class="spec-title">{{ t('product.description') }}</div>
      <div class="desc-content">
        <p>{{ product.desc }}</p>
        <p>这是 TLLOS 全新架构商城系统的商品详情页演示。系统采用 Laravel 11 + Vue3 全新架构，支持 H5、小程序、Flutter APK 多端覆盖，17 大功能模块完整对等。</p>
      </div>
    </div>
    <div class="bottom-bar">
      <div class="bar-icons">
        <div class="bar-icon" @click="$router.push('/home')">🏠<span>首页</span></div>
        <div class="bar-icon" @click="$router.push('/cart')">🛒<span>购物车</span></div>
      </div>
      <button class="add-cart-btn" @click="addToCart">{{ t('common.add') }}</button>
      <button class="buy-btn" @click="buyNow">{{ t('common.buyNow') }}</button>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const product = ref({
  id: route.params.id,
  name: 'TLLOS 全新架构商城系统 多端适配演示商品',
  price: '999.00',
  originalPrice: '1999.00',
  sales: 1286,
  stock: 999,
  color: '#e3f2fd',
  desc: '采用 Laravel 11 + Vue3 全新架构，独立知识产权，17 大功能模块完整对等，支持 H5/小程序/Flutter APK 多端覆盖。',
  specs: ['标准版', '企业版', '旗舰版', '定制开发']
})
const addToCart = () => {
  cartStore.addItem({ id: product.value.id, name: product.value.name, price: parseFloat(product.value.price), color: product.value.color })
  alert('已加入购物车')
}
const buyNow = () => { alert('立即购买功能开发中') }
</script>
<style scoped>
.product-detail { padding-bottom: 70px; }
.detail-header { display: flex; align-items: center; padding: 12px 16px; background: #fff; position: sticky; top: 0; z-index: 10; }
.back-btn { background: none; border: none; font-size: 20px; cursor: pointer; margin-right: 12px; }
.product-gallery { background: #fff; padding-bottom: 12px; }
.main-image { width: 100%; height: 300px; }
.thumb-list { display: flex; gap: 8px; padding: 12px 16px 0; }
.thumb { width: 50px; height: 50px; border-radius: 6px; }
.product-info { margin: 10px; }
.price-row { display: flex; align-items: baseline; gap: 10px; margin-bottom: 8px; }
.price-large { font-size: 24px; color: var(--primary); font-weight: bold; }
.original-price { font-size: 13px; color: var(--text-secondary); text-decoration: line-through; }
.sales { margin-left: auto; font-size: 12px; color: var(--text-secondary); }
.product-title { font-size: 16px; line-height: 1.5; margin-bottom: 8px; }
.product-desc { font-size: 13px; color: var(--text-secondary); line-height: 1.6; }
.spec-section { margin: 0 10px 10px; }
.spec-title { font-size: 15px; font-weight: 500; margin-bottom: 12px; }
.spec-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.spec-tag { padding: 6px 14px; background: var(--bg); border-radius: 4px; font-size: 13px; }
.stock-info { display: flex; gap: 20px; font-size: 12px; color: var(--text-secondary); padding-top: 12px; border-top: 1px solid var(--border); }
.desc-section { margin: 0 10px 10px; }
.desc-content p { font-size: 13px; line-height: 1.8; color: var(--text); margin-bottom: 10px; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; display: flex; align-items: center; background: #fff; padding: 8px 12px; border-top: 1px solid var(--border); padding-bottom: calc(8px + env(safe-area-inset-bottom)); }
.bar-icons { display: flex; gap: 16px; margin-right: 12px; }
.bar-icon { display: flex; flex-direction: column; align-items: center; font-size: 10px; color: var(--text-secondary); }
.bar-icon div { font-size: 20px; }
.add-cart-btn { flex: 1; padding: 10px; background: var(--primary-light); color: #fff; border: none; border-radius: 20px 0 0 20px; font-size: 14px; cursor: pointer; }
.buy-btn { flex: 1; padding: 10px; background: var(--primary); color: #fff; border: none; border-radius: 0 20px 20px 0; font-size: 14px; cursor: pointer; }
</style>
