<template>
  <div class="search-page">
    <div class="container">
      <!-- 搜索栏 -->
      <div class="search-bar">
        <el-input v-model="keyword" size="large" placeholder="搜索商品" @keyup.enter="doSearch">
          <template #prefix><el-icon><Search /></el-icon></template>
          <template #append><el-button type="primary" @click="doSearch">搜索</el-button></template>
        </el-input>
      </div>
      <!-- 搜索结果 -->
      <div class="search-result" v-if="searched">
        <div class="result-header">
          <span class="result-count">找到 <b>{{ total }}</b> 件与 "<b>{{ keyword }}</b>" 相关的商品</span>
          <div class="sort-options">
            <span class="sort-item" :class="{active: sort === 'default'}" @click="setSort('default')">综合</span>
            <span class="sort-item" :class="{active: sort === 'sales'}" @click="setSort('sales')">销量</span>
            <span class="sort-item" :class="{active: sort === 'price_asc' || sort === 'price_desc'}" @click="togglePriceSort">价格</span>
          </div>
        </div>
        <div class="product-grid" v-if="products.length">
          <ProductCard v-for="p in products" :key="p.id" :product="p" />
        </div>
        <div class="empty-result" v-else>
          <el-icon size="64" color="#ddd"><Search /></el-icon>
          <p>未找到相关商品</p>
          <el-button type="primary" @click="$router.push('/products')">查看全部商品</el-button>
        </div>
        <div class="pagination-wrap" v-if="total > limit">
          <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" layout="prev, pager, next, jumper" @current-change="fetchProducts" />
        </div>
      </div>
      <!-- 搜索建议 -->
      <div class="search-suggest" v-else>
        <h3>热门搜索</h3>
        <div class="hot-tags">
          <el-tag v-for="tag in hotTags" :key="tag" size="large" class="hot-tag" @click="keyword = tag; doSearch()">{{ tag }}</el-tag>
        </div>
        <h3>搜索历史</h3>
        <div class="history-tags" v-if="history.length">
          <el-tag v-for="(h, idx) in history" :key="idx" class="history-tag" @click="keyword = h; doSearch()">{{ h }}</el-tag>
          <el-button link type="danger" @click="clearHistory">清空历史</el-button>
        </div>
        <p v-else class="no-history">暂无搜索历史</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getProductList } from '@/api/product'
import ProductCard from '@/components/ProductCard.vue'
const route = useRoute()
const keyword = ref(route.query.keyword || '')
const products = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const sort = ref('default')
const searched = ref(false)
const history = ref(JSON.parse(localStorage.getItem('search_history') || '[]'))
const hotTags = ['男装', '女装', '手机', '零食', '美妆', '家居', '数码', '鞋靴']
const doSearch = () => {
  if (!keyword.value.trim()) return
  if (!history.value.includes(keyword.value)) {
    history.value.unshift(keyword.value)
    if (history.value.length > 10) history.value.pop()
    localStorage.setItem('search_history', JSON.stringify(history.value))
  }
  page.value = 1
  searched.value = true
  fetchProducts()
}
const fetchProducts = async () => {
  try {
    const res = await getProductList({ keyword: keyword.value, page: page.value, limit: limit.value, sort: sort.value })
    products.value = res.data?.list || res.data || []
    total.value = res.data?.total || 0
  } catch (e) { console.error(e) }
}
const setSort = (s) => { sort.value = s; page.value = 1; fetchProducts() }
const togglePriceSort = () => { sort.value = sort.value === 'price_asc' ? 'price_desc' : 'price_asc'; page.value = 1; fetchProducts() }
const clearHistory = () => { history.value = []; localStorage.removeItem('search_history') }
onMounted(() => { if (keyword.value) doSearch() })
</script>
<style scoped>
.search-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.search-bar { max-width: 600px; margin: 0 auto 30px; }
.search-result { }
.result-header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 12px 20px; border-radius: 8px; margin-bottom: 16px; }
.result-count { font-size: 14px; color: #666; }
.result-count b { color: #e6a23c; }
.sort-options { display: flex; gap: 20px; }
.sort-item { font-size: 14px; color: #666; cursor: pointer; padding: 4px 8px; border-radius: 4px; }
.sort-item:hover { color: #e6a23c; }
.sort-item.active { color: #e6a23c; background: #fdf6ec; font-weight: bold; }
.product-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
.empty-result { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-result p { color: #999; margin: 16px 0; }
.pagination-wrap { display: flex; justify-content: center; }
.search-suggest { background: #fff; border-radius: 8px; padding: 30px; }
.search-suggest h3 { font-size: 16px; color: #333; margin: 0 0 16px 0; }
.hot-tags { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
.hot-tag { cursor: pointer; }
.hot-tag:hover { background: #fdf6ec; border-color: #e6a23c; color: #e6a23c; }
.history-tags { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.history-tag { cursor: pointer; background: #f5f5f5; border-color: #eee; color: #666; }
.no-history { color: #999; font-size: 14px; }
</style>
