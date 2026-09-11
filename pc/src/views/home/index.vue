<template>
  <div class="tllos-home">
    <!-- ===== 顶部公告条 ===== -->
    <div class="tllos-notice-bar">
      <div class="tllos-container tllos-flex-center" style="gap:8px">
        <el-icon :size="14"><Promotion /></el-icon>
        <span>TLLOS 商城全新上线 · 注册立享新人礼包 · 全场满199免邮</span>
      </div>
    </div>

    <!-- ===== Hero 主视觉区 ===== -->
    <section class="tllos-hero">
      <div class="tllos-hero__bg">
        <div class="tllos-hero__gradient"></div>
        <div class="tllos-hero__grid"></div>
        <div class="tllos-hero__glow tllos-hero__glow--1"></div>
        <div class="tllos-hero__glow tllos-hero__glow--2"></div>
      </div>
      <div class="tllos-container tllos-hero__content">
        <div class="tllos-hero__text">
          <div class="tllos-hero__badge">
            <el-icon :size="14"><MagicStick /></el-icon>
            <span>TLLOS TECH · 2026 新品季</span>
          </div>
          <h1 class="tllos-hero__title">
            科技点亮生活<br />
            <span class="tllos-gradient-text">TLLOS 品质商城</span>
          </h1>
          <p class="tllos-hero__subtitle">
            智能穿戴 · 箱包配饰 · 跨境精选<br />
            全球品质好物，一站式购齐
          </p>
          <div class="tllos-hero__actions">
            <button class="tllos-btn tllos-btn--accent tllos-btn--lg" @click="goProductList">
              立即选购 <el-icon><ArrowRight /></el-icon>
            </button>
            <button class="tllos-btn tllos-btn--ghost tllos-btn--lg" @click="goNewIn">
              <el-icon><Sunny /></el-icon> 新品首发
            </button>
          </div>
          <div class="tllos-hero__stats">
            <div class="tllos-hero__stat">
              <div class="tllos-hero__stat-num">84+</div>
              <div class="tllos-hero__stat-label">精选商品</div>
            </div>
            <div class="tllos-hero__stat-divider"></div>
            <div class="tllos-hero__stat">
              <div class="tllos-hero__stat-num">94</div>
              <div class="tllos-hero__stat-label">商品分类</div>
            </div>
            <div class="tllos-hero__stat-divider"></div>
            <div class="tllos-hero__stat">
              <div class="tllos-hero__stat-num">7天</div>
              <div class="tllos-hero__stat-label">无理由退换</div>
            </div>
          </div>
        </div>
        <div class="tllos-hero__visual">
          <div class="tllos-hero__card tllos-hero__card--main">
            <div class="tllos-hero__card-img" v-if="hotProducts[0]">
              <img loading="lazy" :src="getProductImage(hotProducts[0])" :alt="hotProducts[0].name" />
            </div>
            <div class="tllos-hero__card-skeleton tllos-skeleton" v-else></div>
            <div class="tllos-hero__card-tag">热销 TOP1</div>
          </div>
          <div class="tllos-hero__card tllos-hero__card--sub tllos-hero__card--1">
            <div class="tllos-hero__card-img" v-if="hotProducts[1]">
              <img loading="lazy" :src="getProductImage(hotProducts[1])" :alt="hotProducts[1].name" />
            </div>
            <div class="tllos-hero__card-skeleton tllos-skeleton" v-else></div>
          </div>
          <div class="tllos-hero__card tllos-hero__card--sub tllos-hero__card--2">
            <div class="tllos-hero__card-img" v-if="newProducts[0]">
              <img loading="lazy" :src="getProductImage(newProducts[0])" :alt="newProducts[0].name" />
            </div>
            <div class="tllos-hero__card-skeleton tllos-skeleton" v-else></div>
            <div class="tllos-hero__card-tag tllos-hero__card-tag--new">NEW</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 服务承诺 ===== -->
    <section class="tllos-container" style="margin-top:-40px;position:relative;z-index:10">
      <div class="tllos-service-bar">
        <div class="tllos-service-item" v-for="(s, i) in services" :key="i">
          <div class="tllos-service-icon" :style="{ background: s.bg, color: s.color }">
            <el-icon :size="22"><component :is="s.icon" /></el-icon>
          </div>
          <div class="tllos-service-text">
            <div class="tllos-service-title">{{ s.title }}</div>
            <div class="tllos-service-desc">{{ s.desc }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 分类导航 ===== -->
    <section class="tllos-container tllos-section">
      <div class="tllos-section__header">
        <h2 class="tllos-section__title">精选分类</h2>
        <router-link to="/products" class="tllos-section__more">全部商品 <el-icon><ArrowRight /></el-icon></router-link>
      </div>
      <div class="tllos-category-grid" v-if="categories.length">
        <div class="tllos-category-item" @click="goNewIn">
          <div class="tllos-category-icon tllos-category-icon--special">
            <el-icon :size="26"><Sunny /></el-icon>
          </div>
          <span class="tllos-category-name">新品首发</span>
        </div>
        <div class="tllos-category-item" v-for="cat in categories.slice(0, 9)" :key="cat.id" @click="goCategory(cat.id)">
          <div class="tllos-category-icon">
            <span class="tllos-category-emoji">{{ getCategoryEmoji(cat) }}</span>
          </div>
          <span class="tllos-category-name">{{ cat.name }}</span>
        </div>
      </div>
      <div class="tllos-category-grid" v-else>
        <div class="tllos-category-item" v-for="i in 10" :key="i">
          <div class="tllos-category-icon tllos-skeleton"></div>
          <span class="tllos-category-name tllos-skeleton" style="width:40px;height:12px"></span>
        </div>
      </div>
    </section>

    <!-- ===== 限时秒杀 ===== -->
    <section class="tllos-container tllos-section" v-if="flashProducts.length">
      <div class="tllos-flash-header">
        <div class="tllos-flash-title">
          <el-icon :size="20"><Lightning /></el-icon>
          <span>限时秒杀</span>
          <span class="tllos-flash-sub">每日 10:00 开抢</span>
        </div>
        <div class="tllos-flash-countdown">
          <span class="tllos-countdown-label">距结束</span>
          <span class="tllos-countdown-box">{{ countdownH }}</span>
          <span class="tllos-countdown-sep">:</span>
          <span class="tllos-countdown-box">{{ countdownM }}</span>
          <span class="tllos-countdown-sep">:</span>
          <span class="tllos-countdown-box">{{ countdownS }}</span>
        </div>
      </div>
      <div class="tllos-flash-grid">
        <div class="tllos-flash-card" v-for="product in flashProducts" :key="product.id" @click="goProductDetail(product.id)">
          <div class="tllos-flash-img">
            <img loading="lazy" :src="getProductImage(product)" :alt="product.name" />
            <div class="tllos-flash-discount" v-if="getDiscount(product)">{{ getDiscount(product) }}%OFF</div>
          </div>
          <div class="tllos-flash-info">
            <div class="tllos-flash-name tllos-ellipsis-2">{{ product.name }}</div>
            <div class="tllos-flash-price">
              <span class="tllos-price tllos-price--large"><span class="tllos-price--symbol">¥</span>{{ Number(product.price).toFixed(0) }}</span>
              <span class="tllos-price--original" v-if="product.market_price > product.price">¥{{ Number(product.market_price).toFixed(0) }}</span>
            </div>
            <div class="tllos-flash-progress">
              <div class="tllos-flash-progress-bar" :style="{ width: Math.min((product.sales || 0) * 8, 92) + '%' }"></div>
              <span>已抢 {{ product.sales || 0 }}%</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 新品上架 ===== -->
    <section class="tllos-container tllos-section">
      <div class="tllos-section__header">
        <h2 class="tllos-section__title">新品上架</h2>
        <router-link to="/products?sort=new" class="tllos-section__more">查看更多 <el-icon><ArrowRight /></el-icon></router-link>
      </div>
      <div class="tllos-product-grid" v-if="newProducts.length">
        <div class="tllos-product-card" v-for="product in newProducts.slice(0, 8)" :key="product.id" @click="goProductDetail(product.id)">
          <div class="tllos-product-img">
            <img loading="lazy" :src="getProductImage(product)" :alt="product.name" />
            <div class="tllos-product-badges">
              <span class="tllos-tag tllos-tag--dark" v-if="isNewProduct(product)">NEW</span>
              <span class="tllos-tag tllos-tag--orange" v-if="product.sales > 100">热销</span>
            </div>
            <div class="tllos-product-actions">
              <button class="tllos-product-action-btn" @click.stop="addToCart(product)">
                <el-icon><ShoppingCart /></el-icon>
              </button>
              <button class="tllos-product-action-btn" :class="{ active: product.favorite }" @click.stop="toggleFavorite(product)">
                <el-icon><Star v-if="!product.favorite" /><StarFilled v-else /></el-icon>
              </button>
            </div>
          </div>
          <div class="tllos-product-info">
            <div class="tllos-product-name tllos-ellipsis-2">{{ product.name }}</div>
            <div class="tllos-product-price-row">
              <span class="tllos-price"><span class="tllos-price--symbol">¥</span>{{ Number(product.price).toFixed(2) }}</span>
              <span class="tllos-price--original" v-if="product.market_price > product.price">¥{{ Number(product.market_price).toFixed(2) }}</span>
            </div>
            <div class="tllos-product-meta">
              <span>已售 {{ product.sales || 0 }}</span>
              <span class="tllos-tag tllos-tag--success" v-if="product.free_shipping !== false">包邮</span>
            </div>
          </div>
        </div>
      </div>
      <div class="tllos-product-grid" v-else>
        <div class="tllos-product-card" v-for="i in 8" :key="i">
          <div class="tllos-product-img tllos-skeleton"></div>
          <div class="tllos-product-info">
            <div class="tllos-skeleton" style="width:90%;height:14px;margin-bottom:8px"></div>
            <div class="tllos-skeleton" style="width:50%;height:18px"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 品牌故事 ===== -->
    <section class="tllos-brand-story">
      <div class="tllos-container">
        <div class="tllos-brand-story__content">
          <div class="tllos-brand-story__text">
            <div class="tllos-brand-story__badge">ABOUT TLLOS</div>
            <h2 class="tllos-brand-story__title">
              由代码驱动的<br />品质生活方式
            </h2>
            <p class="tllos-brand-story__desc">
              TLLOS 诞生于对技术与品质的极致追求。我们甄选全球智能穿戴、箱包配饰与生活好物，
              以科技标准把控每一件商品的品质，让创新科技融入日常生活。
            </p>
            <div class="tllos-brand-story__features">
              <div class="tllos-brand-story__feature">
                <el-icon :size="20"><CircleCheck /></el-icon>
                <span>品质严选</span>
              </div>
              <div class="tllos-brand-story__feature">
                <el-icon :size="20"><CircleCheck /></el-icon>
                <span>全球直采</span>
              </div>
              <div class="tllos-brand-story__feature">
                <el-icon :size="20"><CircleCheck /></el-icon>
                <span>售后无忧</span>
              </div>
            </div>
          </div>
          <div class="tllos-brand-story__visual">
            <div class="tllos-brand-story__card tllos-brand-story__card--1">
              <div class="tllos-brand-story__card-icon"><el-icon :size="32"><Cpu /></el-icon></div>
              <div class="tllos-brand-story__card-title">智能穿戴</div>
              <div class="tllos-brand-story__card-desc">心率监测 · 运动追踪</div>
            </div>
            <div class="tllos-brand-story__card tllos-brand-story__card--2">
              <div class="tllos-brand-story__card-icon"><el-icon :size="32"><ShoppingBag /></el-icon></div>
              <div class="tllos-brand-story__card-title">箱包配饰</div>
              <div class="tllos-brand-story__card-desc">时尚设计 · 品质工艺</div>
            </div>
            <div class="tllos-brand-story__card tllos-brand-story__card--3">
              <div class="tllos-brand-story__card-icon"><el-icon :size="32"><Location /></el-icon></div>
              <div class="tllos-brand-story__card-title">跨境精选</div>
              <div class="tllos-brand-story__card-desc">全球好物 · 直邮到家</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 热门商品 ===== -->
    <section class="tllos-container tllos-section" v-if="hotProducts.length">
      <div class="tllos-section__header">
        <h2 class="tllos-section__title">热门推荐</h2>
        <router-link to="/products?sort=sales" class="tllos-section__more">查看更多 <el-icon><ArrowRight /></el-icon></router-link>
      </div>
      <div class="tllos-product-grid">
        <div class="tllos-product-card" v-for="product in hotProducts.slice(0, 8)" :key="product.id" @click="goProductDetail(product.id)">
          <div class="tllos-product-img">
            <img loading="lazy" :src="getProductImage(product)" :alt="product.name" />
            <div class="tllos-product-badges">
              <span class="tllos-tag tllos-tag--danger">HOT</span>
            </div>
          </div>
          <div class="tllos-product-info">
            <div class="tllos-product-name tllos-ellipsis-2">{{ product.name }}</div>
            <div class="tllos-product-price-row">
              <span class="tllos-price"><span class="tllos-price--symbol">¥</span>{{ Number(product.price).toFixed(2) }}</span>
            </div>
            <div class="tllos-product-meta">
              <span>已售 {{ product.sales || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 页脚 ===== -->
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  Promotion, MagicStick, ArrowRight, Sunny, Lightning,
  ShoppingCart, Star, StarFilled, CircleCheck, Cpu, ShoppingBag,
  Location, Phone, Message, Goods, RefreshLeft, Lock, Service
} from '@element-plus/icons-vue'
import { getProductList, getCategories, getHotProducts, getNewProducts } from '@/api/product'

const router = useRouter()

// ===== 数据 =====
const categories = ref([])
const flashProducts = ref([])
const newProducts = ref([])
const hotProducts = ref([])

// ===== 秒杀倒计时 =====
const countdownTotal = ref(2 * 3600 + 30 * 60 + 45)
const countdownH = computed(() => String(Math.floor(countdownTotal.value / 3600)).padStart(2, '0'))
const countdownM = computed(() => String(Math.floor((countdownTotal.value % 3600) / 60)).padStart(2, '0'))
const countdownS = computed(() => String(countdownTotal.value % 60).padStart(2, '0'))

// ===== 服务承诺 =====
const services = [
  { icon: Goods, title: '全场包邮', desc: '满199元免邮费', bg: '#ecfeff', color: '#0891b2' },
  { icon: RefreshLeft, title: '7天无理由', desc: '退换无忧', bg: '#f0fdf4', color: '#059669' },
  { icon: Lock, title: '安全支付', desc: '多重加密保障', bg: '#eff6ff', color: '#2563eb' },
  { icon: Service, title: '专属客服', desc: '7x24小时在线', bg: '#fff7ed', color: '#ea580c' },
]

// ===== 分类 emoji =====
const categoryEmojiMap = { 1: '👜', 2: '👛', 19: '💼', 30: '🎒', 51: '🔑', 60: '⌚', 61: '⌚', 74: '📿', 81: '🔧' }
const getCategoryEmoji = (cat) => categoryEmojiMap[cat.id] || '📦'

// ===== 工具方法 =====
const getProductImage = (product) => {
  const img = product.main_image || product.image || product.cover_image || (product.images && product.images[0]) || ''
  if (!img) return 'https://mall.tllos.com/assets/placeholder.jpg'
  if (img.startsWith('http')) return img
  return 'https://mall.tllos.com' + (img.startsWith('/') ? '' : '/') + img
}

const getDiscount = (product) => {
  if (!product.market_price || product.market_price <= product.price) return 0
  return Math.round((1 - product.price / product.market_price) * 100)
}

const isNewProduct = (product) => {
  if (!product.created_at) return product.is_new === 1
  return Date.now() - new Date(product.created_at).getTime() < 7 * 86400000
}

// ===== 导航 =====
const goProductList = () => router.push('/products')
const goNewIn = () => router.push('/products?sort=new')
const goProductDetail = (id) => { if (id) router.push('/product/' + id) }
const goCategory = (id) => router.push('/products?category_id=' + id)

// ===== 交互 =====
const addToCart = (product) => ElMessage.success('已加入购物车')
const toggleFavorite = (product) => {
  product.favorite = !product.favorite
  ElMessage.success(product.favorite ? '已收藏' : '已取消收藏')
}

// ===== 数据加载 =====
const loadData = async () => {
  try {
    const [catRes, newRes, hotRes] = await Promise.all([
      getCategories().catch(() => null),
      getNewProducts({ limit: 8 }).catch(() => null),
      getHotProducts({ limit: 12 }).catch(() => null),
    ])
    if (catRes) categories.value = catRes.data?.list || catRes.data || []
    if (newRes) {
      const list = newRes.data?.list || newRes.data?.data || newRes.data || []
      newProducts.value = list.map(p => ({ ...p, favorite: false }))
    }
    if (hotRes) {
      const list = hotRes.data?.list || hotRes.data?.data || hotRes.data || []
      flashProducts.value = list.slice(0, 4)
      hotProducts.value = list.slice(4, 12)
    }
    // 兜底：如果热门为空，用商品列表
    if (!hotProducts.value.length && !newProducts.value.length) {
      const listRes = await getProductList({ sort: 'sales', limit: 12 }).catch(() => null)
      if (listRes) {
        const list = listRes.data?.list || listRes.data?.data || []
        flashProducts.value = list.slice(0, 4)
        hotProducts.value = list.slice(4, 8)
        newProducts.value = list.slice(0, 8).map(p => ({ ...p, favorite: false }))
      }
    }
  } catch (e) {
    console.error('加载首页数据失败', e)
  }
}

onMounted(() => {
  loadData()
  const timer = setInterval(() => {
    countdownTotal.value--
    if (countdownTotal.value <= 0) countdownTotal.value = 24 * 3600
  }, 1000)
})
</script>

<style scoped>
/* ===== 顶部公告条 ===== */
.tllos-notice-bar {
  background: linear-gradient(90deg, var(--tllos-primary-dark), var(--tllos-primary));
  color: rgba(255,255,255,0.9);
  font-size: 12px;
  padding: 6px 0;
  letter-spacing: 0.02em;
}

/* ===== Hero ===== */
.tllos-hero {
  position: relative;
  overflow: hidden;
  padding: 60px 0 100px;
  background: var(--tllos-bg-dark);
}
.tllos-hero__bg { position: absolute; inset: 0; }
.tllos-hero__gradient {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 80% 60% at 20% 30%, rgba(6,182,212,0.15), transparent),
    radial-gradient(ellipse 60% 50% at 80% 70%, rgba(30,58,95,0.4), transparent);
}
.tllos-hero__grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(6,182,212,0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(6,182,212,0.03) 1px, transparent 1px);
  background-size: 40px 40px;
}
.tllos-hero__glow {
  position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.3;
}
.tllos-hero__glow--1 { width: 400px; height: 400px; background: var(--tllos-accent); top: -100px; right: 10%; }
.tllos-hero__glow--2 { width: 300px; height: 300px; background: var(--tllos-orange); bottom: -50px; left: 20%; opacity: 0.15; }

