<template>
  <div class="new-arrivals-page-shein">
    <!-- 门楣 -->
    <PageHeader title="新品上架" subtitle="发现最新潮流 抢先体验" />

    <div class="container">
      <!-- 面包屑 -->
      <div class="breadcrumb">
        <a href="javascript:;" @click="goHome">首页</a>
        <span class="sep">/</span>
        <span class="current">新品上架</span>
      </div>

      <!-- 运营卡片【本周上新】 -->
      <div class="promo-banner">
        <div class="promo-icon">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="promo-text">
          <span class="promo-title">本周上新</span>
          <span class="promo-desc">7天新品优先发货 · 品质保障 · 限时特惠</span>
        </div>
        <div class="promo-tag">NEW</div>
      </div>

      <!-- 标题栏 -->
      <div class="page-title-bar">
        <div class="title-left">
          <h2 class="page-title">新品上架</h2>
          <span class="title-badge">最新</span>
        </div>
        <div class="title-right">
          <span class="total-count">共 <b>{{ total }}</b> 件商品</span>
        </div>
      </div>

      <!-- 排序&筛选栏 -->
      <div class="sort-filter-bar">
        <div class="sort-items">
          <span class="sort-item" :class="{active: sort === 'new'}" @click="setSort('new')">
            最新
          </span>
          <span class="sort-item" :class="{active: sort === 'sales'}" @click="setSort('sales')">
            销量
            <el-icon v-if="sort === 'sales'" class="sort-arrow"><ArrowDown /></el-icon>
          </span>
          <span class="sort-item" :class="{active: sort === 'price_asc' || sort === 'price_desc'}" @click="togglePriceSort">
            价格
            <el-icon class="sort-arrow">
              <ArrowUp v-if="sort === 'price_asc'" />
              <ArrowDown v-else />
            </el-icon>
          </span>
          <span class="sort-item" :class="{active: sort === 'default'}" @click="setSort('default')">
            综合
          </span>
        </div>
        <div class="filter-actions">
          <div class="filter-btn" @click="showFilter = true">
            <el-icon><Filter /></el-icon>
            <span>筛选</span>
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
              <span class="tag tag-new">新品</span>
              <span class="tag tag-hot" v-if="p.sales > 50">热销</span>
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
              <span class="product-sales">已售{{ p.sales || 0 }}</span>
              <span class="product-new-time" v-if="p.created_at">上架{{ getDaysAgo(p.created_at) }}天</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 骨架屏 -->
      <div class="product-grid" v-if="loading">
        <div class="skeleton-card" v-for="i in 12" :key="i">
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
        <p class="empty-title">暂无新品</p>
        <p class="empty-desc">更多新品正在路上，敬请期待~</p>
        <el-button type="primary" @click="goHome">
          <el-icon><House /></el-icon>
          去首页逛逛
        </el-button>
      </div>

      <!-- 分页 -->
      <div class="pagination-wrapper" v-if="!loading && products.length">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="limit"
          :total="total"
          :page-sizes="[12, 24, 48]"
          layout="total, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>

      <!-- 轻量通用页脚 -->
      <div class="mini-footer">
        <div class="footer-links">
          <a href="javascript:;">关于我们</a>
          <span class="sep">|</span>
          <a href="javascript:;">帮助中心</a>
          <span class="sep">|</span>
          <a href="javascript:;">用户协议</a>
          <span class="sep">|</span>
          <a href="javascript:;">隐私政策</a>
        </div>
        <p class="footer-copyright">© 2026 TLLOS商城 版权所有 | 品质保障 · 极速发货 · 售后无忧</p>
      </div>
    </div>

    <!-- 筛选侧边栏 -->
    <el-drawer v-model="showFilter" title="筛选" direction="rtl" size="320px" class="filter-drawer">
      <div class="filter-content">
        <div class="filter-section">
          <h4>商品分类</h4>
          <div class="filter-tags">
            <span class="filter-tag" :class="{ active: !categoryId }" @click="setFilter('category_id', '')">全部</span>
            <span class="filter-tag" v-for="cat in categories" :key="cat.id" :class="{ active: categoryId == cat.id }" @click="setFilter('category_id', cat.id)">{{ cat.name }}</span>
          </div>
        </div>
        <div class="filter-section">
          <h4>价格区间</h4>
          <div class="price-range">
            <el-input v-model="priceMin" placeholder="最低价" type="number" />
            <span class="range-sep">-</span>
            <el-input v-model="priceMax" placeholder="最高价" type="number" />
          </div>
        </div>
        <div class="filter-section">
          <h4>排序方式</h4>
          <div class="filter-tags">
            <span class="filter-tag" :class="{ active: sort === 'new' }" @click="setSort('new')">最新上架</span>
            <span class="filter-tag" :class="{ active: sort === 'sales' }" @click="setSort('sales')">销量优先</span>
            <span class="filter-tag" :class="{ active: sort === 'price_asc' }" @click="setSort('price_asc')">价格从低到高</span>
            <span class="filter-tag" :class="{ active: sort === 'price_desc' }" @click="setSort('price_desc')">价格从高到低</span>
            <span class="filter-tag" :class="{ active: sort === 'default' }" @click="setSort('default')">综合</span>
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
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getProductList, getCategories } from '@/api/product'
import { useCartStore } from '@/stores/cart'
import PageHeader from '@/components/PageHeader.vue'
import {
  TrendCharts, ArrowDown, ArrowUp, Filter, Close, Goods, Plus, House
} from '@element-plus/icons-vue'

const router = useRouter()
const cartStore = useCartStore()

const products = ref([])
const categories = ref([])
const total = ref(0)
const loading = ref(false)
const showFilter = ref(false)

