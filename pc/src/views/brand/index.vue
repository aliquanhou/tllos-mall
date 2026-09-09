<template>
  <div class="brand-page">
    <div class="container">
      <div class="page-header">
        <h2>品牌专区</h2>
        <p>精选优质品牌，品质保障</p>
      </div>
      <!-- 品牌分类筛选 -->
      <div class="brand-filter">
        <span class="filter-label">品牌分类：</span>
        <span class="filter-item" :class="{active: activeCategory === 'all'}" @click="activeCategory = 'all'">全部</span>
        <span class="filter-item" v-for="cat in categories" :key="cat" :class="{active: activeCategory === cat}" @click="activeCategory = cat">{{ cat }}</span>
      </div>
      <!-- 品牌网格 -->
      <div class="brand-grid">
        <div class="brand-card" v-for="brand in filteredBrands" :key="brand.id" @click="goBrand(brand)">
          <div class="brand-logo"><img :src="brand.logo" :alt="brand.name" /></div>
          <div class="brand-name">{{ brand.name }}</div>
          <div class="brand-desc">{{ brand.description }}</div>
          <div class="brand-meta">
            <span>{{ brand.product_count }}件商品</span>
            <span>粉丝{{ brand.fans }}</span>
          </div>
          <div class="brand-action">
            <el-button type="primary" size="small" plain>进入品牌</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
const router = useRouter()
const activeCategory = ref('all')
const categories = ['男装', '女装', '数码', '美妆', '家居', '食品']
const brands = ref([
  { id: 1, name: '品牌A', description: '专注品质男装20年', logo: '', product_count: 128, fans: '12.5万', category: '男装' },
  { id: 2, name: '品牌B', description: '时尚女装领导者', logo: '', product_count: 256, fans: '28.3万', category: '女装' },
  { id: 3, name: '品牌C', description: '智能数码先锋', logo: '', product_count: 64, fans: '8.6万', category: '数码' },
  { id: 4, name: '品牌D', description: '天然美妆护肤', logo: '', product_count: 96, fans: '15.2万', category: '美妆' },
  { id: 5, name: '品牌E', description: '品质家居生活', logo: '', product_count: 180, fans: '6.8万', category: '家居' },
  { id: 6, name: '品牌F', description: '健康食品专家', logo: '', product_count: 72, fans: '4.2万', category: '食品' },
  { id: 7, name: '品牌G', description: '商务男装典范', logo: '', product_count: 88, fans: '9.1万', category: '男装' },
  { id: 8, name: '品牌H', description: '潮流女装设计', logo: '', product_count: 145, fans: '18.7万', category: '女装' },
])
const filteredBrands = computed(() => {
  if (activeCategory.value === 'all') return brands.value
  return brands.value.filter(b => b.category === activeCategory.value)
})
const goBrand = (brand) => { router.push(`/products?brand_id=${brand.id}`) }
</script>
<style scoped>
.brand-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.page-header { margin-bottom: 20px; }
.page-header h2 { font-size: 24px; color: #333; margin: 0 0 8px 0; }
.page-header p { font-size: 14px; color: #999; margin: 0; }
.brand-filter { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.filter-label { font-size: 14px; color: #666; margin-right: 8px; }
.filter-item { font-size: 14px; color: #666; padding: 6px 16px; border-radius: 20px; cursor: pointer; transition: all 0.2s; }
.filter-item:hover { color: #e6a23c; background: #fdf6ec; }
.filter-item.active { color: #fff; background: #e6a23c; }
.brand-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.brand-card { background: #fff; border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s; }
.brand-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.brand-logo { width: 80px; height: 80px; border-radius: 50%; background: #f5f5f5; margin: 0 auto 16px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.brand-logo img { width: 100%; height: 100%; object-fit: cover; }
.brand-name { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 8px; }
.brand-desc { font-size: 13px; color: #999; margin-bottom: 12px; }
.brand-meta { display: flex; justify-content: center; gap: 16px; font-size: 12px; color: #999; margin-bottom: 16px; }
.brand-action { }
</style>
