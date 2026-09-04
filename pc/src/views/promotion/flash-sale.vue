<template>
  <div class="flash-sale-page">
    <div class="container">
      <!-- 活动头部 -->
      <div class="promo-header">
        <div class="promo-title">
          <el-icon :size="32" color="#fff"><Discount /></el-icon>
          <h1>限时特惠</h1>
        </div>
        <div class="promo-countdown">
          <span class="countdown-label">距结束还剩</span>
          <div class="countdown-time">
            <span class="time-box">{{ hours }}</span>
            <span class="time-sep">:</span>
            <span class="time-box">{{ minutes }}</span>
            <span class="time-sep">:</span>
            <span class="time-box">{{ seconds }}</span>
          </div>
        </div>
      </div>
      <!-- 活动场次 -->
      <div class="session-bar">
        <div class="session-item" v-for="session in sessions" :key="session.time" :class="{active: activeSession === session.time, ended: session.ended}">
          <div class="session-time">{{ session.time }}</div>
          <div class="session-status">{{ session.ended ? '已结束' : (session.active ? '抢购中' : '即将开始') }}</div>
        </div>
      </div>
      <!-- 商品列表 -->
      <div class="product-grid">
        <div class="product-card" v-for="product in products" :key="product.id" @click="$router.push(`/product/${product.id}`)">
          <div class="product-image">
            <img :src="product.main_image" :alt="product.name" />
            <div class="discount-tag">{{ product.discount }}折</div>
            <div class="sold-progress" v-if="product.sold_percent > 0">
              <div class="progress-bar" :style="{width: product.sold_percent + '%'}"></div>
              <span class="progress-text">已抢{{ product.sold_percent }}%</span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">{{ product.name }}</div>
            <div class="price-row">
              <span class="sale-price">¥{{ product.sale_price }}</span>
              <span class="origin-price">¥{{ product.origin_price }}</span>
            </div>
            <div class="action-row">
              <span class="stock-info">库存{{ product.stock }}件</span>
              <el-button type="danger" size="small" :disabled="product.sold_percent >= 100">
                {{ product.sold_percent >= 100 ? '已抢光' : '立即抢购' }}
              </el-button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
const hours = ref('02')
const minutes = ref('35')
const seconds = ref('18')
const activeSession = ref('10:00')
const sessions = [
  { time: '08:00', ended: true, active: false },
  { time: '10:00', ended: false, active: true },
  { time: '12:00', ended: false, active: false },
  { time: '14:00', ended: false, active: false },
  { time: '16:00', ended: false, active: false },
  { time: '20:00', ended: false, active: false },
]
const products = ref([
  { id: 1, name: '限时特惠商品1', main_image: '', sale_price: '99.00', origin_price: '299.00', discount: 3.3, stock: 100, sold_percent: 65 },
  { id: 2, name: '限时特惠商品2', main_image: '', sale_price: '199.00', origin_price: '599.00', discount: 3.3, stock: 50, sold_percent: 80 },
  { id: 3, name: '限时特惠商品3', main_image: '', sale_price: '49.00', origin_price: '149.00', discount: 3.3, stock: 200, sold_percent: 45 },
  { id: 4, name: '限时特惠商品4', main_image: '', sale_price: '299.00', origin_price: '899.00', discount: 3.3, stock: 30, sold_percent: 100 },
  { id: 5, name: '限时特惠商品5', main_image: '', sale_price: '159.00', origin_price: '459.00', discount: 3.5, stock: 80, sold_percent: 55 },
  { id: 6, name: '限时特惠商品6', main_image: '', sale_price: '79.00', origin_price: '239.00', discount: 3.3, stock: 150, sold_percent: 30 },
  { id: 7, name: '限时特惠商品7', main_image: '', sale_price: '399.00', origin_price: '1199.00', discount: 3.3, stock: 20, sold_percent: 90 },
  { id: 8, name: '限时特惠商品8', main_image: '', sale_price: '129.00', origin_price: '389.00', discount: 3.3, stock: 60, sold_percent: 70 },
])
let timer = null
const updateCountdown = () => {
  let h = parseInt(hours.value), m = parseInt(minutes.value), s = parseInt(seconds.value)
  s--
  if (s < 0) { s = 59; m-- }
  if (m < 0) { m = 59; h-- }
  if (h < 0) { h = 0; m = 0; s = 0 }
  hours.value = String(h).padStart(2, '0')
  minutes.value = String(m).padStart(2, '0')
  seconds.value = String(s).padStart(2, '0')
}
onMounted(() => { timer = setInterval(updateCountdown, 1000) })
onUnmounted(() => { if (timer) clearInterval(timer) })
</script>
<style scoped>
.flash-sale-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.promo-header { background: linear-gradient(135deg, #f56c6c, #e64c4c); border-radius: 12px; padding: 32px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; color: #fff; }
.promo-title { display: flex; align-items: center; gap: 16px; }
.promo-title h1 { font-size: 32px; margin: 0; }
.promo-countdown { display: flex; align-items: center; gap: 16px; }
.countdown-label { font-size: 14px; opacity: 0.9; }
.countdown-time { display: flex; align-items: center; gap: 4px; }
.time-box { background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 6px; font-size: 24px; font-weight: bold; min-width: 48px; text-align: center; }
.time-sep { font-size: 24px; font-weight: bold; }
.session-bar { background: #fff; border-radius: 8px; padding: 0; margin-bottom: 20px; display: flex; overflow: hidden; }
.session-item { flex: 1; padding: 16px; text-align: center; cursor: pointer; border-right: 1px solid #f5f5f5; transition: all 0.2s; }
.session-item:last-child { border-right: none; }
.session-item:hover { background: #fafafa; }
.session-item.active { background: #fdf6ec; }
.session-item.active .session-time { color: #e6a23c; }
.session-item.ended { opacity: 0.5; }
.session-time { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 4px; }
.session-status { font-size: 12px; color: #999; }
.product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.product-card { background: #fff; border-radius: 8px; overflow: hidden; cursor: pointer; transition: all 0.2s; }
.product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.product-image { position: relative; padding-top: 100%; background: #fafafa; }
.product-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.discount-tag { position: absolute; top: 10px; left: 10px; background: #f56c6c; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; }
.sold-progress { position: absolute; bottom: 0; left: 0; right: 0; height: 24px; background: rgba(0,0,0,0.5); }
.progress-bar { position: absolute; top: 0; left: 0; height: 100%; background: linear-gradient(90deg, #f56c6c, #e64c4c); }
.progress-text { position: absolute; top: 0; left: 0; right: 0; height: 100%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; }
.product-info { padding: 12px; }
.product-name { font-size: 14px; color: #333; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 8px; }
.price-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 10px; }
.sale-price { font-size: 20px; color: #f56c6c; font-weight: bold; }
.origin-price { font-size: 13px; color: #ccc; text-decoration: line-through; }
.action-row { display: flex; justify-content: space-between; align-items: center; }
.stock-info { font-size: 12px; color: #999; }
</style>