.tllos-hero__content {
  position: relative; z-index: 1;
  display: flex; align-items: center; gap: 60px;
}
.tllos-hero__text { flex: 1; color: #fff; }
.tllos-hero__badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3);
  color: var(--tllos-accent-light); padding: 6px 14px; border-radius: 999px;
  font-size: 12px; font-weight: 600; margin-bottom: 24px;
}
.tllos-hero__title {
  font-size: 48px; font-weight: 800; line-height: 1.2; margin: 0 0 20px;
  letter-spacing: -0.02em;
}
.tllos-hero__subtitle {
  font-size: 17px; color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: 36px;
}
.tllos-hero__actions { display: flex; gap: 16px; margin-bottom: 48px; }
.tllos-hero__stats { display: flex; align-items: center; gap: 32px; }
.tllos-hero__stat-num { font-size: 28px; font-weight: 800; color: var(--tllos-accent-light); font-family: var(--tllos-font-mono); }
.tllos-hero__stat-label { font-size: 13px; color: rgba(255,255,255,0.5); margin-top: 2px; }
.tllos-hero__stat-divider { width: 1px; height: 36px; background: rgba(255,255,255,0.1); }

.tllos-hero__visual { flex: 0 0 420px; position: relative; height: 440px; }
.tllos-hero__card {
  position: absolute; border-radius: var(--tllos-radius-xl); overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
}
.tllos-hero__card--main { width: 280px; height: 340px; top: 20px; right: 40px; z-index: 2; }
.tllos-hero__card--sub { width: 160px; height: 180px; z-index: 1; }
.tllos-hero__card--1 { top: 0; left: 0; transform: rotate(-6deg); }
.tllos-hero__card--2 { bottom: 0; left: 60px; transform: rotate(4deg); }
.tllos-hero__card-img { width: 100%; height: 100%; background: #1e293b; }
.tllos-hero__card-img img { width: 100%; height: 100%; object-fit: cover; }
.tllos-hero__card-skeleton { width: 100%; height: 100%; }
.tllos-hero__card-tag {
  position: absolute; top: 12px; left: 12px;
  background: var(--tllos-orange); color: #fff;
  padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
}
.tllos-hero__card-tag--new { background: var(--tllos-accent); }

/* ===== 服务承诺 ===== */
.tllos-service-bar {
  background: var(--tllos-bg-card);
  border-radius: var(--tllos-radius-xl);
  box-shadow: var(--tllos-shadow-lg);
  padding: 24px 32px;
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
  border: 1px solid var(--tllos-border-1);
}
.tllos-service-item { display: flex; align-items: center; gap: 14px; }
.tllos-service-icon {
  width: 48px; height: 48px; border-radius: var(--tllos-radius-lg);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.tllos-service-title { font-size: 15px; font-weight: 700; color: var(--tllos-text-1); }
.tllos-service-desc { font-size: 12px; color: var(--tllos-text-4); margin-top: 2px; }

/* ===== 分类 ===== */
.tllos-category-grid {
  display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px;
  background: var(--tllos-bg-card);
  border-radius: var(--tllos-radius-xl);
  padding: 28px 24px;
  border: 1px solid var(--tllos-border-1);
  box-shadow: var(--tllos-shadow-sm);
}
.tllos-category-item {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  cursor: pointer; padding: 12px 8px; border-radius: var(--tllos-radius-lg);
  transition: all var(--tllos-duration-base) var(--tllos-ease);
}
.tllos-category-item:hover { background: var(--tllos-bg-hover); transform: translateY(-2px); }
.tllos-category-icon {
  width: 56px; height: 56px; border-radius: 50%;
  background: linear-gradient(135deg, var(--tllos-primary-bg), #e0f2fe);
  display: flex; align-items: center; justify-content: center;
  transition: all var(--tllos-duration-base);
}
.tllos-category-item:hover .tllos-category-icon {
  box-shadow: 0 4px 16px rgba(6,182,212,0.2);
  transform: scale(1.08);
}
.tllos-category-icon--special {
  background: linear-gradient(135deg, var(--tllos-accent), var(--tllos-primary));
  color: #fff;
}
.tllos-category-emoji { font-size: 26px; line-height: 1; }
.tllos-category-name { font-size: 13px; color: var(--tllos-text-2); font-weight: 500; text-align: center; }

/* ===== 限时秒杀 ===== */
.tllos-flash-header {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  border-radius: var(--tllos-radius-xl) var(--tllos-radius-xl) 0 0;
  padding: 20px 28px; color: #fff;
}
.tllos-flash-title { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 800; }
.tllos-flash-title .el-icon { color: var(--tllos-orange-light); }
.tllos-flash-sub { font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 400; margin-left: 8px; }
.tllos-flash-countdown { display: flex; align-items: center; gap: 6px; }
.tllos-countdown-label { font-size: 13px; color: rgba(255,255,255,0.6); margin-right: 4px; }
.tllos-countdown-box {
  background: rgba(255,255,255,0.15); color: #fff;
  padding: 4px 8px; border-radius: 6px; font-size: 15px; font-weight: 700;
  font-family: var(--tllos-font-mono); min-width: 30px; text-align: center;
}
.tllos-countdown-sep { color: rgba(255,255,255,0.5); font-weight: 700; }

.tllos-flash-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
  background: var(--tllos-bg-card);
  border-radius: 0 0 var(--tllos-radius-xl) var(--tllos-radius-xl);
  padding: 24px;
  border: 1px solid var(--tllos-border-1);
  border-top: none;
  box-shadow: var(--tllos-shadow-sm);
}
.tllos-flash-card {
  cursor: pointer; border-radius: var(--tllos-radius-lg); overflow: hidden;
  border: 1px solid var(--tllos-border-1);
  transition: all var(--tllos-duration-base) var(--tllos-ease);
}
.tllos-flash-card:hover { transform: translateY(-4px); box-shadow: var(--tllos-shadow-lg); border-color: var(--tllos-orange); }
.tllos-flash-img { position: relative; width: 100%; padding-top: 100%; background: var(--tllos-bg-page); }
.tllos-flash-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.tllos-flash-discount {
  position: absolute; top: 10px; left: 10px;
  background: linear-gradient(135deg, var(--tllos-danger), #f87171);
  color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;
}
.tllos-flash-info { padding: 14px; }
.tllos-flash-name { font-size: 13px; color: var(--tllos-text-2); margin-bottom: 8px; min-height: 36px; line-height: 1.5; }
.tllos-flash-price { display: flex; align-items: baseline; gap: 6px; margin-bottom: 10px; }
.tllos-flash-progress { position: relative; height: 18px; background: #fff1f0; border-radius: 999px; overflow: hidden; }
.tllos-flash-progress-bar { height: 100%; background: linear-gradient(90deg, var(--tllos-orange), var(--tllos-danger)); border-radius: 999px; transition: width 0.3s; }
.tllos-flash-progress span {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #fff; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

/* ===== 商品卡片 ===== */
.tllos-product-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
}
.tllos-product-card {
  background: var(--tllos-bg-card); border-radius: var(--tllos-radius-lg);
  overflow: hidden; cursor: pointer; border: 1px solid var(--tllos-border-1);
  transition: all var(--tllos-duration-base) var(--tllos-ease);
}
.tllos-product-card:hover { transform: translateY(-6px); box-shadow: var(--tllos-shadow-xl); border-color: var(--tllos-border-2); }
.tllos-product-img { position: relative; width: 100%; padding-top: 100%; background: var(--tllos-bg-page); overflow: hidden; }
.tllos-product-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform var(--tllos-duration-slow); }
.tllos-product-card:hover .tllos-product-img img { transform: scale(1.06); }
.tllos-product-badges { position: absolute; top: 10px; left: 10px; display: flex; flex-direction: column; gap: 4px; }
.tllos-product-actions {
  position: absolute; bottom: 10px; right: 10px;
  display: flex; flex-direction: column; gap: 8px;
  opacity: 0; transform: translateX(10px);
  transition: all var(--tllos-duration-base);
}
.tllos-product-card:hover .tllos-product-actions { opacity: 1; transform: translateX(0); }
.tllos-product-action-btn {
  width: 36px; height: 36px; border-radius: 50%; background: #fff;
  display: flex; align-items: center; justify-content: center;
  box-shadow: var(--tllos-shadow-md); color: var(--tllos-text-2);
  transition: all var(--tllos-duration-fast);
}
.tllos-product-action-btn:hover { background: var(--tllos-accent); color: #fff; }
.tllos-product-action-btn.active { background: var(--tllos-danger); color: #fff; }
.tllos-product-info { padding: 16px; }
.tllos-product-name { font-size: 14px; color: var(--tllos-text-2); line-height: 1.5; margin-bottom: 10px; min-height: 42px; }
.tllos-product-price-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 8px; }
.tllos-product-meta { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--tllos-text-4); }

/* ===== 品牌故事 ===== */
.tllos-brand-story {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
  padding: 80px 0; margin: 40px 0;
  position: relative; overflow: hidden;
}
.tllos-brand-story::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(ellipse 50% 80% at 80% 50%, rgba(6,182,212,0.1), transparent);
}
.tllos-brand-story__content { position: relative; z-index: 1; display: flex; align-items: center; gap: 60px; }
.tllos-brand-story__text { flex: 1; color: #fff; }
.tllos-brand-story__badge {
  display: inline-block; color: var(--tllos-accent-light); font-size: 13px; font-weight: 700;
  letter-spacing: 0.15em; margin-bottom: 20px;
}
.tllos-brand-story__title { font-size: 38px; font-weight: 800; line-height: 1.3; margin: 0 0 24px; letter-spacing: -0.01em; }
.tllos-brand-story__desc { font-size: 15px; color: rgba(255,255,255,0.65); line-height: 1.9; margin-bottom: 32px; }
.tllos-brand-story__features { display: flex; gap: 28px; }
.tllos-brand-story__feature {
  display: flex; align-items: center; gap: 8px;
  color: rgba(255,255,255,0.85); font-size: 14px; font-weight: 500;
}
.tllos-brand-story__feature .el-icon { color: var(--tllos-accent); }

.tllos-brand-story__visual { flex: 0 0 380px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.tllos-brand-story__card {
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--tllos-radius-xl); padding: 28px 20px; text-align: center;
  backdrop-filter: blur(10px); transition: all var(--tllos-duration-base);
}
.tllos-brand-story__card:hover { background: rgba(6,182,212,0.1); border-color: rgba(6,182,212,0.3); transform: translateY(-4px); }
.tllos-brand-story__card--1 { grid-column: 1 / -1; }
.tllos-brand-story__card-icon {
  width: 56px; height: 56px; margin: 0 auto 14px; border-radius: var(--tllos-radius-lg);
  background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(30,58,95,0.4));
  display: flex; align-items: center; justify-content: center; color: var(--tllos-accent-light);
}
.tllos-brand-story__card-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px; }
.tllos-brand-story__card-desc { font-size: 12px; color: rgba(255,255,255,0.5); }

/* ===== 页脚 ===== */

/* ===== 响应式 ===== */
@media (max-width: 1024px) {
  .tllos-hero__content { flex-direction: column; text-align: center; }
  .tllos-hero__actions, .tllos-hero__stats { justify-content: center; }
  .tllos-hero__visual { display: none; }
  .tllos-product-grid { grid-template-columns: repeat(3, 1fr); }
  .tllos-brand-story__content { flex-direction: column; }
  .tllos-footer__main { flex-direction: column; }
}
@media (max-width: 768px) {
  .tllos-hero { padding: 40px 0 80px; }
  .tllos-hero__title { font-size: 32px; }
  .tllos-hero__subtitle { font-size: 15px; }
  .tllos-service-bar { grid-template-columns: repeat(2, 1fr); padding: 20px; }
  .tllos-category-grid { grid-template-columns: repeat(5, 1fr); gap: 8px; padding: 16px; }
  .tllos-category-icon { width: 44px; height: 44px; }
  .tllos-category-emoji { font-size: 20px; }
  .tllos-category-name { font-size: 11px; }
  .tllos-flash-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 16px; }
  .tllos-product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .tllos-product-info { padding: 12px; }
  .tllos-product-name { font-size: 12px; min-height: 36px; }
  .tllos-brand-story { padding: 50px 0; }
  .tllos-brand-story__title { font-size: 28px; }
  .tllos-brand-story__visual { flex: 1; width: 100%; }
  .tllos-footer__links { grid-template-columns: repeat(2, 1fr); }
  .tllos-section__title { font-size: 18px; }
}
</style>
