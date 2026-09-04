<template>
  <div class="home-page">
    <!-- 主Banner区：左右分栏 -->
    <div class="banner-section">
      <div class="container">
        <div class="banner-wrapper">
          <!-- 左侧：促销信息 -->
          <div class="banner-left">
            <div class="countdown">
              <div class="countdown-item"><span class="num">{{ days }}</span><span class="label">天</span></div>
              <div class="countdown-item"><span class="num">{{ hours }}</span><span class="label">时</span></div>
              <div class="countdown-item"><span class="num">{{ minutes }}</span><span class="label">分</span></div>
              <div class="countdown-item"><span class="num">{{ seconds }}</span><span class="label">秒</span></div>
            </div>
            <div class="promo-tags">
              <span class="tag tag-online">线上专享</span>
              <span class="tag tag-date">限时48小时</span>
            </div>
            <div class="promo-title">限时特惠</div>
            <div class="promo-subtitle">全场低至</div>
            <div class="promo-discount">5折起</div>
            <el-button type="warning" size="large" class="shop-all-btn" @click="goProducts">立即抢购</el-button>
          </div>
          <!-- 右侧：商品展示 -->
          <div class="banner-right" @click="goDetail(1)">
            <div class="banner-product-img">
              <div class="product-placeholder">
                <el-icon size="80"><Goods /></el-icon>
                <p>精选男装</p>
              </div>
            </div>
            <div class="banner-product-info">
              <h3>品质男装</h3>
              <p class="start-price">起步价</p>
              <p class="price">¥99<span class="price-unit">起</span></p>
              <p class="original-price">原价 ¥299 起</p>
              <span class="shop-now">立即购买 →</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 分类导航 -->
    <div class="category-section" v-if="categories.length">
      <div class="container">
        <div class="category-grid">
          <div class="category-item" v-for="cat in categories.slice(0, 10)" :key="cat.id" @click="goCategory(cat.id)">
            <div class="category-icon"><el-icon size="28"><CollectionTag /></el-icon></div>
            <span class="category-name">{{ cat.name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 第二Banner区：左右分栏 -->
    <div class="second-banner">
      <div class="container">
        <div class="second-banner-wrapper">
          <div class="second-banner-left" @click="goProducts">
            <div class="second-banner-img">
              <el-icon size="60"><Tshirt /></el-icon>
            </div>
          </div>
          <div class="second-banner-right">
            <h3>新品上市</h3>
            <p class="start-price">起步价</p>
            <p class="price">¥149<span class="price-unit">起</span></p>
            <p class="original-price">原价 ¥399 起</p>
            <el-button type="primary" size="large" @click="goProducts">查看详情</el-button>
          </div>
        </div>
      </div>
    </div>

    <!-- 热门推荐商品 -->
    <div class="product-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">🔥 热门推荐</h2>
          <a href="javascript:;" class="more-link" @click="goProducts">查看更多 →</a>
        </div>
        <div class="product-grid" v-if="hotProducts.length">
          <div class="product-card" v-for="p in hotProducts.slice(0, 10)" :key="p.id" @click="goDetail(p.id)">
            <div class="product-tag" v-if="p.sales > 50">热销</div>
            <div class="product-image">
              <img :src="p.main_image || '/pc/assets/placeholder.png'" :alt="p.name" @error="imgError($event)" />
            </div>
            <div class="product-info">
              <div class="product-name">{{ p.name }}</div>
              <div class="product-bottom">
                <span class="product-price">¥{{ p.price }}</span>
                <span class="product-sales">已售{{ p.sales || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="empty-tip" v-else>暂无商品</div>
      </div>
    </div>

    <!-- 新品上市 -->
    <div class="product-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">✨ 新品上市</h2>
          <a href="javascript:;" class="more-link" @click="goProducts">查看更多 →</a>
        </div>
        <div class="product-grid" v-if="newProducts.length">
          <div class="product-card" v-for="p in newProducts.slice(0, 10)" :key="p.id" @click="goDetail(p.id)">
            <div class="product-tag tag-new">新品</div>
            <div class="product-image">
              <img :src="p.main_image || '/pc/assets/placeholder.png'" :alt="p.name" @error="imgError($event)" />
            </div>
            <div class="product-info">
              <div class="product-name">{{ p.name }}</div>
              <div class="product-bottom">
                <span class="product-price">¥{{ p.price }}</span>
                <span class="product-sales">已售{{ p.sales || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="empty-tip" v-else>暂无商品</div>
      </div>
    </div>

    <!-- 为你推荐 -->
    <div class="product-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">🛍️ 为你推荐</h2>
        </div>
        <div class="product-grid" v-if="recommendProducts.length">
          <div class="product-card" v-for="p in recommendProducts" :key="p.id" @click="goDetail(p.id)">
            <div class="product-image">
              <img :src="p.main_image || '/pc/assets/placeholder.png'" :alt="p.name" @error="imgError($event)" />
            </div>
            <div class="product-info">
              <div class="product-name">{{ p.name }}</div>
              <div class="product-bottom">
                <span class="product-price">¥{{ p.price }}</span>
                <span class="product-sales">已售{{ p.sales || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="empty-tip" v-else>暂无商品</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { getHomeData } from '@/api/home'

const router = useRouter()
const banners = ref([])
const categories = ref([])
const hotProducts = ref([])
const newProducts = ref([])
const recommendProducts = ref([])

// 倒计时
const days = ref('00')
const hours = ref('00')
const minutes = ref('00')
const seconds = ref('00')
let countdownTimer = null

const startCountdown = () => {
  const target = Date.now() + 48 * 60 * 60 * 1000
  const update = () => {
    const diff = Math.max(0, target - Date.now())
    const d = Math.floor(diff / 86400000)
    const h = Math.floor((diff % 86400000) / 3600000)
    const m = Math.floor((diff % 3600000) / 60000)
    const s = Math.floor((diff % 60000) / 1000)
    days.value = String(d).padStart(2, '0')
    hours.value = String(h).padStart(2, '0')
    minutes.value = String(m).padStart(2, '0')
    seconds.value = String(s).padStart(2, '0')
  }
  update()
  countdownTimer = setInterval(update, 1000)
}

const fetchHome = async () => {
  try {
    const res = await getHomeData()
    banners.value = res.data.banners || []
    categories.value = res.data.categories || []
    hotProducts.value = res.data.hot_products || []
    newProducts.value = res.data.new_products || []
    recommendProducts.value = res.data.recommend_products || []
  } catch (e) { console.error(e) }
}

const goDetail = id => router.push(`/product/${id}`)
const goCategory = id => router.push({ path: '/products', query: { category_id: id } })
const goProducts = () => router.push('/products')
const imgError = (e) => { e.target.style.display = 'none' }

onMounted(() => { fetchHome(); startCountdown() })
onUnmounted(() => { if (countdownTimer) clearInterval(countdownTimer) })
</script>

<style scoped>
.home-page { background: #f5f5f5; padding-bottom: 40px; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

/* 主Banner */
.banner-section { padding: 20px 0; }
.banner-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border-radius: 8px; overflow: hidden; height: 360px; }
.banner-left { background: linear-gradient(135deg, #fff8e6, #ffe4b5); padding: 30px; display: flex; flex-direction: column; justify-content: center; }
.countdown { display: flex; gap: 12px; margin-bottom: 20px; }
.countdown-item { display: flex; flex-direction: column; align-items: center; }
.countdown-item .num { background: #e6a23c; color: #fff; font-size: 24px; font-weight: bold; padding: 6px 10px; border-radius: 4px; min-width: 40px; text-align: center; }
.countdown-item .label { font-size: 12px; color: #999; margin-top: 4px; }
.promo-tags { display: flex; gap: 10px; margin-bottom: 12px; }
.tag { padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.tag-online { background: #e6a23c; color: #fff; }
.tag-date { background: #fff; color: #e6a23c; border: 1px solid #e6a23c; }
.promo-title { font-size: 28px; font-weight: bold; color: #333; margin-bottom: 8px; }
.promo-subtitle { font-size: 16px; color: #666; margin-bottom: 8px; }
.promo-discount { font-size: 48px; font-weight: bold; color: #f56c6c; margin-bottom: 20px; }
.shop-all-btn { width: 160px; }

.banner-right { background: #2c3e50; display: flex; align-items: center; padding: 30px; cursor: pointer; position: relative; }
.banner-product-img { flex: 1; display: flex; align-items: center; justify-content: center; }
.product-placeholder { text-align: center; color: rgba(255,255,255,0.3); }
.product-placeholder p { margin-top: 10px; font-size: 14px; }
.banner-product-info { color: #fff; text-align: center; }
.banner-product-info h3 { font-size: 20px; margin-bottom: 10px; }
.start-price { font-size: 12px; color: rgba(255,255,255,0.6); margin-bottom: 4px; }
.price { font-size: 36px; font-weight: bold; color: #e6a23c; margin: 0; }
.price-unit { font-size: 16px; }
.original-price { font-size: 13px; color: rgba(255,255,255,0.5); text-decoration: line-through; margin: 8px 0; }
.shop-now { display: inline-block; background: #e6a23c; color: #fff; padding: 8px 20px; border-radius: 4px; font-size: 14px; margin-top: 10px; }

/* 分类导航 */
.category-section { background: #fff; padding: 20px 0; margin-bottom: 20px; }
.category-grid { display: grid; grid-template-columns: repeat(10, 1fr); gap: 10px; }
.category-item { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 15px 5px; border-radius: 8px; cursor: pointer; transition: background 0.2s; }
.category-item:hover { background: #f5f7fa; }
.category-icon { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #fdf6ec, #faecd8); color: #e6a23c; display: flex; align-items: center; justify-content: center; }
.category-name { font-size: 13px; color: #333; }

/* 第二Banner */
.second-banner { margin-bottom: 20px; }
.second-banner-wrapper { display: grid; grid-template-columns: 2fr 1fr; border-radius: 8px; overflow: hidden; height: 200px; }
.second-banner-left { background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.3); cursor: pointer; }
.second-banner-right { background: #1a1a2e; color: #fff; padding: 25px; display: flex; flex-direction: column; justify-content: center; }
.second-banner-right h3 { font-size: 22px; margin: 0 0 8px 0; }

/* 商品区 */
.product-section { background: #fff; padding: 25px 0; margin-bottom: 20px; border-radius: 8px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.section-title { font-size: 20px; font-weight: bold; color: #333; margin: 0; }
.more-link { color: #999; font-size: 14px; text-decoration: none; cursor: pointer; }
.more-link:hover { color: #e6a23c; }
.product-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; }
.product-card { border: 1px solid #eee; border-radius: 8px; overflow: hidden; background: #fff; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; position: relative; }
.product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.product-tag { position: absolute; top: 10px; left: 10px; background: #f56c6c; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; z-index: 1; }
.product-tag.tag-new { background: #67c23a; }
.product-image { width: 100%; height: 180px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; }
.product-image img { width: 100%; height: 100%; object-fit: cover; }
.product-info { padding: 12px; }
.product-name { font-size: 13px; color: #333; line-height: 1.4; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.product-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
.product-price { font-size: 16px; color: #f56c6c; font-weight: bold; }
.product-sales { font-size: 11px; color: #999; }
.empty-tip { text-align: center; padding: 40px; color: #999; }
</style>
