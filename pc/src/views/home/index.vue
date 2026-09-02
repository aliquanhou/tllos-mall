<template>
  <div class="home-page">
    <div class="search-bar">
      <div class="search-input" @click="goSearch">
        <span class="search-icon">🔍</span>
        <span class="search-placeholder">搜索商品</span>
      </div>
    </div>

    <div class="banner-section" v-if="banners.length">
      <div class="banner-list">
        <div class="banner-item" v-for="(banner, idx) in banners" :key="idx">
          <img :src="banner.image" :alt="banner.title" />
        </div>
      </div>
      <div class="banner-dots">
        <span class="dot" :class="{active: currentBanner === i}" v-for="i in banners.length" :key="i"></span>
      </div>
    </div>

    <div class="category-section" v-if="categories.length">
      <div class="category-grid">
        <div class="category-item" v-for="cat in categories" :key="cat.id" @click="goCategory(cat.id)">
          <div class="category-icon">{{ cat.name.charAt(0) }}</div>
          <span class="category-name">{{ cat.name }}</span>
        </div>
      </div>
    </div>

    <div class="section" v-if="hotProducts.length">
      <div class="section-header">
        <span class="section-title">🔥 热门推荐</span>
      </div>
      <div class="product-scroll">
        <div class="product-card-h" v-for="p in hotProducts" :key="p.id" @click="goDetail(p.id)">
          <img :src="p.main_image" :alt="p.name" />
          <div class="product-info-h">
            <div class="product-name-h">{{ p.name }}</div>
            <div class="product-price-h">¥{{ p.price }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="section" v-if="newProducts.length">
      <div class="section-header">
        <span class="section-title">✨ 新品上市</span>
      </div>
      <div class="product-scroll">
        <div class="product-card-h" v-for="p in newProducts" :key="p.id" @click="goDetail(p.id)">
          <img :src="p.main_image" :alt="p.name" />
          <div class="product-info-h">
            <div class="product-name-h">{{ p.name }}</div>
            <div class="product-price-h">¥{{ p.price }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <span class="section-title">🛍️ 为你推荐</span>
      </div>
      <div class="product-grid">
        <div class="product-card" v-for="p in recommendProducts" :key="p.id" @click="goDetail(p.id)">
          <div class="product-image">
            <img :src="p.main_image" :alt="p.name" />
          </div>
          <div class="product-info">
            <div class="product-name">{{ p.name }}</div>
            <div class="product-bottom">
              <span class="product-price">¥{{ p.price }}</span>
              <span class="product-sales">已售{{ p.sales }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="load-more" v-if="recommendProducts.length >= 10" @click="loadMore">加载更多</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getHomeData } from '@/api/home'

const router = useRouter()
const banners = ref([])
const categories = ref([])
const hotProducts = ref([])
const newProducts = ref([])
const recommendProducts = ref([])
const currentBanner = ref(0)

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
const goCategory = id => router.push({ path: '/product/list', query: { category_id: id } })
const goSearch = () => router.push('/product/list')
const loadMore = () => {}

onMounted(fetchHome)
</script>

<style scoped>
.home-page { padding-bottom: 60px; background: #f5f5f5; min-height: 100vh; }
.search-bar { padding: 10px 15px; background: #fff; position: sticky; top: 0; z-index: 100; }
.search-input { background: #f5f5f5; border-radius: 20px; padding: 8px 15px; display: flex; align-items: center; gap: 8px; }
.search-placeholder { color: #999; font-size: 14px; }
.banner-section { margin: 10px 15px; border-radius: 8px; overflow: hidden; }
.banner-list { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; }
.banner-item { min-width: 100%; scroll-snap-align: start; }
.banner-item img { width: 100%; height: 160px; object-fit: cover; }
.banner-dots { display: flex; justify-content: center; gap: 6px; margin-top: -20px; position: relative; z-index: 1; }
.dot { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,0.5); }
.dot.active { background: #fff; width: 16px; border-radius: 3px; }
.category-section { background: #fff; margin: 10px 15px; border-radius: 8px; padding: 15px; }
.category-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
.category-item { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.category-icon { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.category-name { font-size: 12px; color: #333; }
.section { background: #fff; margin: 10px 15px; border-radius: 8px; padding: 15px; }
.section-header { margin-bottom: 12px; }
.section-title { font-size: 16px; font-weight: bold; color: #333; }
.product-scroll { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
.product-card-h { min-width: 120px; border: 1px solid #eee; border-radius: 8px; overflow: hidden; }
.product-card-h img { width: 120px; height: 120px; object-fit: cover; }
.product-info-h { padding: 8px; }
.product-name-h { font-size: 12px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.product-price-h { font-size: 14px; color: #ff4444; font-weight: bold; margin-top: 4px; }
.product-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.product-card { border: 1px solid #eee; border-radius: 8px; overflow: hidden; background: #fff; }
.product-image img { width: 100%; height: 160px; object-fit: cover; }
.product-info { padding: 10px; }
.product-name { font-size: 13px; color: #333; line-height: 1.4; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.product-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
.product-price { font-size: 16px; color: #ff4444; font-weight: bold; }
.product-sales { font-size: 11px; color: #999; }
.load-more { text-align: center; padding: 12px; color: #999; font-size: 13px; }
</style>
