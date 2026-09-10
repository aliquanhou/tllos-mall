<template>
  <div class="product-list-page-shein">
    <!-- 门楣 -->
    <PageHeader :title="pageTitle" :subtitle="pageSubtitle" />

    <div class="container">
      <!-- 运营banner条 -->
      <div class="promo-banner">
        <div class="promo-icon">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="promo-text">
          <span class="promo-title">本周上新</span>
          <span class="promo-desc">7天新品优先发货 · 品质保障</span>
        </div>
        <div class="promo-tag">NEW</div>
      </div>

      <!-- 页面标题栏 -->
      <div class="page-title-bar">
        <div class="title-left">
          <h2 class="page-title">{{ pageTitle }}</h2>
          <span class="title-breadcrumb" v-if="keyword">
            {{ t('product.search') }}：{{ keyword }}
          </span>
        </div>
        <div class="title-right">
          <span class="total-count">{{ t('product.total') }} <b>{{ total }}</b> {{ t('product.items') }}</span>
        </div>
      </div>

      <div class="list-wrapper">
        <!-- 左侧筛选栏（PC端） -->
        <aside class="filter-sidebar" v-if="!isMobile">
          <div class="filter-section">
            <h4 class="filter-title">
              <el-icon><Grid /></el-icon>
              {{ t('product.category') }}
            </h4>
            <div class="filter-options">
              <a href="javascript:;" class="filter-option" :class="{active: !categoryId}" @click="setFilter('category_id', '')">{{ t('product.all') }}</a>
              <a href="javascript:;" class="filter-option" v-for="cat in categories" :key="cat.id" :class="{active: categoryId == cat.id}" @click="setFilter('category_id', cat.id)">{{ cat.name }}</a>
            </div>
          </div>
          <div class="filter-section">
            <h4 class="filter-title">
              <el-icon><PriceTag /></el-icon>
              {{ t('product.priceRange') }}
            </h4>
            <div class="filter-options">
              <a href="javascript:;" class="filter-option" :class="{active: !priceRange}" @click="setFilter('price_range', '')">{{ t('product.all') }}</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '0-50'}" @click="setFilter('price_range', '0-50')">¥0-50</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '50-100'}" @click="setFilter('price_range', '50-100')">¥50-100</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '100-500'}" @click="setFilter('price_range', '100-500')">¥100-500</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '500-99999'}" @click="setFilter('price_range', '500-99999')">¥500+</a>
            </div>
          </div>
          <div class="filter-section">
            <el-button type="primary" plain size="small" @click="resetFilters" style="width:100%">
              <el-icon><Refresh /></el-icon>
              {{ t('product.resetFilter') }}
            </el-button>
          </div>
        </aside>

        <!-- 右侧内容区 -->
        <div class="list-content">
          <!-- 排序&筛选栏 -->
          <div class="sort-filter-bar">
            <div class="sort-items">
              <span class="sort-item" :class="{active: sort === 'default'}" @click="setSort('default')">
                {{ t('product.comprehensive') }}
              </span>
              <span class="sort-item" :class="{active: sort === 'sales'}" @click="setSort('sales')">
                {{ t('product.sales') }}
                <el-icon v-if="sort === 'sales'" class="sort-arrow"><ArrowDown /></el-icon>
              </span>
              <span class="sort-item" :class="{active: sort === 'price_asc' || sort === 'price_desc'}" @click="togglePriceSort">
                {{ t('product.price') }}
                <el-icon class="sort-arrow">
                  <ArrowUp v-if="sort === 'price_asc'" />
                  <ArrowDown v-else />
                </el-icon>
              </span>
              <span class="sort-item" :class="{active: sort === 'new'}" @click="setSort('new')">
                {{ t('product.new') }}
              </span>
            </div>
            <div class="filter-actions">
              <div class="filter-btn" @click="showMobileFilter = true">
                <el-icon><Filter /></el-icon>
                <span>{{ t('product.filter') }}</span>
              </div>
            </div>
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
            <div class="product-card-shein" v-for="p in products" :key="p.id" @click="goDetail(p.id)">
              <!-- 图片区 3:4 -->
              <div class="product-image-wrap">
                <div class="product-image">
                  <img loading="lazy" :src="getProductImage(p)" :alt="p.name" @error="handleImgError($event, p)" />
                </div>
                <!-- 标签组 -->
                <div class="product-tags">
                  <span class="tag tag-hot" v-if="p.sales > 50">{{ t('product.hot') }}</span>
                  <span class="tag tag-new" v-if="isNewProduct(p)">{{ t('product.newArrival') }}</span>
                </div>
                <!-- 折扣角标 -->
                <div class="discount-badge" v-if="getDiscountPercent(p) > 0">
                  -{{ getDiscountPercent(p) }}%
                </div>
                <!-- 快速加购按钮 -->
                <div class="quick-add-btn" @click.stop="quickAddToCart(p)">
                  <el-icon><Plus /></el-icon>
                </div>
              </div>
              <!-- 信息区 -->
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
            <div class="empty-icon">
              <el-icon :size="64"><Goods /></el-icon>
            </div>
            <p class="empty-title">{{ t('product.noProducts') }}</p>
            <p class="empty-desc">换个筛选条件试试吧~</p>
            <el-button type="primary" @click="resetFilters">
              <el-icon><Refresh /></el-icon>
              {{ t('product.browseAll') }}
            </el-button>
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
import { ElMessage } from 'element-plus'
import { getProductList, getCategories } from '@/api/product'
import { useCartStore } from '@/stores/cart'
import PageHeader from '@/components/PageHeader.vue'
import {
  Grid, PriceTag, Refresh, Filter, Close, ArrowDown, ArrowUp,
  Goods, Plus, TrendCharts
} from '@element-plus/icons-vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

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

