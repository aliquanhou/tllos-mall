<template>
  <div class="category-page">
    <div class="container">
      <div class="category-wrapper">
        <!-- 左侧分类导航 -->
        <aside class="category-sidebar">
          <h3>商品分类</h3>
          <div class="category-tree">
            <div class="category-item" v-for="cat in categories" :key="cat.id" :class="{active: activeCategory === cat.id}" @click="selectCategory(cat.id)">
              <el-icon><CollectionTag /></el-icon>
              <span>{{ cat.name }}</span>
              <el-icon class="arrow"><ArrowRight /></el-icon>
            </div>
          </div>
        </aside>
        <!-- 右侧商品列表 -->
        <div class="category-content">
          <div class="content-header">
            <h2>{{ currentCategoryName || '全部商品' }}</h2>
            <div class="sort-bar">
              <span class="sort-item" :class="{active: sort === 'default'}" @click="setSort('default')">综合</span>
              <span class="sort-item" :class="{active: sort === 'sales'}" @click="setSort('sales')">销量</span>
              <span class="sort-item" :class="{active: sort === 'price_asc' || sort === 'price_desc'}" @click="togglePriceSort">价格</span>
              <span class="sort-item" :class="{active: sort === 'new'}" @click="setSort('new')">新品</span>
            </div>
          </div>
          <Skeleton v-if="loading" type="product-grid" :count="10" />
          <div class="product-grid" v-else-if="products.length">
            <ProductCard v-for="p in products" :key="p.id" :product="p" />
          </div>
          <div class="empty-category" v-else>
            <el-icon size="64" color="#ddd"><Goods /></el-icon>
            <p>该分类下暂无商品</p>
          </div>
          <div class="pagination-wrap" v-if="!loading && total > limit">
            <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" layout="prev, pager, next, jumper" @current-change="fetchProducts" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { getProductList, getCategories } from '@/api/product'
import ProductCard from '@/components/ProductCard.vue'
import Skeleton from '@/components/Skeleton.vue'
const categories = ref([])
const activeCategory = ref('')
const products = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const sort = ref('default')
const loading = ref(false)
const currentCategoryName = computed(() => {
  const cat = categories.value.find(c => c.id == activeCategory.value)
  return cat?.name || ''
})
const fetchCategories = async () => {
  try {
    const res = await getCategories()
    categories.value = res.data?.list || res.data || []
  } catch (e) { console.error(e) }
}
const selectCategory = (id) => { activeCategory.value = id; page.value = 1; fetchProducts() }
const fetchProducts = async () => {
  loading.value = true
  try {
    const params = { page: page.value, limit: limit.value, sort: sort.value }
    if (activeCategory.value) params.category_id = activeCategory.value
    const res = await getProductList(params)
    products.value = res.data?.list || res.data || []
    total.value = res.data?.total || 0
  } catch (e) { console.error(e) } finally { loading.value = false }
}
const setSort = (s) => { sort.value = s; page.value = 1; fetchProducts() }
const togglePriceSort = () => { sort.value = sort.value === 'price_asc' ? 'price_desc' : 'price_asc'; page.value = 1; fetchProducts() }
onMounted(() => { fetchCategories(); fetchProducts() })
</script>
<style scoped>
.category-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.category-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.category-sidebar { width: 200px; flex-shrink: 0; background: #fff; border-radius: 8px; padding: 16px; position: sticky; top: 20px; }
.category-sidebar h3 { font-size: 16px; color: #333; margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
.category-tree { }
.category-item { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 6px; cursor: pointer; font-size: 14px; color: #666; transition: all 0.2s; margin-bottom: 4px; }
.category-item:hover { background: #fafafa; color: #e6a23c; }
.category-item.active { background: #fdf6ec; color: #e6a23c; font-weight: bold; }
.category-item .arrow { margin-left: auto; font-size: 12px; opacity: 0.5; }
.category-content { flex: 1; min-width: 0; }
.content-header { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
.content-header h2 { font-size: 18px; color: #333; margin: 0; }
.sort-bar { display: flex; gap: 20px; }
.sort-item { font-size: 14px; color: #666; cursor: pointer; padding: 4px 8px; border-radius: 4px; }
.sort-item:hover { color: #e6a23c; }
.sort-item.active { color: #e6a23c; background: #fdf6ec; font-weight: bold; }
.product-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
.empty-category { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-category p { color: #999; margin: 16px 0; }
.pagination-wrap { display: flex; justify-content: center; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .category-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .category-wrapper { flex-direction: column; gap: 10px; }
  .category-sidebar { width: 100%; position: static; padding: 12px; border-radius: 6px; }
  .category-sidebar h3 { font-size: 14px; margin-bottom: 10px; padding-bottom: 8px; }
  .category-tree { display: flex; overflow-x: auto; gap: 8px; padding-bottom: 4px; }
  .category-item { padding: 8px 12px; margin-bottom: 0; font-size: 13px; white-space: nowrap; flex-shrink: 0; }
  .category-item .arrow { display: none; }
  .category-content { width: 100%; }
  .content-header { padding: 12px; margin-bottom: 10px; border-radius: 6px; flex-wrap: wrap; gap: 8px; }
  .content-header h2 { font-size: 15px; }
  .sort-bar { gap: 12px; flex-wrap: wrap; }
  .sort-item { font-size: 12px; padding: 3px 6px; }
  .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 10px; }
  .empty-category { padding: 40px 16px; border-radius: 6px; }
  .empty-category p { font-size: 13px; margin: 12px 0; }
  .pagination-wrap { overflow-x: auto; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .product-grid { gap: 8px; }
  .category-item { padding: 6px 10px; font-size: 12px; }
}
</style>
