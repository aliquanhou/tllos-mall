<template>
  <div class="my-reviews-page">
    <PageHeader title="我的评价" subtitle="分享购物体验 帮助更多买家" />
    <div class="container">
      <div class="reviews-wrapper">
        <!-- 评价统计 -->
        <div class="reviews-stats">
          <div class="stat-item">
            <span class="stat-number">{{ totalCount }}</span>
            <span class="stat-label">全部评价</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">{{ goodCount }}</span>
            <span class="stat-label">好评</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">{{ midCount }}</span>
            <span class="stat-label">中评</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">{{ badCount }}</span>
            <span class="stat-label">差评</span>
          </div>
        </div>

        <!-- 筛选标签 -->
        <div class="filter-tabs">
          <div class="tab-item" :class="{active: activeTab === 'all'}" @click="switchTab('all')">全部</div>
          <div class="tab-item" :class="{active: activeTab === 'good'}" @click="switchTab('good')">好评</div>
          <div class="tab-item" :class="{active: activeTab === 'mid'}" @click="switchTab('mid')">中评</div>
          <div class="tab-item" :class="{active: activeTab === 'bad'}" @click="switchTab('bad')">差评</div>
        </div>

        <!-- 评价列表 -->
        <div class="review-list" v-if="filteredReviews.length > 0">
          <div class="review-card" v-for="review in filteredReviews" :key="review.id">
            <div class="review-product">
              <img loading="lazy" :src="getImageUrl(review.product_image || review.main_image)" :alt="review.product_name" class="product-img" @error="handleImgError" />
              <div class="product-info">
                <div class="product-name">{{ review.product_name }}</div>
                <div class="review-time">{{ formatTime(review.created_at) }}</div>
              </div>
              <el-rate :model-value="Number(review.rating || 5)" disabled size="small" />
            </div>
            <div class="review-content">{{ review.content }}</div>
            <div class="review-images" v-if="review.images && review.images.length">
              <img loading="lazy" v-for="(img, idx) in review.images" :key="idx" :src="getImageUrl(img)" class="review-img" />
            </div>
            <div class="merchant-reply" v-if="review.reply">
              <div class="reply-label">商家回复：</div>
              <div class="reply-text">{{ review.reply }}</div>
            </div>
            <div class="review-actions">
              <el-button type="primary" link @click="goProduct(review.product_id)">查看商品</el-button>
              <el-button type="primary" link @click="goOrder(review.order_id)" v-if="review.order_id">查看订单</el-button>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div class="empty-state" v-else-if="!loading">
          <el-icon :size="64" color="#ddd"><ChatDotRound /></el-icon>
          <p class="empty-text">暂无评价记录</p>
          <el-button type="primary" @click="$router.push('/orders')">去评价订单</el-button>
        </div>

        <!-- 加载中 -->
        <div class="loading-state" v-if="loading">
          <el-icon class="is-loading" :size="24"><Loading /></el-icon>
          <span>加载中...</span>
        </div>

        <!-- 加载更多 -->
        <div class="load-more" v-if="hasMore && !loading && filteredReviews.length > 0">
          <el-button type="primary" plain @click="loadMore">加载更多</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import PageHeader from "@/components/PageHeader.vue"
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ChatDotRound, Loading } from '@element-plus/icons-vue'
import { getMyComments } from '@/api/review'

const router = useRouter()
const reviews = ref([])
const loading = ref(false)
const activeTab = ref('all')
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)

const filteredReviews = computed(() => {
  if (activeTab.value === 'all') return reviews.value
  if (activeTab.value === 'good') return reviews.value.filter(r => r.rating >= 4)
  if (activeTab.value === 'mid') return reviews.value.filter(r => r.rating === 3)
  if (activeTab.value === 'bad') return reviews.value.filter(r => r.rating <= 2)
  return reviews.value
})

const totalCount = computed(() => reviews.value.length)
const goodCount = computed(() => reviews.value.filter(r => r.rating >= 4).length)
const midCount = computed(() => reviews.value.filter(r => r.rating === 3).length)
const badCount = computed(() => reviews.value.filter(r => r.rating <= 2).length)

const getImageUrl = (url) => {
  if (!url) return '/assets/placeholder.jpg' + Date.now()
  if (url.startsWith('http')) return url
  return 'https://mall.tllos.com' + (url.startsWith('/') ? '' : '/') + url
}

const handleImgError = (event) => {
  event.target.src = '/assets/placeholder.jpg' + Date.now()
}

const formatTime = (time) => {
  if (!time) return ''
  return time.substring(0, 10)
}

const fetchReviews = async (pageNum = 1) => {
  loading.value = true
  try {
    const res = await getMyComments({ page: pageNum, limit: pageSize })
    const data = res.data || res
    const list = data.list || data || []
    if (pageNum === 1) {
      reviews.value = list
    } else {
      reviews.value = [...reviews.value, ...list]
    }
    hasMore.value = list.length >= pageSize
    page.value = pageNum
  } catch (e) {
    console.error('获取我的评价失败:', e)
  } finally {
    loading.value = false
  }
}

const loadMore = () => {
  fetchReviews(page.value + 1)
}

const switchTab = (tab) => {
  activeTab.value = tab
}

const goProduct = (productId) => {
  router.push(`/product/${productId}`)
}

const goOrder = (orderId) => {
  router.push(`/order/${orderId}`)
}

onMounted(() => {
  fetchReviews(1)
})
</script>

<style scoped>
.my-reviews-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
.reviews-wrapper { background: #fff; border-radius: 12px; padding: 24px; }
.reviews-stats { display: flex; gap: 40px; padding: 20px; background: #f9f9f9; border-radius: 8px; margin-bottom: 20px; }
.stat-item { text-align: center; flex: 1; }
.stat-number { display: block; font-size: 28px; font-weight: bold; color: #333; }
.stat-label { font-size: 13px; color: #999; }
.filter-tabs { display: flex; gap: 24px; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 12px; }
.tab-item { font-size: 15px; color: #666; cursor: pointer; padding: 4px 0; position: relative; transition: color 0.2s; }
.tab-item:hover { color: #e6a23c; }
.tab-item.active { color: #e6a23c; font-weight: 500; }
.tab-item.active::after { content: ''; position: absolute; bottom: -13px; left: 0; right: 0; height: 2px; background: #e6a23c; }
.review-list { }
.review-card { padding: 20px 0; border-bottom: 1px solid #f0f0f0; }
.review-card:last-child { border-bottom: none; }
.review-product { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.product-img { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; }
.product-info { flex: 1; }
.product-name { font-size: 14px; color: #333; margin-bottom: 4px; }
.review-time { font-size: 12px; color: #999; }
.review-content { font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 12px; }
.review-images { display: flex; gap: 8px; margin-bottom: 12px; }
.review-img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; }
.merchant-reply { background: #f9f9f9; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
.reply-label { font-size: 12px; color: #e6a23c; font-weight: 500; margin-bottom: 6px; }
.reply-text { font-size: 13px; color: #666; line-height: 1.5; }
.review-actions { display: flex; gap: 16px; }
.empty-state { text-align: center; padding: 60px 20px; }
.empty-text { color: #999; margin: 16px 0 24px; }
.loading-state { text-align: center; padding: 40px; color: #999; display: flex; align-items: center; justify-content: center; gap: 8px; }
.load-more { text-align: center; padding: 20px 0; }
@media (max-width: 768px) {
  .reviews-stats { gap: 16px; padding: 16px; }
  .stat-number { font-size: 22px; }
  .filter-tabs { gap: 16px; }
}
</style>
