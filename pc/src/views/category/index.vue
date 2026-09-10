<template>
  <div class="category-page-shein">
    <!-- 门楣 -->
    <PageHeader title="商品分类" subtitle="精选分类 好物云集" />

    <div class="container">
      <!-- 分类金刚区（横向滚动） -->
      <div class="category-quick-nav">
        <div class="quick-nav-scroll">
          <div
            class="quick-nav-item"
            :class="{ active: !activeCategory }"
            @click="selectCategory('')"
          >
            <div class="nav-icon all-icon">
              <el-icon><Grid /></el-icon>
            </div>
            <span class="nav-text">全部</span>
          </div>
          <div
            class="quick-nav-item"
            v-for="cat in categories"
            :key="cat.id"
            :class="{ active: activeCategory === cat.id }"
            @click="selectCategory(cat.id)"
          >
            <div class="nav-icon">
              <img loading="lazy" v-if="cat.icon" :src="cat.icon" :alt="cat.name" class="cat-icon-img" />
              <el-icon v-else><CollectionTag /></el-icon>
            </div>
            <span class="nav-text">{{ cat.name }}</span>
          </div>
        </div>
      </div>

      <!-- 排序&筛选栏 -->
      <div class="sort-filter-bar">
        <div class="sort-items">
          <span class="sort-item" :class="{ active: sort === 'default' }" @click="setSort('default')">
            综合
          </span>
          <span class="sort-item" :class="{ active: sort === 'sales' }" @click="setSort('sales')">
            销量
            <el-icon v-if="sort === 'sales'" class="sort-arrow"><ArrowDown /></el-icon>
          </span>
          <span class="sort-item" :class="{ active: sort === 'price_asc' || sort === 'price_desc' }" @click="togglePriceSort">
            价格
            <el-icon class="sort-arrow" :class="{ asc: sort === 'price_asc', desc: sort === 'price_desc' }">
              <ArrowUp v-if="sort === 'price_asc'" />
              <ArrowDown v-else />
            </el-icon>
          </span>
          <span class="sort-item" :class="{ active: sort === 'new' }" @click="setSort('new')">
            新品
          </span>
        </div>
        <div class="filter-btn" @click="showFilter = true">
          <el-icon><Filter /></el-icon>
          <span>筛选</span>
        </div>
      </div>

      <!-- 结果统计 -->
      <div class="result-stats" v-if="!loading">
        <span>共 <strong>{{ total }}</strong> 件商品</span>
        <span v-if="currentCategoryName" class="current-cat">分类：{{ currentCategoryName }}</span>
      </div>

      <!-- 商品列表 -->
      <Skeleton v-if="loading" type="product-grid" :count="10" />
      <div class="product-grid" v-else-if="products.length">
        <ProductCard v-for="p in products" :key="p.id" :product="p" />
      </div>

      <!-- 空状态 -->
      <div class="empty-state" v-else>
        <div class="empty-icon">
          <el-icon size="64"><Goods /></el-icon>
        </div>
        <p class="empty-title">该分类下暂无商品</p>
        <p class="empty-desc">换个分类看看吧~</p>
        <el-button type="primary" @click="selectCategory('')">查看全部商品</el-button>
      </div>

      <!-- 分页 -->
      <div class="pagination-wrap" v-if="!loading && total > limit">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="limit"
          :total="total"
          layout="prev, pager, next, jumper"
          @current-change="fetchProducts"
        />
      </div>
    </div>

    <!-- 筛选侧边栏 -->
    <el-drawer v-model="showFilter" title="筛选" direction="rtl" size="320px" class="filter-drawer">
      <div class="filter-content">
        <div class="filter-section">
          <h4>价格区间</h4>
          <div class="price-range">
            <el-input v-model="filterPriceMin" placeholder="最低价" type="number" />
            <span class="range-sep">-</span>
            <el-input v-model="filterPriceMax" placeholder="最高价" type="number" />
          </div>
        </div>
        <div class="filter-section">
          <h4>商品分类</h4>
          <div class="filter-tags">
            <span
              class="filter-tag"
              :class="{ active: !activeCategory }"
              @click="selectCategory('')"
            >全部</span>
            <span
              class="filter-tag"
              v-for="cat in categories"
              :key="cat.id"
              :class="{ active: activeCategory === cat.id }"
              @click="selectCategory(cat.id)"
            >{{ cat.name }}</span>
          </div>
        </div>
        <div class="filter-section">
          <h4>排序方式</h4>
          <div class="filter-tags">
            <span class="filter-tag" :class="{ active: sort === 'default' }" @click="setSort('default')">综合</span>
            <span class="filter-tag" :class="{ active: sort === 'sales' }" @click="setSort('sales')">销量优先</span>
            <span class="filter-tag" :class="{ active: sort === 'price_asc' }" @click="setSort('price_asc')">价格从低到高</span>
            <span class="filter-tag" :class="{ active: sort === 'price_desc' }" @click="setSort('price_desc')">价格从高到低</span>
            <span class="filter-tag" :class="{ active: sort === 'new' }" @click="setSort('new')">最新上架</span>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="filter-footer">
          <el-button @click="resetFilter">重置</el-button>
          <el-button type="primary" @click="applyFilter">确定</el-button>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getProductList, getCategories } from '@/api/product'
