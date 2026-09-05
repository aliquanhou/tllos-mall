<template>
  <div class="home-page">
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
      <!-- 悬浮商品卡片 -->
      <div class="floating-products" v-if="!isMobile">
        <div class="float-product-card" v-for="(product, i) in floatingProducts" :key="i" @click="goProductDetail(product.id)">
          <div class="float-product-img" :style="{ backgroundImage: 'url(' + product.image + ')' }"></div>
          <div class="float-product-price">¥{{ product.price }}</div>
        </div>
      </div>
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

    <!-- 分类导航 -->
    <div class="category-section">
      <div class="section-header">
        <h3 class="section-title">{{ t('home.shopByCategory') }}</h3>
      </div>
      <div class="category-grid">
        <div class="category-item" v-for="cat in categories" :key="cat.id" @click="goCategory(cat.id)">
          <div class="category-icon" :style="{ backgroundImage: 'url(' + cat.icon + ')' }"></div>
          <span class="category-name">{{ cat.name }}</span>
        </div>
      </div>
    </div>

    <!-- 限时秒杀 -->
    <div class="flash-sale-section">
      <div class="section-header flash-header">
        <div class="flash-title">
          <el-icon :size="20"><Star /></el-icon>
          <span>{{ t('home.flashSale') }}</span>
        </div>
        <div class="flash-countdown">
          <span class="countdown-label">{{ t('home.endsIn') }}</span>
          <span class="countdown-time">{{ countdown }}</span>
        </div>
        <router-link to="/product/list?tag=flash_sale" class="view-all">{{ t('home.viewAll') }} ></router-link>
      </div>
      <div class="flash-products">
        <div class="flash-product-card" v-for="product in flashProducts" :key="product.id" @click="goProductDetail(product.id)">
          <div class="flash-product-img" :style="{ backgroundImage: 'url(' + product.image + ')' }">
            <div class="flash-discount">-{{ product.discount }}%</div>
          </div>
          <div class="flash-product-info">
            <div class="flash-product-name">{{ product.name }}</div>
            <div class="flash-product-price">
              <span class="current-price">¥{{ product.price }}</span>
              <span class="original-price">¥{{ product.originalPrice }}</span>
            </div>
            <div class="flash-progress">
              <div class="progress-bar" :style="{ width: product.soldPercent + '%' }"></div>
              <span class="sold-text">{{ t('home.sold') }} {{ product.soldPercent }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 新品推荐 -->
    <div class="new-arrivals-section">
      <div class="section-header">
        <h3 class="section-title">{{ t('home.newArrivals') }}</h3>
        <router-link to="/product/list?sort=new" class="view-all">{{ t('home.viewAll') }} ></router-link>
      </div>
      <div class="product-grid">
        <div class="product-card" v-for="product in newProducts" :key="product.id" @click="goProductDetail(product.id)">
          <div class="product-img" :style="{ backgroundImage: 'url(' + product.image + ')' }">
            <div class="product-tags" v-if="product.tags">
              <span class="product-tag" v-for="tag in product.tags" :key="tag">{{ tag }}</span>
            </div>
            <div class="product-actions">
              <el-button circle size="small" class="action-btn" @click.stop="addToCart(product)">
                <el-icon><ShoppingCart /></el-icon>
              </el-button>
              <el-button circle size="small" class="action-btn" @click.stop="toggleFavorite(product)">
                <el-icon :color="product.favorite ? '#ff6b00' : ''"><Star /></el-icon>
              </el-button>
            </div>
          </div>
          <div class="product-info">
            <div class="product-name">{{ product.name }}</div>
            <div class="product-price-row">
              <span class="product-price">¥{{ product.price }}</span>
              <span v-if="product.originalPrice" class="product-original">¥{{ product.originalPrice }}</span>
            </div>
            <div class="product-meta">
              <span v-if="product.sales" class="product-sales">{{ t('home.sold') }} {{ product.sales }}</span>
              <span v-if="product.freeShipping" class="free-shipping-tag">{{ t('home.freeShip') }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="load-more">
        <el-button type="primary" plain @click="loadMoreProducts">{{ t('home.loadMore') }}</el-button>
      </div>
    </div>

    <!-- 品牌专区 -->
    <div class="brand-section" v-if="!isMobile">
      <div class="section-header">
        <h3 class="section-title">{{ t('home.brandZone') }}</h3>
      </div>
      <div class="brand-grid">
        <div class="brand-card" v-for="brand in brands" :key="brand.id">
          <div class="brand-logo" :style="{ backgroundImage: 'url(' + brand.logo + ')' }"></div>
          <span class="brand-name">{{ brand.name }}</span>
        </div>
      </div>
    </div>

    <!-- 下载APP提示（移动端） -->
    <div class="app-promo" v-if="isMobile">
      <div class="app-promo-content">
        <div class="app-icon">
          <el-icon :size="32"><Iphone /></el-icon>
        </div>
        <div class="app-text">
          <span class="app-title">{{ t('home.downloadApp') }}</span>
          <span class="app-desc">{{ t('home.appExclusive') }}</span>
        </div>
        <el-button type="primary" size="small" class="app-btn">{{ t('home.download') }}</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import {
  Goods, RefreshLeft, Lock, Service, Star,
  ShoppingCart, Iphone
} from '@element-plus/icons-vue'

const router = useRouter()
const { t } = useI18n()

const isMobile = ref(false)
const countdown = ref('02:30:45')

const banners = ref([
  {
    image: 'https://picsum.photos/1200/500?random=1',
    tag: 'NEW SEASON',
    title: '秋冬新品上市',
    subtitle: '全场低至5折 限时特惠',
    btnText: '立即选购'
  },
  {
    image: 'https://picsum.photos/1200/500?random=2',
    tag: 'FLASH SALE',
    title: '限时秒杀',
    subtitle: '智能手表低至¥99 数量有限',
    btnText: '马上抢购'
  },
  {
    image: 'https://picsum.photos/1200/500?random=3',
    tag: 'CROSS BORDER',
    title: '跨境精选',
    subtitle: '欧盟认证品质 全球直邮',
    btnText: '探索更多'
  }
])

const floatingProducts = ref([
  { id: 1, image: 'https://picsum.photos/200/200?random=10', price: '271' },
  { id: 2, image: 'https://picsum.photos/200/200?random=11', price: '603' }
])

const categories = ref([
  { id: 1, name: '智能手表', icon: 'https://picsum.photos/100/100?random=20' },
  { id: 2, name: '箱包配饰', icon: 'https://picsum.photos/100/100?random=21' },
  { id: 3, name: '数码电子', icon: 'https://picsum.photos/100/100?random=22' },
  { id: 4, name: '家居生活', icon: 'https://picsum.photos/100/100?random=23' },
  { id: 5, name: '美妆个护', icon: 'https://picsum.photos/100/100?random=24' },
  { id: 6, name: '运动户外', icon: 'https://picsum.photos/100/100?random=25' },
  { id: 7, name: '母婴玩具', icon: 'https://picsum.photos/100/100?random=26' },
  { id: 8, name: '服装鞋包', icon: 'https://picsum.photos/100/100?random=27' },
  { id: 9, name: '食品保健', icon: 'https://picsum.photos/100/100?random=28' },
  { id: 10, name: '更多分类', icon: 'https://picsum.photos/100/100?random=29' }
])

const flashProducts = ref([
  { id: 101, name: '智能手表多功能运动版', image: 'https://picsum.photos/300/300?random=30', price: '99', originalPrice: '299', discount: 67, soldPercent: 78 },
  { id: 102, name: '时尚双肩包大容量', image: 'https://picsum.photos/300/300?random=31', price: '129', originalPrice: '399', discount: 68, soldPercent: 65 },
  { id: 103, name: '蓝牙耳机降噪版', image: 'https://picsum.photos/300/300?random=32', price: '199', originalPrice: '499', discount: 60, soldPercent: 89 },
  { id: 104, name: '智能手环心率监测', image: 'https://picsum.photos/300/300?random=33', price: '79', originalPrice: '199', discount: 60, soldPercent: 92 }
])

const newProducts = ref([
  { id: 201, name: '智能手表蓝牙通话版 心率监测 运动防水', image: 'https://picsum.photos/400/400?random=40', price: '299', originalPrice: '599', sales: '2.3k', freeShipping: true, tags: ['新品', '跨境'], favorite: false },
  { id: 202, name: '时尚女士手提包 真皮大容量 通勤百搭', image: 'https://picsum.photos/400/400?random=41', price: '399', originalPrice: '799', sales: '1.8k', freeShipping: true, tags: ['热销'], favorite: false },
  { id: 203, name: '男士双肩包 商务休闲 防水耐磨', image: 'https://picsum.photos/400/400?random=42', price: '199', originalPrice: '399', sales: '3.1k', freeShipping: true, tags: ['爆款'], favorite: false },
  { id: 204, name: '智能手表运动版 GPS定位 血氧监测', image: 'https://picsum.photos/400/400?random=43', price: '499', originalPrice: '899', sales: '956', freeShipping: true, tags: ['新品', '欧盟认证'], favorite: false },
  { id: 205, name: '钱包男士短款 真皮多卡位 简约时尚', image: 'https://picsum.photos/400/400?random=44', price: '129', originalPrice: '259', sales: '5.6k', freeShipping: true, tags: ['热销'], favorite: false },
  { id: 206, name: '行李箱24寸 万向轮 密码锁 大容量', image: 'https://picsum.photos/400/400?random=45', price: '349', originalPrice: '699', sales: '1.2k', freeShipping: true, tags: ['跨境'], favorite: false },
  { id: 207, name: '智能手表儿童版 定位通话 防水防摔', image: 'https://picsum.photos/400/400?random=46', price: '259', originalPrice: '459', sales: '876', freeShipping: true, tags: ['新品'], favorite: false },
  { id: 208, name: '帆布包女 单肩斜挎 文艺简约 大容量', image: 'https://picsum.photos/400/400?random=47', price: '89', originalPrice: '169', sales: '4.2k', freeShipping: true, tags: ['爆款'], favorite: false }
])

const brands = ref([
  { id: 1, name: '品牌A', logo: 'https://picsum.photos/160/80?random=50' },
  { id: 2, name: '品牌B', logo: 'https://picsum.photos/160/80?random=51' },
  { id: 3, name: '品牌C', logo: 'https://picsum.photos/160/80?random=52' },
  { id: 4, name: '品牌D', logo: 'https://picsum.photos/160/80?random=53' },
  { id: 5, name: '品牌E', logo: 'https://picsum.photos/160/80?random=54' },
  { id: 6, name: '品牌F', logo: 'https://picsum.photos/160/80?random=55' }
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

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  startCountdown()
})

const goProductList = () => {
  router.push('/product/list')
}

const goProductDetail = (id) => {
  router.push('/product/' + id)
}

const goCategory = (id) => {
  router.push('/product/list?category=' + id)
}

const addToCart = (product) => {
  ElMessage.success(t('home.addedToCart'))
}

const toggleFavorite = (product) => {
  product.favorite = !product.favorite
  ElMessage.success(product.favorite ? t('home.favorited') : t('home.unfavorited'))
}

const loadMoreProducts = () => {
  ElMessage.info(t('home.loadingMore'))
}
</script>

<style scoped>
/* 全局溢出保护 */
.home-page {
  background: #f5f5f5;
  overflow-x: hidden;
  max-width: 100%;
}

/* Banner区域 */
.banner-section {
  position: relative;
  margin: -16px -20px 16px;
  background: linear-gradient(135deg, #ff6b00, #ff8c33);
  overflow: hidden;
  max-width: calc(100% + 40px);
}
.mobile-view .banner-section {
  margin: -12px -12px 12px;
  max-width: calc(100% + 24px);
}
.main-banner {
  height: 420px;
}
.mobile-view .main-banner {
  height: 200px;
}
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
.mobile-view .banner-overlay {
  left: 20px;
  max-width: 200px;
}
.banner-tag {
  display: inline-block;
  background: rgba(255,255,255,.2);
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  margin-bottom: 12px;
  letter-spacing: 1px;
}
.banner-title {
  font-size: 42px;
  font-weight: 900;
  margin: 0 0 8px;
  text-shadow: 0 2px 8px rgba(0,0,0,.3);
}
.mobile-view .banner-title {
  font-size: 22px;
}
.banner-subtitle {
  font-size: 18px;
  margin: 0 0 20px;
  opacity: .9;
}
.mobile-view .banner-subtitle {
  font-size: 13px;
  margin-bottom: 12px;
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
.mobile-view .banner-btn {
  padding: 8px 20px;
  font-size: 13px;
}
.floating-products {
  position: absolute;
  right: 60px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  gap: 16px;
}
.float-product-card {
  width: 160px;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(0,0,0,.15);
  transition: transform .3s;
}
.float-product-card:hover {
  transform: translateY(-4px);
}
.float-product-img {
  width: 100%;
  padding-top: 100%;
  background-size: cover;
  background-position: center;
}
.float-product-price {
  text-align: center;
  padding: 8px;
  font-size: 20px;
  font-weight: bold;
  color: #ff6b00;
}

/* 服务承诺栏 */
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
.mobile-view .service-bar {
  padding: 12px;
  flex-wrap: wrap;
  gap: 8px;
}
.service-item {
  display: flex;
  align-items: center;
  gap: 12px;
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
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #333;
  margin: 0;
}
.mobile-view .section-title {
  font-size: 16px;
}
.view-all {
  color: #999;
  text-decoration: none;
  font-size: 14px;
}

/* 通用区块溢出保护 */
.category-section,
.flash-sale-section,
.new-arrivals-section,
.brand-section,
.app-promo {
  max-width: 100%;
  overflow: hidden;
}

/* 分类导航 */
.category-section {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}
.category-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
}
.mobile-view .category-grid {
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
}
.category-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.category-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background-size: cover;
  background-position: center;
  border: 2px solid #f5f5f5;
}
.mobile-view .category-icon {
  width: 52px;
  height: 52px;
}
.category-name {
  font-size: 13px;
  color: #333;
  text-align: center;
}
.mobile-view .category-name {
  font-size: 11px;
}

/* 限时秒杀 */
.flash-sale-section {
  background: linear-gradient(135deg, #fff5f0, #fff);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}
.flash-header {
  margin-bottom: 16px;
}
.flash-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 20px;
  font-weight: 700;
  color: #ff4757;
}
.mobile-view .flash-title {
  font-size: 16px;
}
.flash-countdown {
  display: flex;
  align-items: center;
  gap: 8px;
}
.countdown-label {
  font-size: 13px;
  color: #666;
}
.countdown-time {
  background: #333;
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 600;
  font-family: monospace;
}
.flash-products {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
.mobile-view .flash-products {
  grid-template-columns: repeat(2, 1fr);
}
.flash-product-card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: transform .3s;
}
.flash-product-card:hover {
  transform: translateY(-2px);
}
.flash-product-img {
  width: 100%;
  padding-top: 100%;
  background-size: cover;
  background-position: center;
  position: relative;
}
.flash-discount {
  position: absolute;
  top: 8px;
  left: 8px;
  background: #ff4757;
  color: #fff;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}
.flash-product-info {
  padding: 10px;
}
.flash-product-name {
  font-size: 13px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin-bottom: 6px;
  height: 36px;
}
.flash-product-price {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 8px;
}
.current-price {
  font-size: 18px;
  font-weight: bold;
  color: #ff4757;
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
}
.sold-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 10px;
  color: #fff;
  font-weight: 600;
}

/* 商品网格 */
.new-arrivals-section {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.mobile-view .product-grid {
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
.product-card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all .3s;
  border: 1px solid #f5f5f5;
}
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.product-img {
  width: 100%;
  padding-top: 100%;
  background-size: cover;
  background-position: center;
  position: relative;
}
.product-tags {
  position: absolute;
  top: 8px;
  left: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.product-tag {
  background: #ff6b00;
  color: #fff;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
}
.product-actions {
  position: absolute;
  bottom: 8px;
  right: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  opacity: 0;
  transition: opacity .3s;
}
.product-card:hover .product-actions {
  opacity: 1;
}
.action-btn {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.product-info {
  padding: 12px;
}
.product-name {
  font-size: 14px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin-bottom: 8px;
  line-height: 1.4;
  min-height: 40px;
}
.mobile-view .product-name {
  font-size: 12px;
  min-height: 34px;
}
.product-price-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 6px;
}
.product-price {
  font-size: 18px;
  font-weight: bold;
  color: #ff6b00;
}
.mobile-view .product-price {
  font-size: 16px;
}
.product-original {
  font-size: 12px;
  color: #999;
  text-decoration: line-through;
}
.product-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: #999;
}
.free-shipping-tag {
  background: #f0f9eb;
  color: #67c23a;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 10px;
}
.load-more {
  text-align: center;
  margin-top: 20px;
}

/* 品牌专区 */
.brand-section {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}
.brand-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
}
.brand-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  border: 1px solid #f5f5f5;
  border-radius: 8px;
  cursor: pointer;
  transition: all .3s;
}
.brand-card:hover {
  border-color: #ff6b00;
  box-shadow: 0 4px 12px rgba(255,107,0,.1);
}
.brand-logo {
  width: 100%;
  height: 50px;
  background-size: contain;
  background-position: center;
  background-repeat: no-repeat;
}
.brand-name {
  font-size: 13px;
  color: #333;
}

/* APP推广（移动端） */
.app-promo {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}
.app-promo-content {
  display: flex;
  align-items: center;
  gap: 12px;
}
.app-icon {
  width: 48px;
  height: 48px;
  background: rgba(255,255,255,.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.app-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  color: #fff;
}
.app-title {
  font-size: 14px;
  font-weight: 600;
}
.app-desc {
  font-size: 11px;
  opacity: .8;
}
.app-btn {
  background: #fff;
  color: #667eea;
  border: none;
  font-weight: 600;
}
</style>
