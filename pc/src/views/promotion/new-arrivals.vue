<template>
  <div class="new-arrivals-page">
    <div class="container">
      <div class="page-header">
        <div class="header-content">
          <h2>新品上市</h2>
          <p>发现最新潮流，抢先体验</p>
        </div>
        <div class="header-filter">
          <el-select v-model="activeCategory" placeholder="全部分类" style="width: 160px" @change="fetchProducts">
            <el-option label="全部分类" value="" />
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
          <el-select v-model="sortBy" placeholder="默认排序" style="width: 140px" @change="fetchProducts">
            <el-option label="最新上架" value="newest" />
            <el-option label="销量优先" value="sales" />
            <el-option label="价格从低到高" value="price_asc" />
            <el-option label="价格从高到低" value="price_desc" />
          </el-select>
        </div>
      </div>
      <!-- 新品Banner -->
      <div class="new-banner">
        <div class="banner-content">
          <h3>秋季新品系列</h3>
          <p>2026秋季新品全面上市，限时享新品特惠</p>
          <el-button type="primary" size="large">立即选购</el-button>
        </div>
      </div>
      <!-- 商品网格 -->
      <Skeleton v-if="loading" type="product-grid" :count="12" />
      <div class="product-grid" v-else-if="products.length">
        <div class="product-card" v-for="product in products" :key="product.id" @click="$router.push(`/product/${product.id}`)">
          <div class="product-image">
            <img :src="product.main_image" :alt="product.name" />
            <div class="new-tag">NEW</div>
          </div>
          <div class="product-info">
            <div class="product-name">{{ product.name }}</div>
            <div class="product-price">¥{{ product.price }}</div>
            <div class="product-meta">
              <span>上架{{ product.days_ago }}天前</span>
              <span>已售{{ product.sales }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="empty-state" v-else>
        <el-icon size="64" color="#ddd"><Goods /></el-icon>
        <p>暂无新品</p>
      </div>
      <div class="pagination-wrap" v-if="!loading && total > limit">
        <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" layout="prev, pager, next, jumper" @current-change="fetchProducts" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import Skeleton from '@/components/Skeleton.vue'
const loading = ref(false)
const products = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(12)
const activeCategory = ref('')
const sortBy = ref('newest')
const categories = ref([
  { id: 1, name: '男装' }, { id: 2, name: '女装' }, { id: 3, name: '数码' },
  { id: 4, name: '美妆' }, { id: 5, name: '家居' }, { id: 6, name: '食品' },
])
const fetchProducts = () => {
  loading.value = true
  setTimeout(() => {
    products.value = Array.from({ length: 12 }, (_, i) => ({
      id: i + 1, name: `新品商品${i + 1}`, main_image: '', price: (99 + i * 50).toFixed(2),
      days_ago: i + 1, sales: Math.floor(Math.random() * 500) + 10
    }))
    total.value = 48
    loading.value = false
  }, 500)
}
onMounted(fetchProducts)
</script>
<style scoped>
.new-arrivals-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
.header-content h2 { font-size: 24px; color: #333; margin: 0 0 8px 0; }
.header-content p { font-size: 14px; color: #999; margin: 0; }
.header-filter { display: flex; gap: 12px; }
.new-banner { background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 12px; padding: 40px; margin-bottom: 24px; color: #fff; }
.banner-content h3 { font-size: 28px; margin: 0 0 12px 0; }
.banner-content p { font-size: 15px; opacity: 0.9; margin: 0 0 20px 0; }
.product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.product-card { background: #fff; border-radius: 8px; overflow: hidden; cursor: pointer; transition: all 0.2s; }
.product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.product-image { position: relative; padding-top: 100%; background: #fafafa; }
.product-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.new-tag { position: absolute; top: 10px; left: 10px; background: #67c23a; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; letter-spacing: 1px; }
.product-info { padding: 12px; }
.product-name { font-size: 14px; color: #333; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 8px; }
.product-price { font-size: 18px; color: #f56c6c; font-weight: bold; margin-bottom: 8px; }
.product-meta { display: flex; justify-content: space-between; font-size: 12px; color: #999; }
.empty-state { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-state p { color: #999; margin: 16px 0; }
.pagination-wrap { display: flex; justify-content: center; }
</style>