const page = ref(1)
const limit = ref(12)
const sort = ref('new')
const categoryId = ref('')
const priceMin = ref('')
const priceMax = ref('')

const activeFilters = computed(() => {
  const list = []
  if (categoryId.value) {
    const cat = categories.value.find(c => c.id == categoryId.value)
    list.push({ key: 'category_id', label: `分类：${cat?.name || ''}` })
  }
  if (priceMin.value || priceMax.value) {
    list.push({ key: 'price', label: `价格：¥${priceMin.value || 0}-¥${priceMax.value || '不限'}` })
  }
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

const getDiscountPercent = (p) => {
  if (!p.market_price || !p.price || p.market_price <= p.price) return 0
  return Math.round((1 - p.price / p.market_price) * 100)
}

const getDaysAgo = (dateStr) => {
  if (!dateStr) return ''
  const created = new Date(dateStr).getTime()
  const days = Math.floor((Date.now() - created) / (24 * 60 * 60 * 1000))
  return days
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

const goHome = () => router.push('/')

const fetchCategories = async () => {
  try {
    const res = await getCategories()
    categories.value = res.data?.list || res.data || []
  } catch (e) {
    console.error(e)
  }
}

const fetchProducts = async () => {
  loading.value = true
  try {
    const params = {
      page: page.value,
      limit: limit.value,
      sort: sort.value
    }
    if (categoryId.value) params.category_id = categoryId.value
    if (priceMin.value) params.min_price = priceMin.value
    if (priceMax.value) params.max_price = priceMax.value

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
  if (key === 'price') {
    priceMin.value = ''
    priceMax.value = ''
  }
  page.value = 1
  fetchProducts()
}

const setSort = (s) => {
  sort.value = s
  page.value = 1
  fetchProducts()
}

const togglePriceSort = () => {
  if (sort.value === 'price_asc') sort.value = 'price_desc'
  else if (sort.value === 'price_desc') sort.value = 'new'
  else sort.value = 'price_asc'
  page.value = 1
  fetchProducts()
}

const resetFilter = () => {
  categoryId.value = ''
  priceMin.value = ''
  priceMax.value = ''
  sort.value = 'new'
}

const applyFilter = () => {
  showFilter.value = false
  page.value = 1
  fetchProducts()
}

const handlePageChange = (p) => {
  page.value = p
  fetchProducts()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleSizeChange = (s) => {
  limit.value = s
  page.value = 1
  fetchProducts()
}

onMounted(() => {
  fetchCategories()
  fetchProducts()
})
</script>

<style scoped>
/* ========== SHEIN风格新品上架页 ========== */
.new-arrivals-page-shein {
  background: #f8fafc;
  min-height: calc(100vh - 200px);
  padding-bottom: 40px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* 面包屑 */
.breadcrumb {
  padding: 12px 0;
  font-size: 13px;
  color: #64748b;
}

.breadcrumb a {
  color: #64748b;
  transition: color 0.2s;
}

.breadcrumb a:hover {
  color: #f97316;
}

.breadcrumb .sep {
  margin: 0 8px;
  color: #cbd5e1;
}

.breadcrumb .current {
  color: #1e293b;
  font-weight: 500;
}

/* 运营卡片【本周上新】 */
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
  gap: 10px;
}

.page-title {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.title-badge {
  background: linear-gradient(135deg, #2ed573, #7bed9f);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
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

/* 商品网格 */
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

/* 商品卡片 SHEIN风格 */
.product-card-shein {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.25s, box-shadow 0.25s;
  position: relative;
  border: 1px solid #f1f5f9;
}

.product-card-shein:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  z-index: 10;
}

/* 图片区 3:4 */
.product-image-wrap {
  position: relative;
  overflow: hidden;
}

.product-image {
  position: relative;
  width: 100%;
  padding-top: 133.33%;
  background: #f8fafc;
}

.product-image img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
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
  justify-content: space-between;
  align-items: center;
}

.product-sales {
  font-size: 11px;
  color: #94a3b8;
}

.product-new-time {
  font-size: 10px;
  color: #2ed573;
  background: #f0fdf4;
  padding: 2px 6px;
  border-radius: 4px;
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

/* 轻量通用页脚 */
.mini-footer {
  margin-top: 40px;
  padding: 24px 0;
  border-top: 1px solid #e2e8f0;
  text-align: center;
}

.footer-links {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0;
  margin-bottom: 12px;
}

.footer-links a {
  font-size: 13px;
  color: #64748b;
  transition: color 0.2s;
}

.footer-links a:hover {
  color: #f97316;
}

.footer-links .sep {
  margin: 0 12px;
  color: #cbd5e1;
}

.footer-copyright {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
}

/* 筛选抽屉 */
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

.filter-tags .filter-tag {
  padding: 6px 14px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-tags .filter-tag:hover {
  background: #e2e8f0;
}

.filter-tags .filter-tag.active {
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
  .new-arrivals-page-shein {
    padding-bottom: 20px;
    min-height: calc(100vh - 120px);
  }

  .container {
    max-width: 100%;
    padding: 0 12px;
  }

  /* 面包屑 */
  .breadcrumb {
    padding: 8px 0;
    font-size: 12px;
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

  /* 商品网格 */
  .product-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
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

  /* 页脚 */
  .mini-footer {
    margin-top: 24px;
    padding: 16px 0;
  }

  .footer-links {
    flex-wrap: wrap;
    gap: 8px;
  }

  .footer-links a {
    font-size: 12px;
  }

  .footer-links .sep {
    display: none;
  }

  .footer-copyright {
    font-size: 11px;
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