const currentCategoryName = computed(() => {
  const cat = categories.value.find(c => c.id == categoryId.value)
  return cat?.name || ''
})

const pageTitle = computed(() => {
  if (keyword.value) return t('product.search')
  if (categoryId.value) return currentCategoryName.value
  return t('product.allProducts')
})

const pageSubtitle = computed(() => {
  if (keyword.value) return `搜索"${keyword.value}"的结果`
  if (categoryId.value) return '精选好物 品质保障'
  return '全部商品 精选好物'
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
  if (!img) return '/placeholder.svg' + p.id
  if (img.startsWith('http')) return img
  return 'https://mall.tllos.com' + (img.startsWith('/') ? '' : '/') + img
}

const handleImgError = (event, p) => {
  event.target.src = '/placeholder.svg' + p.id
}

const isNewProduct = (p) => {
  if (!p.created_at) return false
  const created = new Date(p.created_at).getTime()
  return Date.now() - created < 7 * 24 * 60 * 60 * 1000
}

const getDiscountPercent = (p) => {
  if (!p.market_price || !p.price || p.market_price <= p.price) return 0
  return Math.round((1 - p.price / p.market_price) * 100)
}

const quickAddToCart = async (p) => {
  try {
    await cartStore.addToCart(p.id, 1)
    ElMessage.success('已加入购物车')
  } catch (e) {
    ElMessage.error('加入购物车失败')
  }
}

const goDetail = (id) => {
  if (id) router.push('/product/' + id)
}

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
/* ========== SHEIN风格商品列表页 ========== */
.product-list-page-shein {
  background: #f8fafc;
  min-height: calc(100vh - 200px);
  padding-bottom: 40px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* 运营banner条 */
.promo-banner {
  background: linear-gradient(135deg, #fff7ed, #ffedd5);
  border-radius: 12px;
  padding: 14px 20px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid #fed7aa;
}

.promo-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #f97316, #fb923c);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.promo-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.promo-title {
  font-size: 14px;
  font-weight: 700;
  color: #c2410c;
}

.promo-desc {
  font-size: 12px;
  color: #9a3412;
}

.promo-tag {
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 6px;
  letter-spacing: 1px;
}

/* 页面标题栏 */
.page-title-bar {
  background: #fff;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.title-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.title-breadcrumb {
  font-size: 13px;
  color: #64748b;
  background: #f1f5f9;
  padding: 4px 10px;
  border-radius: 6px;
}

.title-right {
  display: flex;
  align-items: center;
}

.total-count {
  font-size: 13px;
  color: #64748b;
}

.total-count b {
  color: #f97316;
  font-size: 18px;
  font-weight: 700;
}

/* 列表布局 */
.list-wrapper {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

/* 左侧筛选栏 */
.filter-sidebar {
  width: 220px;
  flex-shrink: 0;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  position: sticky;
  top: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
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
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.filter-options {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.filter-option {
  padding: 8px 10px;
  font-size: 13px;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-option:hover {
  background: #f8fafc;
  color: #f97316;
}

.filter-option.active {
  background: linear-gradient(135deg, #fff7ed, #ffedd5);
  color: #f97316;
  font-weight: 600;
}

/* 内容区 */
.list-content {
  flex: 1;
  min-width: 0;
}

/* 排序&筛选栏 */
.sort-filter-bar {
  background: #fff;
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.sort-items {
  display: flex;
  align-items: center;
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

.filter-actions {
  display: flex;
  align-items: center;
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
  padding: 4px 12px;
  background: linear-gradient(135deg, #fff7ed, #ffedd5);
  color: #f97316;
  font-size: 12px;
  border-radius: 20px;
  cursor: pointer;
  font-weight: 500;
}

/* 商品瀑布流 */
.product-grid {
  column-count: 4;
  column-gap: 16px;
  margin-bottom: 24px;
}

/* 商品卡片 SHEIN风格 - 瀑布流 */
.product-card-shein {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.25s, box-shadow 0.25s;
  position: relative;
  border: 1px solid #f1f5f9;
  break-inside: avoid;
  margin-bottom: 16px;
  display: inline-block;
  width: 100%;
}

.product-card-shein:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  z-index: 10;
}

/* 图片区 - 自适应高度（瀑布流关键） */
.product-image-wrap {
  position: relative;
  overflow: hidden;
}

.product-image {
  position: relative;
  width: 100%;
  background: #f8fafc;
  line-height: 0;
}

.product-image img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
  transition: transform 0.5s;
}

.product-card-shein:hover .product-image img {
  transform: scale(1.05);
}

/* 标签组 */
.product-tags {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 5;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tag {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  color: #fff;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.tag-hot {
  background: linear-gradient(135deg, #ff4757, #ff6b81);
}

.tag-new {
  background: linear-gradient(135deg, #2ed573, #7bed9f);
}

/* 折扣角标 */
.discount-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: linear-gradient(135deg, #ff4757, #ff6b81);
  color: #fff;
  font-size: 11px;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 8px;
  z-index: 5;
  box-shadow: 0 2px 8px rgba(255,71,87,0.4);
}

/* 快速加购按钮 */
.quick-add-btn {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #f97316, #fb923c);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 6;
  box-shadow: 0 2px 8px rgba(249,115,22,0.4);
  transition: transform 0.2s;
  opacity: 0;
}

.product-card-shein:hover .quick-add-btn {
  opacity: 1;
}

.quick-add-btn:active {
  transform: scale(0.9);
}

/* 信息区 */
.product-info {
  padding: 12px;
}

.product-name {
  font-size: 13px;
  color: #1e293b;
  line-height: 1.4;
  height: 36px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin-bottom: 8px;
  font-weight: 500;
  word-break: break-all;
}

.product-price-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 6px;
}

.product-price {
  font-size: 17px;
  color: #f97316;
  font-weight: 700;
}

.product-original {
  font-size: 12px;
  color: #94a3b8;
  text-decoration: line-through;
}

.product-meta {
  display: flex;
  align-items: center;
}

.product-sales {
  font-size: 11px;
  color: #94a3b8;
}

/* 骨架屏 */
.skeleton-card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}

.skeleton-image {
  width: 100%;
  padding-top: 133.33%;
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

/* ========== 平板适配 ========== */
@media (max-width: 1024px) and (min-width: 769px) {
  .product-grid {
    column-count: 3;
    column-gap: 12px;
  }
  .product-card-shein {
    margin-bottom: 12px;
  }
}

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .product-list-page-shein {
    padding-bottom: 20px;
    min-height: calc(100vh - 120px);
  }

  .container {
    max-width: 100%;
    padding: 0 12px;
  }

  /* 运营banner */
  .promo-banner {
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 10px;
  }

  .promo-icon {
    width: 32px;
    height: 32px;
    font-size: 16px;
  }

  .promo-title {
    font-size: 13px;
  }

  .promo-desc {
    font-size: 11px;
  }

  .promo-tag {
    font-size: 10px;
    padding: 3px 8px;
  }

  /* 页面标题栏 */
  .page-title-bar {
    padding: 12px 14px;
    border-radius: 8px;
    margin-bottom: 10px;
  }

  .page-title {
    font-size: 16px;
  }

  .total-count {
    font-size: 12px;
  }

  .total-count b {
    font-size: 15px;
  }

  /* 列表布局 */
  .list-wrapper {
    flex-direction: column;
    gap: 0;
  }

  .filter-sidebar {
    display: none;
  }

  /* 排序筛选栏 */
  .sort-filter-bar {
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 12px;
  }

  .sort-items {
    gap: 4px;
    overflow-x: auto;
  }

  .sort-item {
    font-size: 12px;
    padding: 5px 10px;
    white-space: nowrap;
  }

  .filter-btn {
    padding: 5px 10px;
    font-size: 12px;
  }

  .filter-btn span {
    display: none;
  }

  /* 商品瀑布流 - 移动端2列 */
  .product-grid {
    column-count: 2;
    column-gap: 10px;
    margin-bottom: 16px;
  }

  .product-card-shein {
    margin-bottom: 10px;
  }

  .product-card-shein {
    border-radius: 8px;
  }

  .product-card-shein:hover {
    transform: none;
    box-shadow: none;
  }

  /* 快速加购按钮移动端始终显示 */
  .quick-add-btn {
    opacity: 1;
    width: 28px;
    height: 28px;
  }

  .product-info {
    padding: 8px 10px 10px;
  }

  .product-name {
    font-size: 12px;
    height: 34px;
    margin-bottom: 6px;
  }

  .product-price {
    font-size: 15px;
  }

  .product-original {
    font-size: 11px;
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
  .pagination-wrapper {
    margin-top: 16px;
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
