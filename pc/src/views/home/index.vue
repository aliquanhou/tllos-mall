<template>
  <div class="home-page">
    <!-- 顶部激励通栏 -->
    <div class="promo-bar">
      <div class="promo-content">
        <el-icon><Gift /></el-icon>
        <span class="promo-text">注册立享8折 · 新用户专享 · 积分支付上线</span>
        <el-button class="promo-btn" size="small" @click="goRegister">立即注册</el-button>
      </div>
    </div>

    <!-- 顶部Banner轮播 -->
    <div class="banner-section">
      <el-carousel :interval="4000" arrow="never" height="100%" class="main-banner">
        <el-carousel-item v-for="(banner, index) in banners" :key="index">
          <div class="banner-item" :style="{ backgroundImage: 'url(' + banner.image + ')' }">
            <div class="banner-overlay">
              <div class="banner-tag">{{ banner.tag }}</div>
              <h2 class="banner-title">{{ banner.title }}</h2>
              <p class="banner-subtitle">{{ banner.subtitle }}</p>
              <el-button class="banner-btn" @click="goProductList">{{ banner.btnText }}</el-button>
            </div>
          </div>
        </el-carousel-item>
      </el-carousel>
    </div>

    <!-- 服务承诺栏 -->
    <div class="service-bar">
      <div class="service-item">
        <el-icon :size="24" color="#ff6b00"><Goods /></el-icon>
        <div class="service-text">
          <span class="service-title">{{ t('home.freeShipping') }}</span>
          <span class="service-desc">{{ t('home.freeShippingDesc') }}</span>
        </div>
      </div>
      <div class="service-divider"></div>
      <div class="service-item">
        <el-icon :size="24" color="#ff6b00"><RefreshLeft /></el-icon>
        <div class="service-text">
          <span class="service-title">{{ t('home.freeReturn') }}</span>
          <span class="service-desc">{{ t('home.freeReturnDesc') }}</span>
        </div>
      </div>
      <div class="service-divider" v-if="!isMobile"></div>
      <div class="service-item" v-if="!isMobile">
        <el-icon :size="24" color="#ff6b00"><Lock /></el-icon>
        <div class="service-text">
          <span class="service-title">{{ t('home.securePayment') }}</span>
          <span class="service-desc">{{ t('home.securePaymentDesc') }}</span>
        </div>
      </div>
      <div class="service-divider" v-if="!isMobile"></div>
      <div class="service-item" v-if="!isMobile">
        <el-icon :size="24" color="#ff6b00"><Service /></el-icon>
        <div class="service-text">
          <span class="service-title">{{ t('home.support247') }}</span>
          <span class="service-desc">{{ t('home.support247Desc') }}</span>
        </div>
      </div>
    </div>

    <!-- 分类导航（金刚区） -->
    <div class="category-section">
      <div class="category-grid" v-if="categories.length">
        <!-- NEW IN 入口（优先级最高） -->
        <div class="category-item new-in-item" @click="goNewIn">
          <div class="category-icon new-in-icon">
            <el-icon :size="28"><Sunny /></el-icon>
          </div>
          <span class="category-name new-in-name">NEW IN</span>
        </div>
        <div class="category-item" v-for="cat in categories.slice(0, 9)" :key="cat.id" @click="goCategory(cat.id)">
          <div class="category-icon">
            <span v-if="!getCategoryIcon(cat)" class="category-emoji">{{ getCategoryEmoji(cat) }}</span>
            <img v-else :src="getCategoryIcon(cat)" :alt="cat.name" @error="handleCategoryIconError($event)" />
          </div>
          <span class="category-name">{{ cat.name }}</span>
        </div>
      </div>
      <div class="category-grid" v-else>
        <div class="category-item skeleton-item" v-for="i in 10" :key="i">
          <div class="category-icon skeleton-circle"></div>
          <span class="category-name skeleton-line"></span>
        </div>
      </div>
    </div>

    <!-- 限时秒杀 -->
    <div class="flash-sale-section" v-if="flashProducts.length">
      <div class="section-header flash-header">
        <div class="flash-title">
          <el-icon :size="20"><Star /></el-icon>
          <span>{{ t('home.flashSale') }}</span>
        </div>
        <div class="flash-countdown">
          <span class="countdown-label">{{ t('home.endsIn') }}</span>
          <span class="countdown-time">{{ countdown }}</span>
        </div>
        <router-link to="/products?sort=sales" class="view-all">{{ t('home.viewAll') }} ></router-link>
      </div>
      <div class="flash-products">
        <div class="flash-product-card" v-for="product in flashProducts" :key="product.id" @click="goProductDetail(product.id)">
          <div class="flash-product-img">
            <img :src="getProductImage(product)" :alt="product.name" @error="handleProductImgError($event, product)" />
            <div class="flash-discount" v-if="product.market_price && product.market_price > product.price">{{ getDiscount(product) }}%</div>
          </div>
          <div class="flash-product-info">
            <div class="flash-product-name">{{ product.name }}</div>
            <div class="flash-product-price">
              <span class="current-price">¥{{ Number(product.price).toFixed(2) }}</span>
              <span class="original-price" v-if="product.market_price && product.market_price > product.price">¥{{ Number(product.market_price).toFixed(2) }}</span>
            </div>
            <div class="flash-progress">
              <div class="progress-bar" :style="{ width: Math.min(product.sales * 10, 95) + '%' }"></div>
              <span class="sold-text">{{ t('home.sold') }} {{ product.sales || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 新品上架 -->
    <div class="new-arrivals-section">
      <div class="section-header">
        <h3 class="section-title">{{ t('home.newArrivals') }}</h3>
        <router-link to="/products?sort=new" class="view-all">{{ t('home.viewAll') }} ></router-link>
      </div>
      <div class="product-grid" v-if="newProducts.length">
        <div class="product-card" v-for="product in newProducts" :key="product.id" @click="goProductDetail(product.id)">
          <div class="product-image">
            <img :src="getProductImage(product)" :alt="product.name" @error="handleProductImgError($event, product)" />
            <div class="product-badges">
              <span class="badge badge-new" v-if="isNewProduct(product)">{{ t('home.newTag') }}</span>
              <span class="badge badge-hot" v-if="product.sales > 50">{{ t('home.hotTag') }}</span>
            </div>
            <div class="product-actions">
              <el-button class="action-btn" size="small" circle @click.stop="addToCart(product)">
                <el-icon><ShoppingCart /></el-icon>
              </el-button>
              <el-button class="action-btn favorite-btn" size="small" circle :class="{active: product.favorite}" @click.stop="toggleFavorite(product)">
                <el-icon><Star v-if="!product.favorite" /><StarFilled v-else /></el-icon>
              </el-button>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">{{ product.name }}</div>
            <div class="product-price-row">
              <span class="product-price">¥{{ Number(product.price).toFixed(2) }}</span>
              <span class="product-original" v-if="product.market_price && product.market_price > product.price">¥{{ Number(product.market_price).toFixed(2) }}</span>
            </div>
            <div class="product-meta">
              <span class="product-sales">{{ t('home.sold') }} {{ product.sales || 0 }}</span>
              <span class="product-free" v-if="product.free_shipping !== false">{{ t('home.freeShippingTag') }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="product-grid" v-else>
        <div class="product-card skeleton-card" v-for="i in 8" :key="i">
          <div class="product-image skeleton-image"></div>
          <div class="product-info">
            <div class="skeleton-line" style="width: 90%"></div>
            <div class="skeleton-line" style="width: 60%"></div>
            <div class="skeleton-line" style="width: 40%"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 潮流趋势（TREND） -->
    <div class="trend-section">
      <div class="section-header trend-header">
        <h3 class="section-title">TREND 潮流趋势</h3>
        <span class="trend-subtitle">穿搭灵感 · 场景化搭配</span>
      </div>
      <div class="trend-grid">
        <div class="trend-card" v-for="(trend, index) in trendItems" :key="index" @click="goProductDetail(trend.product_id)">
          <div class="trend-image">
            <img :src="trend.image" :alt="trend.title" @error="handleTrendImgError($event)" />
            <div class="trend-overlay">
              <span class="trend-tag">{{ trend.tag }}</span>
              <span class="trend-title">{{ trend.title }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 热门商品 -->
    <div class="hot-products-section" v-if="hotProducts.length">
      <div class="section-header">
        <h3 class="section-title">{{ t('home.hotProducts') }}</h3>
        <router-link to="/products?sort=sales" class="view-all">{{ t('home.viewAll') }} ></router-link>
      </div>
      <div class="product-grid">
        <div class="product-card" v-for="product in hotProducts" :key="product.id" @click="goProductDetail(product.id)">
          <div class="product-image">
            <img :src="getProductImage(product)" :alt="product.name" @error="handleProductImgError($event, product)" />
            <div class="product-badges">
              <span class="badge badge-hot">{{ t('home.hotTag') }}</span>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">{{ product.name }}</div>
            <div class="product-price-row">
              <span class="product-price">¥{{ Number(product.price).toFixed(2) }}</span>
            </div>
            <div class="product-meta">
              <span class="product-sales">{{ t('home.sold') }} {{ product.sales || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 品牌专区 -->
    <div class="brand-section" v-if="brands.length">
      <div class="section-header">
        <h3 class="section-title">{{ t('home.brandZone') }}</h3>
      </div>
      <div class="brand-grid">
        <div class="brand-item" v-for="brand in brands" :key="brand.id">
          <div class="brand-logo">
            <span>{{ brand.name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- APP推广 -->
    <div class="app-promo" v-if="!isMobile">
      <div class="app-promo-content">
        <h3>{{ t('home.downloadApp') }}</h3>
        <p>{{ t('home.appDesc') }}</p>
        <el-button type="primary" size="large">{{ t('home.downloadNow') }}</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { getProductList, getCategories, getHotProducts, getNewProducts } from '@/api/product'

const { t } = useI18n()
const router = useRouter()

const isMobile = ref(false)
const countdown = ref('02:30:45')
const banners = ref([
  { image: '/assets/banner1.jpg', tag: 'NEW ARRIVAL', title: '新品上市', subtitle: '智能手表箱包配饰 全场低至5折', btnText: '立即选购' },
  { image: '/assets/banner2.jpg', tag: 'FLASH SALE', title: '限时秒杀', subtitle: '智能手表低至¥99 数量有限', btnText: '马上抢购' },
  { image: '/assets/banner3.jpg', tag: 'CROSS BORDER', title: '跨境精选', subtitle: '欧盟认证品质 全球直邮', btnText: '探索更多' }
])

const categories = ref([])
const flashProducts = ref([])
const newProducts = ref([])
const hotProducts = ref([])
const trendItems = ref([
  { product_id: 79, image: '/assets/banner1.jpg', tag: '智能穿戴', title: '运动时尚' },
  { product_id: 80, image: '/assets/banner2.jpg', tag: '箱包配饰', title: '通勤百搭' },
  { product_id: 81, image: '/assets/banner3.jpg', tag: '跨境精选', title: '品质生活' },
  { product_id: 82, image: '/assets/banner1.jpg', tag: '新品推荐', title: '潮流先锋' }
])
const brands = ref([
  { id: 1, name: 'TLLOS' },
  { id: 2, name: 'SmartWatch' },
  { id: 3, name: 'BagStyle' },
  { id: 4, name: 'CrossBorder' },
  { id: 5, name: 'EU Certified' },
  { id: 6, name: 'Quality First' }
])

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

const startCountdown = () => {
  let totalSeconds = 2 * 3600 + 30 * 60 + 45
  setInterval(() => {
    totalSeconds--
    if (totalSeconds <= 0) totalSeconds = 24 * 3600
    const h = Math.floor(totalSeconds / 3600)
    const m = Math.floor((totalSeconds % 3600) / 60)
    const s = totalSeconds % 60
    countdown.value = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  }, 1000)
}

const getProductImage = (product) => {
  const img = product.main_image || product.image || product.cover_image || (product.images && product.images[0]) || ''
  if (!img) return '/assets/placeholder.jpg?random=' + product.id
  if (img.startsWith('http')) return img
  return 'https://mall.tllos.com' + (img.startsWith('/') ? '' : '/') + img
}

// 分类默认emoji图标映射
const categoryEmojiMap = {
  1: '👜', 2: '👛', 19: '💼', 30: '🎒', 51: '🔑',
  60: '⌚', 61: '⌚', 74: '📿', 81: '🔧',
}

const getCategoryEmoji = (cat) => {
  return categoryEmojiMap[cat.id] || '📦'
}

const getCategoryIcon = (cat) => {
  const img = cat.icon || cat.image || ''
  if (!img) return ''
  if (img.startsWith('http')) return img
  return 'https://mall.tllos.com' + (img.startsWith('/') ? '' : '/') + img
}

const handleProductImgError = (event, product) => {
  event.target.src = '/assets/placeholder.jpg?random=' + product.id
}

const handleCategoryIconError = (event) => {
  event.target.src = '/assets/placeholder.jpg' + Math.random()
}

const handleTrendImgError = (event) => {
  event.target.src = '/assets/banner1.jpg'
}

const goNewIn = () => {
  router.push('/products?sort=new')
}

const goRegister = () => {
  router.push('/register')
}

const getDiscount = (product) => {
  if (!product.market_price || product.market_price <= product.price) return 0
  return Math.round((1 - product.price / product.market_price) * 100)
}

const isNewProduct = (product) => {
  if (!product.created_at) return false
  const created = new Date(product.created_at).getTime()
  return Date.now() - created < 7 * 24 * 60 * 60 * 1000
}

const loadData = async () => {
  try {
    // 加载分类
    try {
      const catRes = await getCategories()
      categories.value = catRes.data?.list || catRes.data || []
    } catch (e) {
      console.error('加载分类失败', e)
    }

    // 加载新品
    try {
      const newRes = await getNewProducts({ limit: 8 })
      const newList = newRes.data?.list || newRes.data?.data || newRes.data || []
      newProducts.value = newList.map(p => ({ ...p, favorite: false }))
    } catch (e) {
      console.error('加载新品失败', e)
    }

    // 加载热门商品（用于秒杀和热门区）
    try {
      const hotRes = await getHotProducts({ limit: 12 })
      const hotList = hotRes.data?.list || hotRes.data?.data || hotRes.data || []
      flashProducts.value = hotList.slice(0, 4)
      hotProducts.value = hotList.slice(4, 12)
    } catch (e) {
      console.error('加载热门商品失败', e)
      // 如果热门接口失败，用商品列表代替
      try {
        const listRes = await getProductList({ sort: 'sales', limit: 12 })
        const list = listRes.data?.list || listRes.data?.data || listRes.data || []
        flashProducts.value = list.slice(0, 4)
        hotProducts.value = list.slice(4, 12)
        if (!newProducts.value.length) {
          const newRes = await getProductList({ sort: 'new', limit: 8 })
          newProducts.value = (newRes.data?.list || newRes.data?.data || newRes.data || []).map(p => ({ ...p, favorite: false }))
        }
      } catch (e2) {
        console.error('加载商品列表失败', e2)
      }
    }
  } catch (e) {
    console.error('加载数据失败', e)
  }
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  startCountdown()
  loadData()
})

const goProductList = () => {
  router.push('/products')
}

const goProductDetail = (id) => {
  if (id) router.push('/product/' + id)
}

const goCategory = (id) => {
  router.push('/products?category_id=' + id)
}

const addToCart = (product) => {
  ElMessage.success(t('home.addedToCart'))
}

const toggleFavorite = (product) => {
  product.favorite = !product.favorite
  ElMessage.success(product.favorite ? t('home.favorited') : t('home.unfavorited'))
}
</script>

<style scoped>
.home-page {
  background: #f5f5f5;
  overflow-x: hidden;
  max-width: 100%;
  padding-bottom: 20px;
}

/* 顶部激励通栏 */
.promo-bar {
  background: linear-gradient(90deg, #ff6b00, #ff8c33);
  padding: 8px 20px;
  margin: -16px -20px 0;
  max-width: calc(100% + 40px);
}
.promo-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #fff;
  font-size: 13px;
}
.promo-text {
  font-weight: 500;
}
.promo-btn {
  background: #fff;
  color: #ff6b00;
  border: none;
  padding: 4px 16px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 12px;
}

/* Banner */
.banner-section {
  position: relative;
  margin: -16px -20px 16px;
  background: linear-gradient(135deg, #ff6b00, #ff8c33);
  overflow: hidden;
  max-width: calc(100% + 40px);
}
.main-banner { height: 420px; }
.banner-item {
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  position: relative;
}
.banner-overlay {
  position: absolute;
  left: 60px;
  top: 50%;
  transform: translateY(-50%);
  color: #fff;
  max-width: 400px;
}
.banner-tag {
  display: inline-block;
  background: rgba(255,255,255,.2);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  margin-bottom: 12px;
  backdrop-filter: blur(10px);
}
.banner-title {
  font-size: 36px;
  font-weight: 700;
  margin: 0 0 12px;
  text-shadow: 0 2px 4px rgba(0,0,0,.2);
}
.banner-subtitle {
  font-size: 16px;
  margin: 0 0 24px;
  opacity: .9;
}
.banner-btn {
  background: #fff;
  color: #ff6b00;
  border: none;
  padding: 12px 32px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 24px;
}
.banner-btn:hover {
  background: #fff5f0;
  color: #ff6b00;
}

/* 服务承诺 */
.service-bar {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: space-around;
  max-width: 100%;
  overflow: hidden;
}
.service-item {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  justify-content: center;
}
.service-text {
  display: flex;
  flex-direction: column;
}
.service-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}
.service-desc {
  font-size: 12px;
  color: #999;
}
.service-divider {
  width: 1px;
  height: 40px;
  background: #eee;
}

/* 通用区块 */
.category-section, .flash-sale-section, .new-arrivals-section, .hot-products-section, .brand-section {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
  max-width: 100%;
  overflow: hidden;
}
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.section-title {
  font-size: 20px;
  font-weight: 700;
  margin: 0;
  color: #1a1a1a;
}
.view-all {
  font-size: 14px;
  color: #ff6b00;
  text-decoration: none;
}

/* 分类 */
.category-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
}
.category-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all .2s;
}
.category-item:hover {
  background: #fafafa;
}
.category-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  background: #f5f5f5;
}
.category-icon img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.category-emoji {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  font-size: 28px;
  line-height: 1;
}
.category-name {
  font-size: 13px;
  color: #333;
  text-align: center;
}
.new-in-item {
  background: linear-gradient(135deg, #fff5f0, #ffe8d6);
  border-radius: 12px;
}
.new-in-icon {
  background: linear-gradient(135deg, #ff6b00, #ff8c33);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.new-in-name {
  color: #ff6b00;
  font-weight: 700;
  font-size: 12px;
}

/* 限时秒杀 */
.flash-header {
  background: linear-gradient(135deg, #ff4757, #ff6b00);
  margin: -20px -20px 16px;
  padding: 16px 20px;
  border-radius: 12px 12px 0 0;
  color: #fff;
}
.flash-header .section-title { color: #fff; }
.flash-header .view-all { color: #fff; }
.flash-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 700;
}
.flash-countdown {
  display: flex;
  align-items: center;
  gap: 8px;
}
.countdown-label { font-size: 13px; opacity: .9; }
.countdown-time {
  font-size: 16px;
  font-weight: 700;
  background: rgba(0,0,0,.2);
  padding: 4px 8px;
  border-radius: 4px;
}
.flash-products {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
.flash-product-card {
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  transition: all .2s;
}
.flash-product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,.1);
}
.flash-product-img {
  position: relative;
  width: 100%;
  padding-top: 100%;
  background: #f5f5f5;
  overflow: hidden;
}
.flash-product-img img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.flash-discount {
  position: absolute;
  top: 8px;
  left: 8px;
  background: #ff4757;
  color: #fff;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}
.flash-product-info { padding: 10px; }
.flash-product-name {
  font-size: 13px;
  color: #333;
  margin-bottom: 6px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 36px;
}
.flash-product-price {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 6px;
}
.current-price {
  font-size: 16px;
  color: #ff4757;
  font-weight: 700;
}
.original-price {
  font-size: 12px;
  color: #999;
  text-decoration: line-through;
}
.flash-progress {
  position: relative;
  height: 16px;
  background: #ffe4e1;
  border-radius: 8px;
  overflow: hidden;
}
.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #ff6b00, #ff4757);
  border-radius: 8px;
  transition: width .3s;
}
.sold-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 10px;
  color: #fff;
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0,0,0,.3);
}

/* 潮流趋势（TREND） */
.trend-section {
  margin-bottom: 24px;
}
.trend-header {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 16px;
}
.trend-subtitle {
  font-size: 13px;
  color: #999;
}
.trend-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
.trend-card {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  aspect-ratio: 3/4;
}
.trend-image {
  width: 100%;
  height: 100%;
  position: relative;
}
.trend-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.trend-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px 12px;
  background: linear-gradient(transparent, rgba(0,0,0,.7));
  color: #fff;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.trend-tag {
  font-size: 11px;
  background: rgba(255,107,0,.9);
  padding: 2px 8px;
  border-radius: 10px;
  display: inline-block;
  width: fit-content;
}
.trend-title {
  font-size: 14px;
  font-weight: 600;
}

/* 商品网格 */
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.product-card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all .25s;
  border: 1px solid #f0f0f0;
}
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.product-image {
  position: relative;
  width: 100%;
  padding-top: 100%;
  background: #f5f5f5;
  overflow: hidden;
}
.product-image img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .4s;
}
.product-card:hover .product-image img {
  transform: scale(1.05);
}
.product-badges {
  position: absolute;
  top: 8px;
  left: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.badge {
  padding: 2px 6px;
  font-size: 10px;
  border-radius: 4px;
  color: #fff;
  font-weight: 600;
}
.badge-hot { background: #ff4757; }
.badge-new { background: #07c160; }
.product-actions {
  position: absolute;
  bottom: 8px;
  right: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  opacity: 0;
  transition: opacity .2s;
}
.product-card:hover .product-actions {
  opacity: 1;
}
.action-btn {
  background: #fff;
  border: none;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.action-btn:hover {
  background: #ff6b00;
  color: #fff;
}
.favorite-btn.active {
  background: #ff4757;
  color: #fff;
}
.product-info {
  padding: 12px;
}
.product-name {
  font-size: 13px;
  color: #333;
  line-height: 1.4;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 36px;
}
.product-price-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 6px;
}
.product-price {
  font-size: 16px;
  color: #ff4757;
  font-weight: 700;
}
.product-original {
  font-size: 12px;
  color: #999;
  text-decoration: line-through;
}
.product-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.product-sales {
  font-size: 11px;
  color: #999;
}
.product-free {
  font-size: 10px;
  color: #07c160;
  background: #f0f9eb;
  padding: 1px 4px;
  border-radius: 2px;
}

/* 品牌 */
.brand-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 12px;
}
.brand-item {
  cursor: pointer;
}
.brand-logo {
  height: 60px;
  background: #f5f5f5;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  transition: all .2s;
}
.brand-logo:hover {
  background: #ff6b00;
  color: #fff;
}

/* APP推广 */
.app-promo {
  background: linear-gradient(135deg, #1a1a2e, #16213e);
  border-radius: 12px;
  padding: 40px;
  color: #fff;
  text-align: center;
  margin-bottom: 16px;
}
.app-promo h3 {
  font-size: 24px;
  margin: 0 0 8px;
}
.app-promo p {
  font-size: 14px;
  opacity: .8;
  margin: 0 0 20px;
}

/* 骨架屏 */
.skeleton-circle {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}
.skeleton-line {
  height: 12px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: 4px;
  width: 60px;
}
.skeleton-image {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}
@keyframes skeleton-loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* 移动端适配 */
@media (max-width: 768px) {
  .promo-bar {
    margin: -12px -12px 0;
    max-width: calc(100% + 24px);
    padding: 6px 12px;
  }
  .promo-content {
    font-size: 11px;
    gap: 8px;
  }
  .promo-btn {
    padding: 3px 10px;
    font-size: 11px;
  }

  .banner-section {
    margin: -12px -12px 12px;
    max-width: calc(100% + 24px);
  }
  .main-banner { height: 180px; }
  .banner-overlay {
    left: 20px;
    max-width: 250px;
  }
  .banner-title { font-size: 20px; }
  .banner-subtitle { font-size: 12px; margin-bottom: 12px; }
  .banner-btn { padding: 8px 20px; font-size: 13px; }

  .service-bar {
    padding: 12px;
    flex-wrap: wrap;
    gap: 8px;
  }
  .service-item { gap: 6px; }
  .service-title { font-size: 12px; }
  .service-desc { font-size: 10px; }
  .service-divider { display: none; }

  .category-section, .flash-sale-section, .new-arrivals-section, .hot-products-section, .brand-section, .trend-section {
    padding: 12px;
  }
  .section-title { font-size: 16px; }
  .category-grid {
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
  }
  .category-icon { width: 44px; height: 44px; }
  .category-name { font-size: 11px; }
  .new-in-name { font-size: 10px; }

  .trend-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }
  .trend-title { font-size: 12px; }
  .trend-tag { font-size: 10px; }

  .flash-header {
    margin: -12px -12px 12px;
    padding: 12px;
  }
  .flash-products {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }
  .flash-product-name { font-size: 12px; min-height: 32px; }
  .current-price { font-size: 14px; }

  .product-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .product-info { padding: 8px; }
  .product-name { font-size: 12px; min-height: 32px; }
  .product-price { font-size: 14px; }
  .product-actions { opacity: 1; }

  .brand-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
  }
  .brand-logo { height: 44px; font-size: 12px; }
}
</style>