import ProductCard from '@/components/ProductCard.vue'
import Skeleton from '@/components/Skeleton.vue'
import PageHeader from '@/components/PageHeader.vue'
import {
  Grid, CollectionTag, ArrowDown, ArrowUp, Filter, Goods
} from '@element-plus/icons-vue'

const categories = ref([])
const activeCategory = ref('')
const products = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const sort = ref('default')
const loading = ref(false)
const showFilter = ref(false)
const filterPriceMin = ref('')
const filterPriceMax = ref('')

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

const selectCategory = (id) => {
  activeCategory.value = id
  page.value = 1
  fetchProducts()
}

const fetchProducts = async () => {
  loading.value = true
  try {
    const params = { page: page.value, limit: limit.value, sort: sort.value }
    if (activeCategory.value) params.category_id = activeCategory.value
    if (filterPriceMin.value) params.price_min = filterPriceMin.value
    if (filterPriceMax.value) params.price_max = filterPriceMax.value
    const res = await getProductList(params)
    products.value = res.data?.list || res.data || []
    total.value = res.data?.total || 0
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const setSort = (s) => {
  sort.value = s
  page.value = 1
  fetchProducts()
}

const togglePriceSort = () => {
  sort.value = sort.value === 'price_asc' ? 'price_desc' : 'price_asc'
  page.value = 1
  fetchProducts()
}

const resetFilter = () => {
  filterPriceMin.value = ''
  filterPriceMax.value = ''
  activeCategory.value = ''
  sort.value = 'default'
}

const applyFilter = () => {
  showFilter.value = false
  page.value = 1
  fetchProducts()
}

onMounted(() => {
  fetchCategories()
  fetchProducts()
})
</script>

<style scoped>
/* ========== SHEIN风格分类页 ========== */
.category-page-shein {
  background: #f8fafc;
  min-height: calc(100vh - 200px);
  padding-bottom: 40px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* 分类金刚区 */
.category-quick-nav {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.quick-nav-scroll {
  display: flex;
  gap: 16px;
  overflow-x: auto;
  padding-bottom: 4px;
  scrollbar-width: none;
}

.quick-nav-scroll::-webkit-scrollbar {
  display: none;
}

.quick-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  flex-shrink: 0;
  min-width: 64px;
  transition: transform 0.2s;
}

.quick-nav-item:active {
  transform: scale(0.95);
}

.nav-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: #64748b;
  transition: all 0.3s;
}

.quick-nav-item.active .nav-icon {
  background: linear-gradient(135deg, #f97316, #fb923c);
  color: #fff;
  box-shadow: 0 4px 12px rgba(249,115,22,0.3);
}

.all-icon {
  background: linear-gradient(135deg, #06b6d4, #22d3ee);
  color: #fff;
}

.cat-icon-img {
  width: 28px;
  height: 28px;
  object-fit: contain;
}

.nav-text {
  font-size: 12px;
  color: #475569;
  font-weight: 500;
  white-space: nowrap;
}

.quick-nav-item.active .nav-text {
  color: #f97316;
  font-weight: 600;
}

/* 排序&筛选栏 */
.sort-filter-bar {
  background: #fff;
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.sort-items {
  display: flex;
  gap: 8px;
}

.sort-item {
  font-size: 14px;
  color: #64748b;
  cursor: pointer;
  padding: 6px 14px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 4px;
  transition: all 0.2s;
  font-weight: 500;
}

.sort-item:hover {
  color: #f97316;
  background: #fff7ed;
}

.sort-item.active {
  color: #fff;
  background: linear-gradient(135deg, #f97316, #fb923c);
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(249,115,22,0.3);
}

.sort-arrow {
  font-size: 12px;
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
}

.filter-btn:hover {
  background: #e2e8f0;
  color: #1e293b;
}

/* 结果统计 */
.result-stats {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 4px 12px;
  font-size: 13px;
  color: #64748b;
}

.result-stats strong {
  color: #f97316;
  font-size: 16px;
  font-weight: 700;
}

.current-cat {
  padding: 2px 10px;
  background: #fff7ed;
  color: #f97316;
  border-radius: 6px;
  font-size: 12px;
}

/* 商品网格 */
.product-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

/* 空状态 */
.empty-state {
  background: #fff;
  border-radius: 12px;
  padding: 60px 20px;
  text-align: center;
  margin-bottom: 24px;
}

.empty-icon {
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 16px;
  color: #475569;
  margin: 0 0 8px 0;
  font-weight: 600;
}

.empty-desc {
  font-size: 13px;
  color: #94a3b8;
  margin: 0 0 20px 0;
}

/* 分页 */
.pagination-wrap {
  display: flex;
  justify-content: center;
}

/* 筛选抽屉 */
.filter-drawer :deep(.el-drawer__body) {
  padding: 0;
}

.filter-content {
  padding: 20px;
}

.filter-section {
  margin-bottom: 24px;
}

.filter-section h4 {
  font-size: 14px;
  color: #1e293b;
  margin: 0 0 12px 0;
  font-weight: 600;
}

.price-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.range-sep {
  color: #94a3b8;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.filter-tag {
  padding: 6px 14px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-tag:hover {
  background: #e2e8f0;
}

.filter-tag.active {
  background: linear-gradient(135deg, #f97316, #fb923c);
  color: #fff;
  font-weight: 500;
}

.filter-footer {
  display: flex;
  gap: 12px;
  padding: 16px 20px;
}

.filter-footer .el-button {
  flex: 1;
}

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .category-page-shein {
    padding-bottom: 20px;
    min-height: calc(100vh - 120px);
  }

  .container {
    max-width: 100%;
    padding: 0 12px;
  }

  /* 金刚区 */
  .category-quick-nav {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
  }

  .quick-nav-scroll {
    gap: 12px;
  }

  .quick-nav-item {
    min-width: 56px;
    gap: 6px;
  }

  .nav-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    font-size: 18px;
  }

  .nav-text {
    font-size: 11px;
  }

  /* 筛选栏 */
  .sort-filter-bar {
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 8px;
  }

  .sort-items {
    gap: 4px;
  }

  .sort-item {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 6px;
  }

  .filter-btn {
    padding: 5px 10px;
    font-size: 12px;
    border-radius: 6px;
  }

  .filter-btn span {
    display: none;
  }

  /* 结果统计 */
  .result-stats {
    font-size: 12px;
    padding: 0 2px 8px;
  }

  .result-stats strong {
    font-size: 14px;
  }

  /* 商品网格 */
  .product-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
  }

  /* 空状态 */
  .empty-state {
    padding: 40px 16px;
    border-radius: 8px;
  }

  .empty-title {
    font-size: 14px;
  }

  .empty-desc {
    font-size: 12px;
  }

  /* 分页 */
  .pagination-wrap {
    overflow-x: auto;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 8px;
  }

  .product-grid {
    gap: 8px;
  }

  .sort-item {
    padding: 4px 8px;
    font-size: 11px;
  }
}
</style>
