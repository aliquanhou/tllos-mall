<template>
  <div class="product-list-page">
    <div class="container">
      <!-- 面包屑 -->
      <div class="breadcrumb">
        <a href="javascript:;" @click="goHome">首页</a>
        <span class="sep">/</span>
        <span class="current">{{ currentCategoryName || '全部商品' }}</span>
        <span class="sep" v-if="keyword">/</span>
        <span class="current" v-if="keyword">搜索：{{ keyword }}</span>
      </div>

      <div class="list-wrapper">
        <!-- 左侧筛选栏 -->
        <aside class="filter-sidebar">
          <div class="filter-section">
            <h4 class="filter-title">商品分类</h4>
            <div class="filter-options">
              <a href="javascript:;" class="filter-option" :class="{active: !categoryId}" @click="setFilter('category_id', '')">全部</a>
              <a href="javascript:;" class="filter-option" v-for="cat in categories" :key="cat.id" :class="{active: categoryId == cat.id}" @click="setFilter('category_id', cat.id)">{{ cat.name }}</a>
            </div>
          </div>
          <div class="filter-section">
            <h4 class="filter-title">价格区间</h4>
            <div class="filter-options">
              <a href="javascript:;" class="filter-option" :class="{active: !priceRange}" @click="setFilter('price_range', '')">全部</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '0-50'}" @click="setFilter('price_range', '0-50')">¥0-50</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '50-100'}" @click="setFilter('price_range', '50-100')">¥50-100</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '100-500'}" @click="setFilter('price_range', '100-500')">¥100-500</a>
              <a href="javascript:;" class="filter-option" :class="{active: priceRange === '500-99999'}" @click="setFilter('price_range', '500-99999')">¥500以上</a>
            </div>
          </div>
          <div class="filter-section">
            <h4 class="filter-title">商品状态</h4>
            <div class="filter-options">
              <a href="javascript:;" class="filter-option" :class="{active: !status}" @click="setFilter('status', '')">全部</a>
              <a href="javascript:;" class="filter-option" :class="{active: status == 1}" @click="setFilter('status', 1)">在售</a>
              <a href="javascript:;" class="filter-option" :class="{active: status == 0}" @click="setFilter('status', 0)">下架</a>
            </div>
          </div>
          <div class="filter-section">
            <el-button type="primary" plain size="small" @click="resetFilters" style="width:100%">重置筛选</el-button>
          </div>
        </aside>

        <!-- 右侧内容区 -->
        <div class="list-content">
          <!-- 排序栏 -->
          <div class="sort-bar">
            <div class="sort-left">
              <span class="sort-item" :class="{active: sort === 'default'}" @click="setSort('default')">综合</span>
              <span class="sort-item" :class="{active: sort === 'sales'}" @click="setSort('sales')">销量</span>
              <span class="sort-item" :class="{active: sort === 'price_asc' || sort === 'price_desc'}" @click="togglePriceSort">
                价格
                <el-icon v-if="sort === 'price_asc'"><SortUp /></el-icon>
                <el-icon v-else-if="sort === 'price_desc'"><SortDown /></el-icon>
                <el-icon v-else><Sort /></el-icon>
              </span>
              <span class="sort-item" :class="{active: sort === 'new'}" @click="setSort('new')">新品</span>
            </div>
            <div class="sort-right">
              <span class="result-count">共 <b>{{ total }}</b> 件商品</span>
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
            <ProductCard v-for="p in products" :key="p.id" :product="p" />
          </div>

          <!-- 骨架屏 -->
          <Skeleton v-if="loading" type="product-grid" :count="10" />

          <!-- 空状态 -->
          <div class="empty-state" v-if="!loading && !products.length">
            <el-icon size="64" color="#ddd"><Goods /></el-icon>
            <p>暂无符合条件的商品</p>
            <el-button type="primary" @click="resetFilters">重置筛选条件</el-button>
          </div>

          <!-- 分页 -->
          <div class="pagination-wrap" v-if="!loading && total > limit">
            <el-pagination
              v-model:current-page="page"
              v-model:page-size="limit"
              :total="total"
              :page-sizes="[20, 40, 60]"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handlePageChange"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getProductList } from '@/api/product'
import { getCategories } from '@/api/product'
import ProductCard from '@/components/ProductCard.vue'
import Skeleton from '@/components/Skeleton.vue'

const route = useRoute()
const router = useRouter()

const products = ref([])
const categories = ref([])
const total = ref(0)
const loading = ref(false)

