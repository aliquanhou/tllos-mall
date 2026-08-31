<template>
  <div class="product-detail-page" v-if="product">
    <div class="product-images">
      <img :src="product.main_image" :alt="product.name" />
    </div>
    <div class="product-info">
      <div class="price-row">
        <span class="price">¥{{ product.price }}</span>
        <span class="market-price" v-if="product.market_price">¥{{ product.market_price }}</span>
        <span class="sales">已售{{ product.sales }}</span>
      </div>
      <div class="product-name">{{ product.name }}</div>
      <div class="product-subtitle" v-if="product.subtitle">{{ product.subtitle }}</div>
    </div>
    <div class="section">
      <div class="section-title">商品详情</div>
      <div class="description" v-if="product.description">{{ product.description }}</div>
      <div class="desc-images" v-if="product.images && product.images.length">
        <img v-for="(img, idx) in product.images" :key="idx" :src="img" />
      </div>
    </div>
    <div class="bottom-bar">
      <div class="bar-item" @click="goCart">🛒<span>购物车</span></div>
      <div class="bar-item" @click="goHome">🏠<span>首页</span></div>
      <button class="add-cart-btn" @click="addToCart">加入购物车</button>
      <button class="buy-btn" @click="buyNow">立即购买</button>
    </div>
  </div>
  <div class="loading" v-else>加载中...</div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getProductDetail } from '@/api/product'
import { addToCart as addCartApi } from '@/api/cart'
const route = useRoute()
const router = useRouter()
const product = ref(null)
const fetchDetail = async () => {
  try {
    const res = await getProductDetail(route.params.id)
    product.value = res.data
  } catch (e) { console.error(e) }
}
const addToCart = async () => {
  try {
    await addCartApi({ product_id: product.value.id, quantity: 1 })
    alert('已加入购物车')
  } catch (e) { alert(e.message || '加入失败') }
}
const buyNow = () => { alert('即将跳转到结算页') }
const goCart = () => router.push('/cart')
const goHome = () => router.push('/')
onMounted(fetchDetail)
</script>
<style scoped>
.product-detail-page { padding-bottom: 60px; background: #f5f5f5; min-height: 100vh; }
.product-images img { width: 100%; height: 375px; object-fit: cover; }
.product-info { background: #fff; padding: 15px; }
.price-row { display: flex; align-items: baseline; gap: 10px; }
.price { font-size: 24px; color: #ff4444; font-weight: bold; }
.market-price { font-size: 14px; color: #999; text-decoration: line-through; }
.sales { font-size: 12px; color: #999; margin-left: auto; }
.product-name { font-size: 16px; color: #333; margin-top: 8px; font-weight: 500; }
.product-subtitle { font-size: 13px; color: #666; margin-top: 4px; }
.section { background: #fff; margin-top: 10px; padding: 15px; }
.section-title { font-size: 15px; font-weight: bold; color: #333; margin-bottom: 10px; }
.description { font-size: 14px; color: #666; line-height: 1.6; }
.desc-images img { width: 100%; margin-top: 10px; border-radius: 4px; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; align-items: center; padding: 8px 15px; border-top: 1px solid #eee; gap: 10px; }
.bar-item { display: flex; flex-direction: column; align-items: center; font-size: 10px; color: #666; gap: 2px; padding: 0 8px; }
.add-cart-btn { flex: 1; padding: 10px; background: #ffaa00; color: #fff; border: none; border-radius: 20px; font-size: 14px; }
.buy-btn { flex: 1; padding: 10px; background: #ff4444; color: #fff; border: none; border-radius: 20px; font-size: 14px; }
.loading { text-align: center; padding: 40px; color: #999; }
</style>
