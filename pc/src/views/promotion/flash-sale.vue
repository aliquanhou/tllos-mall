<template>
  <div class="flash-sale-page">
    <div class="container">
      <PageHeader title="限时秒杀" subtitle="超值特惠 限时抢购" />
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
            <img loading="lazy" :src="product.main_image" :alt="product.name" />
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
import PageHeader from "@/components/PageHeader.vue"
import { ref, onMounted, onUnmounted } from 'vue'
import request from '@/utils/request'
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
const products = ref([])
const loading = ref(false)

// 获取限时秒杀商品（使用商品API，前端模拟秒杀价格）
const fetchProducts = async () => {
  loading.value = true
  try {
    // 尝试获取促销商品，如果没有则获取普通商品
    const res = await request({ url: '/products', method: 'get', params: { limit: 12, sort: 'sales' } })
    const list = res.data?.list || res.data || []
    products.value = list.map((item, index) => {
      const originPrice = parseFloat(item.price || item.market_price || 100)
      const discount = Math.min(0.3 + (index % 5) * 0.1, 0.8) // 模拟3-8折
      const salePrice = (originPrice * discount).toFixed(2)
      const stock = 50 + (index * 30) % 200
      const soldPercent = Math.min(30 + (index * 15) % 70, 100)
      return {
        id: item.id,
        name: item.name || item.title || '',
        main_image: item.main_image || item.image || '',
        sale_price: salePrice,
        origin_price: originPrice.toFixed(2),
        discount: (discount * 10).toFixed(1),
        stock: stock,
        sold_percent: soldPercent
      }
    })
  } catch (e) {
    console.error('获取秒杀商品失败:', e)
    products.value = []
  } finally {
    loading.value = false
  }
}
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
onMounted(() => { timer = setInterval(updateCountdown, 1000); fetchProducts() })
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

/* 移动端适配 - 列表页 */
@media (max-width: 768px) {
  .container { padding: 0 12px; }
  .filter-bar, .sort-bar { flex-wrap: wrap; gap: 8px; }
  .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .product-card { padding: 8px; }
  .product-image { aspect-ratio: 1; }
  .product-name { font-size: 12px; line-height: 1.4; }
  .product-price { font-size: 14px; }
  .pagination { justify-content: center; }
  .el-pagination { font-size: 12px; }
}

</style>
