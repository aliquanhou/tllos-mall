<template>
  <div class="product-list-page">
    <div class="container">
      <!-- 面包屑 -->
      <div class="breadcrumb">
        <a href="javascript:;" @click="goHome">{{ t('product.home') }}</a>
        <span class="sep">/</span>
        <span class="current">{{ currentCategoryName || t('product.allProducts') }}</span>
        <span class="sep" v-if="keyword">/</span>
        <span class="current" v-if="keyword">{{ t('product.search') }}：{{ keyword }}</span>
      </div>

      <div class="list-wrapper">
        <!-- 左侧筛选栏（PC端） -->
        <aside class="filter-sidebar" v-if="!isMobile">
          <div class="filter-section">
            <h4 class="filter-title">{{ t('product.category') }}</h4>
            <div class="filter-options">
              <a href="javascript:;" class="filter-option" :class="{active: !categoryId}" @click="setFilter('category_id', '')">{{ t('product.all') }}</a>
              <a href="javascript:;" class="filter-option" v-for="cat in categories" :key="cat.id" :class="{active: categoryId == cat.id}" @click="setFilter('category_id', cat.id)">{{ cat.name }}</a>
            </div>
          </div>
          <div class="filter-section">
            <h4 class="filter-title">{{ t('product.priceRange') }}</h4>
            <div class="filter-options">
              <a href="javascript:;" class="filter-option" :class="{active: !priceRange}" @click="setFilter('price_range', '')">{{ t('product.all') }}</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '0-50'}" @click="setFilter('price_range', '0-50')">¥0-50</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '50-100'}" @click="setFilter('price_range', '50-100')">¥50-100</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '100-500'}" @click="setFilter('price_range', '100-500')">¥100-500</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '500-99999'}" @click="setFilter('price_range', '500-99999')">¥500+</a>
            </div>
          </div>
          <div class="filter-section">
            <el-button type="primary" plain size="small" @click="resetFilters" style="width:100%">{{ t('product.resetFilter') }}</el-button>
          </div>
        </aside>

        <!-- 右侧内容区 -->
        <div class="list-content">
          <!-- 排序栏 -->
          <div class="sort-bar">
            <div class="sort-left">
              <span class="sort-item" :class="{active: sort === 'default'}" @click="setSort('default')">{{ t('product.comprehensive') }}</span>
              <span class="sort-item" :class="{active: sort === 'sales'}" @click="setSort('sales')">{{ t('product.sales') }}</span>
              <span class="sort-item" :class="{active: sort === 'price_asc' || sort === 'price_desc'}" @click="togglePriceSort">
                {{ t('product.price') }}
                <el-icon v-if="sort === 'price_asc'"><SortUp /></el-icon>
                <el-icon v-else-if="sort === 'price_desc'"><SortDown /></el-icon>
                <el-icon v-else><Sort /></el-icon>
              </span>
              <span class="sort-item" :class="{active: sort === 'new'}" @click="setSort('new')">{{ t('product.new') }}</span>
            </div>
            <div class="sort-right">
              <span class="result-count">{{ t('product.total') }} <b>{{ total }}</b> {{ t('product.items') }}</span>
            </div>
          </div>

          <!-- 移动端筛选按钮 -->
          <div class="mobile-filter-bar" v-if="isMobile">
            <el-button size="small" plain @click="showMobileFilter = true">
              <el-icon><Filter /></el-icon> {{ t('product.filter') }}
            </el-button>
            <span class="mobile-result">{{ total }} {{ t('product.items') }}</span>
          </div>

          <!-- 已选筛选标签 -->
          <div class="active-filters" v-if="activeFilters.length">
            <span class="filter-tag" v-for="f in activeFilters" :key="f.key">
              {{ f.label }}
              <el-icon size="12" @click="setFilter(f.key, '')"><Close /></el-icon>
            </span>
          </div>

          <!-- 商品网格 -->
          <div class="product-grid" v-if="!loading && products.length">
            <div class="product-card" v-for="p in products" :key="p.id" @click="goDetail(p.id)">
              <div class="product-image">
                <img :src="getProductImage(p)" :alt="p.name" @error="handleImgError($event, p)" />
                <div class="product-badges">
                  <span class="badge badge-hot" v-if="p.sales > 50">{{ t('product.hot') }}</span>
                  <span class="badge badge-new" v-if="isNewProduct(p)">{{ t('product.newArrival') }}</span>
                </div>
              </div>
              <div class="product-info">
                <div class="product-name">{{ p.name }}</div>
                <div class="product-price-row">
                  <span class="product-price">¥{{ Number(p.price).toFixed(2) }}</span>
                  <span class="product-original" v-if="p.market_price && p.market_price > p.price">¥{{ Number(p.market_price).toFixed(2) }}</span>
                </div>
                <div class="product-meta">
                  <span class="product-sales">{{ t('product.sold') }} {{ p.sales || 0 }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 骨架屏 -->
          <div class="product-grid" v-if="loading">
            <div class="skeleton-card" v-for="i in 10" :key="i">
              <div class="skeleton-image"></div>
              <div class="skeleton-info">
                <div class="skeleton-line" style="width: 80%"></div>
                <div class="skeleton-line" style="width: 60%"></div>
                <div class="skeleton-line" style="width: 40%"></div>
              </div>
            </div>
          </div>

          <!-- 空状态 -->
          <div class="empty-state" v-if="!loading && !products.length">
            <el-icon :size="64" color="#ddd"><Goods /></el-icon>
            <p>{{ t('product.noProducts') }}</p>
            <el-button type="primary" @click="resetFilters">{{ t('product.browseAll') }}</el-button>
          </div>

          <!-- 分页 -->
          <div class="pagination-wrapper" v-if="!loading && products.length">
            <el-pagination
              v-model:current-page="page"
              v-model:page-size="limit"
              :total="total"
              :page-sizes="[20, 40, 60]"
              layout="total, prev, pager, next"
              @size-change="handleSizeChange"
              @current-change="handlePageChange"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- 移动端筛选抽屉 -->
    <el-drawer v-model="showMobileFilter" :title="t('product.filter')" direction="right" size="80%">
      <div class="mobile-filter-content">
        <div class="filter-section">
          <h4 class="filter-title">{{ t('product.category') }}</h4>
          <div class="filter-options">
            <a href="javascript:;" class="filter-option" :class="{active: !categoryId}" @click="setFilter('category_id', '')">{{ t('product.all') }}</a>
            <a href="javascript:;" class="filter-option" v-for="cat in categories" :key="cat.id" :class="{active: categoryId == cat.id}" @click="setFilter('category_id', cat.id)">{{ cat.name }}</a>
          </div>
        </div>
        <div class="filter-section">
          <h4 class="filter-title">{{ t('product.priceRange') }}</h4>
          <div class="filter-options">
            <a href="javascript:;" class="filter-option" :class="{active: !priceRange}" @click="setFilter('price_range', '')">{{ t('product.all') }}</a>
            <a href="javascript:;" class="filter-option" :class="{active: priceRange === '0-50'}" @click="setFilter('price_range', '0-50')">¥0-50</a>
            <a href="javascript:;" class="filter-option" :class="{active: priceRange === '50-100'}" @click="setFilter('price_range', '50-100')">¥50-100</a>
            <a href="javascript:;" class="filter-option" :class="{active: priceRange === '100-500'}" @click="setFilter('price_range', '100-500')">¥100-500</a>
            <a href="javascript:;" class="filter-option" :class="{active: priceRange === '500-99999'}" @click="setFilter('price_range', '500-99999')">¥500+</a>
          </div>
        </div>
        <div class="filter-actions">
          <el-button plain @click="resetFilters">{{ t('product.reset') }}</el-button>
          <el-button type="primary" @click="showMobileFilter = false">{{ t('product.confirm') }}</el-button>
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getProductList, getCategories } from '@/api/product'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const products = ref([])
const categories = ref([])
const total = ref(0)
const loading = ref(false)
const isMobile = ref(false)
const showMobileFilter = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchCategories()
  fetchList()
})

