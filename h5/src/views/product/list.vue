<template>
  <div class="product-list-page">
    <div class="search-bar">
      <input v-model="keyword" class="search-input" placeholder="搜索商品" @keyup.enter="fetchList" />
      <button class="search-btn" @click="fetchList">搜索</button>
    </div>
    <div class="filter-bar">
      <div class="filter-item" :class="{active: sort === 'default'}" @click="setSort('default')">综合</div>
      <div class="filter-item" :class="{active: sort === 'sales'}" @click="setSort('sales')">销量</div>
      <div class="filter-item" :class="{active: sort === 'price_asc'}" @click="setSort('price_asc')">价格↑</div>
      <div class="filter-item" :class="{active: sort === 'price_desc'}" @click="setSort('price_desc')">价格↓</div>
    </div>
    <div class="product-grid" v-if="products.length">
      <div class="product-card" v-for="p in products" :key="p.id" @click="goDetail(p.id)">
        <div class="product-image"><img :src="p.main_image" :alt="p.name" /></div>
        <div class="product-info">
          <div class="product-name">{{ p.name }}</div>
          <div class="product-bottom">
            <span class="product-price">¥{{ p.price }}</span>
            <span class="product-sales">已售{{ p.sales }}</span>
          </div>
        </div>
      </div>
    </div>
    <div class="empty" v-else-if="!loading">暂无商品</div>
    <div class="loading" v-if="loading">加载中...</div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getProductList } from '@/api/product'
const route = useRoute()
const router = useRouter()
const products = ref([])
const keyword = ref('')
const sort = ref('default')
const page = ref(1)
const loading = ref(false)
const fetchList = async () => {
  loading.value = true
  try {
    const res = await getProductList({ page: page.value, limit: 20, keyword: keyword.value || undefined, sort: sort.value, category_id: route.query.category_id || undefined })
    products.value = res.data.list || []
  } catch (e) { console.error(e) } finally { loading.value = false }
}
const setSort = s => { sort.value = s; page.value = 1; fetchList() }
const goDetail = id => router.push(`/product/${id}`)
onMounted(fetchList)
</script>
<style scoped>
.product-list-page { padding-bottom: 20px; background: #f5f5f5; min-height: 100vh; }
.search-bar { display: flex; gap: 10px; padding: 10px 15px; background: #fff; }
.search-input { flex: 1; padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; font-size: 14px; }
.search-btn { padding: 8px 20px; background: #ff4444; color: #fff; border: none; border-radius: 20px; font-size: 14px; }
.filter-bar { display: flex; background: #fff; padding: 10px 15px; border-top: 1px solid #eee; }
.filter-item { flex: 1; text-align: center; font-size: 13px; color: #666; padding: 5px; }
.filter-item.active { color: #ff4444; font-weight: bold; }
.product-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 10px 15px; }
.product-card { border: 1px solid #eee; border-radius: 8px; overflow: hidden; background: #fff; }
.product-image img { width: 100%; height: 160px; object-fit: cover; }
.product-info { padding: 10px; }
.product-name { font-size: 13px; color: #333; line-height: 1.4; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.product-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
.product-price { font-size: 16px; color: #ff4444; font-weight: bold; }
.product-sales { font-size: 11px; color: #999; }
.empty, .loading { text-align: center; padding: 40px; color: #999; }
</style>