// 从URL读取状态
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
  if (categoryId.value) list.push({ key: 'category_id', label: `分类：${currentCategoryName.value}` })
  if (priceRange.value) list.push({ key: 'price_range', label: `价格：¥${priceRange.value.replace('-', '-¥')}` })
  if (status.value !== '') list.push({ key: 'status', label: `状态：${status.value == 1 ? '在售' : '下架'}` })
  if (keyword.value) list.push({ key: 'keyword', label: `搜索：${keyword.value}` })
  return list
})

// 同步状态到URL
const syncToUrl = () => {
  const query = {}
  if (keyword.value) query.keyword = keyword.value
  if (categoryId.value) query.category_id = categoryId.value
  if (sort.value !== 'default') query.sort = sort.value
  if (page.value > 1) query.page = page.value
  if (limit.value !== 20) query.limit = limit.value
  if (priceRange.value) query.price_range = priceRange.value
  if (status.value !== '') query.status = status.value
  router.replace({ path: '/products', query })
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
    if (status.value !== '') params.status = status.value
    const res = await getProductList(params)
    products.value = res.data?.list || res.data?.data || []
    total.value = res.data?.total || 0
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const fetchCategories = async () => {
  try {
    const res = await getCategories()
    categories.value = res.data?.list || res.data || []
  } catch (e) { console.error(e) }
}

const setFilter = (key, value) => {
  if (key === 'category_id') categoryId.value = value
  if (key === 'price_range') priceRange.value = value
  if (key === 'status') status.value = value
  if (key === 'keyword') keyword.value = value
  page.value = 1
  syncToUrl()
  fetchList()
}

const setSort = s => { sort.value = s; page.value = 1; syncToUrl(); fetchList() }
const togglePriceSort = () => {
  if (sort.value === 'price_asc') setSort('price_desc')
  else setSort('price_asc')
}
const handlePageChange = p => { page.value = p; syncToUrl(); fetchList() }
const handleSizeChange = s => { limit.value = s; page.value = 1; syncToUrl(); fetchList() }
const resetFilters = () => {
  keyword.value = ''; categoryId.value = ''; sort.value = 'default'
  page.value = 1; limit.value = 20; priceRange.value = ''; status.value = ''
  router.replace({ path: '/products' })
  fetchList()
}
const goHome = () => router.push('/home')

// 监听URL变化（浏览器前进/后退）
watch(() => route.query, (q) => {
  keyword.value = q.keyword || ''
  categoryId.value = q.category_id || ''
  sort.value = q.sort || 'default'
  page.value = parseInt(q.page) || 1
  limit.value = parseInt(q.limit) || 20
  priceRange.value = q.price_range || ''
  status.value = q.status || ''
  fetchList()
})

onMounted(() => { fetchCategories(); fetchList() })
</script>

<style scoped>
.product-list-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.breadcrumb { font-size: 13px; color: #999; margin-bottom: 16px; }
.breadcrumb a { color: #666; text-decoration: none; }
.breadcrumb a:hover { color: #e6a23c; }
.breadcrumb .sep { margin: 0 8px; }
.breadcrumb .current { color: #333; }
.list-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.filter-sidebar { width: 200px; flex-shrink: 0; background: #fff; border-radius: 8px; padding: 16px; position: sticky; top: 20px; }
.filter-section { margin-bottom: 20px; }
.filter-section:last-child { margin-bottom: 0; }
.filter-title { font-size: 14px; color: #333; font-weight: bold; margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
.filter-options { display: flex; flex-direction: column; gap: 4px; }
.filter-option { font-size: 13px; color: #666; padding: 6px 8px; border-radius: 4px; text-decoration: none; transition: all 0.2s; }
.filter-option:hover { color: #e6a23c; background: #fdf6ec; }
.filter-option.active { color: #e6a23c; background: #fdf6ec; font-weight: bold; }
.list-content { flex: 1; min-width: 0; }
.sort-bar { background: #fff; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.sort-left { display: flex; gap: 24px; }
.sort-item { font-size: 14px; color: #666; cursor: pointer; display: flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; }
.sort-item:hover { color: #e6a23c; }
.sort-item.active { color: #e6a23c; font-weight: bold; background: #fdf6ec; }
.result-count { font-size: 13px; color: #999; }
.result-count b { color: #e6a23c; font-size: 16px; }
.active-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.filter-tag { background: #fff; border: 1px solid #e6a23c; color: #e6a23c; padding: 4px 10px; border-radius: 4px; font-size: 12px; display: flex; align-items: center; gap: 6px; }
.filter-tag .el-icon { cursor: pointer; }
.product-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
.empty-state { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-state p { color: #999; margin: 16px 0; }
.pagination-wrap { background: #fff; border-radius: 8px; padding: 16px; display: flex; justify-content: center; }
</style>