const keyword = ref(route.query.keyword || '')
const categoryId = ref(route.query.category_id || '')
const sort = ref(route.query.sort || 'default')
const page = ref(parseInt(route.query.page) || 1)
const limit = ref(parseInt(route.query.limit) || 20)
const priceRange = ref(route.query.price_range || '')
const status = ref(route.query.status || '')

const currentCategoryName = computed(() => {
  const cat = categories.value.find(c => c.id == categoryId.value)
  return cat?.name || ''
})

const activeFilters = computed(() => {
  const list = []
  if (categoryId.value) list.push({ key: 'category_id', label: `${t('product.category')}：${currentCategoryName.value}` })
  if (priceRange.value) list.push({ key: 'price_range', label: `${t('product.price')}：¥${priceRange.value.replace('-', '-¥')}` })
  if (keyword.value) list.push({ key: 'keyword', label: `${t('product.search')}：${keyword.value}` })
  return list
})

const getProductImage = (p) => {
  const img = p.main_image || p.image || p.cover_image || (p.images && p.images[0]) || ''
  if (!img) return 'https://picsum.photos/300/300?random=' + p.id
  if (img.startsWith('http')) return img
  return 'https://mall.tllos.com' + (img.startsWith('/') ? '' : '/') + img
}

const handleImgError = (event, p) => {
  event.target.src = 'https://picsum.photos/300/300?random=' + p.id
}

