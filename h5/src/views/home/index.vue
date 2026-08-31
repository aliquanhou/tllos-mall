<template>
  <div class="home">
    <!-- 搜索栏 -->
    <div class="search-bar" @click="goSearch">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <span>{{ t('home.searchPlaceholder') }}</span>
    </div>

    <!-- 轮播图 -->
    <div class="banner">
      <div class="banner-slide" :style="{ background: banners[currentBanner].bg }">
        <div class="banner-text">
          <h2>{{ banners[currentBanner].title }}</h2>
          <p>{{ banners[currentBanner].subtitle }}</p>
        </div>
      </div>
      <div class="banner-dots">
        <span v-for="(b, i) in banners" :key="i" :class="{ active: i === currentBanner }" @click="currentBanner = i"></span>
      </div>
    </div>

    <!-- 分类导航 -->
    <div class="card category-nav">
      <div v-for="cat in categories" :key="cat.id" class="cat-item" @click="goCategory(cat.id)">
        <div class="cat-icon" :style="{ background: cat.color }">{{ cat.icon }}</div>
        <span>{{ cat.name }}</span>
      </div>
    </div>

    <!-- 限时秒杀 -->
    <div class="card section">
      <div class="section-header">
        <h3>⚡ {{ t('home.flashSale') }}</h3>
        <span class="more" @click="goProducts('flash')">{{ t('home.viewAll') }} ></span>
      </div>
      <div class="product-scroll">
        <div v-for="p in flashProducts" :key="p.id" class="product-mini" @click="goProduct(p.id)">
          <div class="product-img" :style="{ background: p.color }"></div>
          <div class="product-name ellipsis">{{ p.name }}</div>
          <div class="product-price">¥{{ p.price }}</div>
        </div>
      </div>
    </div>

    <!-- 新品上架 -->
    <div class="card section">
      <div class="section-header">
        <h3>🆕 {{ t('home.newProducts') }}</h3>
        <span class="more" @click="goProducts('new')">{{ t('home.viewAll') }} ></span>
      </div>
      <div class="product-grid">
        <div v-for="p in newProducts" :key="p.id" class="product-card" @click="goProduct(p.id)">
          <div class="product-img-lg" :style="{ background: p.color }"></div>
          <div class="product-info">
            <div class="product-name ellipsis-2">{{ p.name }}</div>
            <div class="product-bottom">
              <span class="price">{{ p.price }}</span>
              <span class="sales">{{ p.sales }}人付款</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const router = useRouter()
const currentBanner = ref(0)

const banners = [
  { title: 'TLLOS Mall', subtitle: '全新架构 · 独立知识产权', bg: 'linear-gradient(135deg, #667eea, #764ba2)' },
  { title: '新品首发', subtitle: '限时优惠 低至5折', bg: 'linear-gradient(135deg, #f093fb, #f5576c)' },
  { title: '多端覆盖', subtitle: 'H5 · 小程序 · Flutter APK', bg: 'linear-gradient(135deg, #4facfe, #00f2fe)' }
]

const categories = [
  { id: 1, name: '数码电器', icon: '📱', color: '#e3f2fd' },
  { id: 2, name: '服饰鞋包', icon: '👕', color: '#fce4ec' },
  { id: 3, name: '美妆护肤', icon: '💄', color: '#f3e5f5' },
  { id: 4, name: '食品生鲜', icon: '🍎', color: '#e8f5e9' },
  { id: 5, name: '家居家装', icon: '🏠', color: '#fff3e0' },
  { id: 6, name: '运动户外', icon: '⚽', color: '#e0f7fa' },
  { id: 7, name: '母婴玩具', icon: '🧸', color: '#f9fbe7' },
  { id: 8, name: '更多分类', icon: '📦', color: '#eceff1' }
]

const flashProducts = [
  { id: 101, name: '无线蓝牙耳机 Pro', price: '199.00', color: '#e3f2fd' },
  { id: 102, name: '智能手表 S2', price: '599.00', color: '#fce4ec' },
  { id: 103, name: '便携充电宝 20000mAh', price: '89.00', color: '#f3e5f5' },
  { id: 104, name: '机械键盘 87键', price: '259.00', color: '#e8f5e9' }
]

const newProducts = [
  { id: 201, name: 'TLLOS 全新架构商城系统 多端适配', price: '0.01', sales: 1286, color: '#e3f2fd' },
  { id: 202, name: 'Laravel 11 + Vue3 高性能电商平台', price: '999.00', sales: 356, color: '#fce4ec' },
  { id: 203, name: '多商户入驻 SaaS 商城解决方案', price: '2999.00', sales: 89, color: '#f3e5f5' },
  { id: 204, name: '小程序+H5+APP 三端合一', price: '1999.00', sales: 234, color: '#e8f5e9' },
  { id: 205, name: '分销裂变营销系统', price: '599.00', sales: 445, color: '#fff3e0' },
  { id: 206, name: '秒杀拼团优惠券营销工具包', price: '399.00', sales: 678, color: '#e0f7fa' }
]

const goProduct = id => router.push(`/product/${id}`)
const goCategory = id => router.push('/category')
const goProducts = type => router.push('/products')
const goSearch = () => router.push('/products')
</script>

<style scoped>
.home { padding-bottom: 10px; }
.search-bar { display: flex; align-items: center; gap: 8px; background: #fff; margin: 10px; padding: 10px 16px; border-radius: 20px; color: var(--text-secondary); font-size: 13px; }
.search-bar svg { width: 16px; height: 16px; }
.banner { margin: 0 10px 10px; border-radius: 12px; overflow: hidden; position: relative; height: 140px; }
.banner-slide { width: 100%; height: 100%; display: flex; align-items: center; padding: 0 24px; color: #fff; }
.banner-text h2 { font-size: 22px; margin-bottom: 6px; }
.banner-text p { font-size: 13px; opacity: 0.9; }
.banner-dots { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; }
.banner-dots span { width: 6px; height: 6px; border-radius: 3px; background: rgba(255,255,255,0.5); transition: all 0.3s; }
.banner-dots span.active { width: 18px; background: #fff; }
.category-nav { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin: 0 10px 10px; }
.cat-item { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.cat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.cat-item span { font-size: 12px; color: var(--text); }
.section { margin: 0 10px 10px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.section-header h3 { font-size: 16px; }
.more { font-size: 12px; color: var(--text-secondary); }
.product-scroll { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
.product-mini { width: 100px; flex-shrink: 0; }
.product-img { width: 100px; height: 100px; border-radius: 8px; margin-bottom: 6px; }
.product-name { font-size: 12px; color: var(--text); margin-bottom: 4px; }
.product-price { font-size: 14px; color: var(--primary); font-weight: bold; }
.product-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.product-card { background: #fff; border-radius: 8px; overflow: hidden; }
.product-img-lg { width: 100%; height: 160px; }
.product-info { padding: 8px 10px; }
.product-name { font-size: 13px; line-height: 1.4; margin-bottom: 6px; height: 36px; }
.product-bottom { display: flex; justify-content: space-between; align-items: center; }
.sales { font-size: 11px; color: var(--text-secondary); }
</style>