const isNewProduct = (p) => {
  if (!p.created_at) return false
  const created = new Date(p.created_at).getTime()
  return Date.now() - created < 7 * 24 * 60 * 60 * 1000
}

const goDetail = (id) => {
  if (id) router.push('/product/' + id)
}

const goHome = () => router.push('/')

const syncToUrl = () => {
  const query = {}
  if (keyword.value) query.keyword = keyword.value
  if (categoryId.value) query.category_id = categoryId.value
  if (sort.value !== 'default') query.sort = sort.value
  if (page.value > 1) query.page = page.value
  if (limit.value !== 20) query.limit = limit.value
  if (priceRange.value) query.price_range = priceRange.value
  router.replace({ path: '/products', query })
}

const fetchCategories = async () => {
  try {
    const res = await getCategories()
    categories.value = res.data?.list || res.data || []
  } catch (e) {
    console.error(e)
  }
}

const fetchList = async () => {
  loading.value = true
  try {
    const params = { page: page.value, limit: limit.value, sort: sort.value }
    if (keyword.value) params.keyword = keyword.value
    if (categoryId.value) params.category_id = categoryId.value
    if (priceRange.value) {
      const [min, max] = priceRange.value.split('-')
      params.min_price = min
      params.max_price = max
    }
    const res = await getProductList(params)
    products.value = res.data?.list || res.data?.data || res.data || []
    total.value = res.data?.total || 0
  } catch (e) {
    console.error(e)
    products.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

const setFilter = (key, value) => {
  if (key === 'category_id') categoryId.value = value
  if (key === 'price_range') priceRange.value = value
  if (key === 'keyword') keyword.value = value
  page.value = 1
  syncToUrl()
  fetchList()
}

const setSort = (s) => {
  sort.value = s
  page.value = 1
  syncToUrl()
  fetchList()
}

const togglePriceSort = () => {
  if (sort.value === 'price_asc') sort.value = 'price_desc'
  else if (sort.value === 'price_desc') sort.value = 'default'
  else sort.value = 'price_asc'
  page.value = 1
  syncToUrl()
  fetchList()
}

const resetFilters = () => {
  categoryId.value = ''
  priceRange.value = ''
  keyword.value = ''
  sort.value = 'default'
  page.value = 1
  showMobileFilter.value = false
  syncToUrl()
  fetchList()
}

const handlePageChange = (p) => {
  page.value = p
  syncToUrl()
  fetchList()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleSizeChange = (s) => {
  limit.value = s
  page.value = 1
  syncToUrl()
  fetchList()
}

watch(() => route.query, (newQuery) => {
  keyword.value = newQuery.keyword || ''
  categoryId.value = newQuery.category_id || ''
  sort.value = newQuery.sort || 'default'
  page.value = parseInt(newQuery.page) || 1
  limit.value = parseInt(newQuery.limit) || 20
  priceRange.value = newQuery.price_range || ''
  fetchList()
}, { deep: true })
</script>

<style scoped>
.product-list-page {
  min-height: calc(100vh - 200px);
  padding-bottom: 20px;
}

.breadcrumb {
  padding: 16px 0;
  font-size: 13px;
  color: var(--color-text-secondary);
}
.breadcrumb a {
  color: var(--color-text-secondary);
}
.breadcrumb a:hover {
  color: var(--color-primary);
}
.breadcrumb .sep {
  margin: 0 8px;
  color: var(--color-text-placeholder);
}
.breadcrumb .current {
  color: var(--color-text-regular);
}

.list-wrapper {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

/* 筛选栏 */
.filter-sidebar {
  width: 220px;
  flex-shrink: 0;
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 16px;
  position: sticky;
  top: 20px;
}
.filter-section {
  margin-bottom: 20px;
}
.filter-section:last-child {
  margin-bottom: 0;
}
.filter-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--color-border-light);
  color: var(--color-text-regular);
}
.filter-options {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.filter-option {
  padding: 6px 10px;
  font-size: 13px;
  color: var(--color-text-secondary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all var(--transition-fast);
}
.filter-option:hover {
  background: var(--color-bg-hover);
  color: var(--color-primary);
}
.filter-option.active {
  background: var(--color-primary-bg);
  color: var(--color-primary);
  font-weight: 500;
}

/* 内容区 */
.list-content {
  flex: 1;
  min-width: 0;
}

/* 排序栏 */
.sort-bar {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.sort-left {
  display: flex;
  align-items: center;
  gap: 20px;
}
.sort-item {
  font-size: 14px;
  color: var(--color-text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  transition: color var(--transition-fast);
}
.sort-item:hover {
  color: var(--color-primary);
}
.sort-item.active {
  color: var(--color-primary);
  font-weight: 600;
}
.result-count {
  font-size: 13px;
  color: var(--color-text-secondary);
}
.result-count b {
  color: var(--color-primary);
  font-weight: 600;
}

/* 移动端筛选栏 */
.mobile-filter-bar {
  display: none;
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  margin-bottom: 12px;
  align-items: center;
  justify-content: space-between;
}
.mobile-result {
  font-size: 13px;
  color: var(--color-text-secondary);
}

/* 已选筛选标签 */
.active-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}
.filter-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  background: var(--color-primary-bg);
  color: var(--color-primary);
  font-size: 12px;
  border-radius: var(--radius-full);
  cursor: pointer;
}
.filter-tag .el-icon {
  cursor: pointer;
}

/* 商品网格 */
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

/* 商品卡片 */
.product-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: pointer;
  transition: all var(--transition-base);
}
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}
.product-image {
  position: relative;
  width: 100%;
  padding-top: 100%;
  background: var(--color-bg-page);
  overflow: hidden;
}
.product-image img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-slow);
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
  border-radius: var(--radius-sm);
  color: #fff;
  font-weight: 500;
}
.badge-hot {
  background: var(--color-danger);
}
.badge-new {
  background: var(--color-success);
}
.product-info {
  padding: 12px;
}
.product-name {
  font-size: 13px;
  color: var(--color-text-regular);
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
  color: var(--color-danger);
  font-weight: 700;
}
.product-original {
  font-size: 12px;
  color: var(--color-text-placeholder);
  text-decoration: line-through;
}
.product-meta {
  display: flex;
  align-items: center;
}
.product-sales {
  font-size: 11px;
  color: var(--color-text-placeholder);
}

/* 骨架屏 */
.skeleton-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  overflow: hidden;
}
.skeleton-image {
  width: 100%;
  padding-top: 100%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}
.skeleton-info {
  padding: 12px;
}
.skeleton-line {
  height: 12px;
  margin-bottom: 8px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: 4px;
}
@keyframes skeleton-loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* 分页 */
.pagination-wrapper {
  margin-top: 24px;
  display: flex;
  justify-content: center;
}

/* 移动端筛选抽屉 */
.mobile-filter-content {
  padding: 16px;
}
.filter-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}
.filter-actions .el-button {
  flex: 1;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .breadcrumb {
    padding: 12px 0;
    font-size: 12px;
  }
  .list-wrapper {
    flex-direction: column;
    gap: 0;
  }
  .filter-sidebar {
    display: none;
  }
  .sort-bar {
    padding: 10px 12px;
    margin-bottom: 12px;
    overflow-x: auto;
  }
  .sort-left {
    gap: 16px;
  }
  .sort-item {
    font-size: 13px;
    white-space: nowrap;
  }
  .result-count {
    display: none;
  }
  .mobile-filter-bar {
    display: flex;
  }
  .active-filters {
    margin-bottom: 12px;
  }
  .product-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .product-info {
    padding: 8px;
  }
  .product-name {
    font-size: 12px;
    min-height: 32px;
    margin-bottom: 6px;
  }
  .product-price {
    font-size: 14px;
  }
  .pagination-wrapper {
    margin-top: 16px;
  }
}
</style>
