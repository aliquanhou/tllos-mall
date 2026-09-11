<template>
  <div class="product-detail-page">
    <PageHeader title="商品详情" subtitle="品质好物 优选生活" />
    <div class="container">
      <!-- 面包屑 -->
      <div class="breadcrumb">
        <a href="javascript:;" @click="$router.push('/home')">{{ t('product.home') }}</a>
        <span class="sep">/</span>
        <a href="javascript:;" @click="$router.push('/products')">{{ t('product.allProducts') }}</a>
        <span class="sep">/</span>
        <span class="current">{{ product?.name || t('product.detail') }}</span>
      </div>

      <!-- 加载中 -->
      <div v-if="loading" class="detail-loading">
        <div class="loading-skeleton">
          <div class="skeleton-image-lg"></div>
          <div class="skeleton-info">
            <div class="skeleton-line" style="width: 80%"></div>
            <div class="skeleton-line" style="width: 60%"></div>
            <div class="skeleton-line" style="width: 50%"></div>
          </div>
        </div>
      </div>

      <div v-else-if="product" class="detail-wrapper">
        <!-- 左侧图片区 -->
        <div class="detail-images">
          <div class="main-image-wrap">
            <div class="main-image shein-main-image" @mousemove="handleZoom" @mouseleave="zoomShow=false" @mouseenter="zoomShow=true">
              <video v-if="currentMedia.type === 'video'" :src="currentMedia.url" :poster="product.video_poster || ''" controls class="main-video" @mouseenter.stop @mousemove.stop></video>
              <img loading="lazy" v-else :src="getImageUrl(currentMedia.url)" :alt="product.name" ref="mainImgRef" @error="handleMainImgError" />
              <div class="zoom-lens" v-if="zoomShow && currentMedia.type === 'image'" :style="lensStyle"></div>
              <!-- 图片数量指示器 -->
              <div class="image-counter" v-if="allMedia.length > 1">
                <span>{{ currentMediaIndex + 1 }}/{{ allMedia.length }}</span>
              </div>
            </div>
            <div class="zoom-result" v-if="zoomShow && currentMedia.type === 'image'" :style="resultStyle">
              <img loading="lazy" :src="getImageUrl(currentMedia.url)" :style="zoomImgStyle" />
            </div>
          </div>
          <div class="thumb-list shein-thumb-list">
            <div class="thumb-item" v-for="(media, idx) in allMedia" :key="idx" :class="{active: currentMediaIndex === idx}" @click="currentMediaIndex = idx">
              <img loading="lazy" v-if="media.type === 'image'" :src="getImageUrl(media.url)" :alt="'缩略图'+(idx+1)" />
              <div v-else class="thumb-video">
                <img loading="lazy" :src="getImageUrl(product.video_poster || media.url)" alt="视频缩略图" />
                <div class="play-icon"><el-icon :size="20"><VideoPlay /></el-icon></div>
              </div>
            </div>
          </div>
        </div>

        <!-- 右侧信息区 -->
        <div class="detail-info">
          <h1 class="product-name ceo-edit-trigger"
    @mousedown="startTitleHold" @mouseup="endTitleHold" @mouseleave="endTitleHold"
    @touchstart="startTitleHold" @touchend="endTitleHold"
    @click="handleTitleClick">{{ product.name }}
    <span v-if="titleHoldProgress > 0" class="hold-progress" :style="holdProgressStyle"></span>
</h1>
          <p class="product-subtitle" v-if="product.subtitle">{{ product.subtitle }}</p>

          <div class="price-box">
            <div class="price-row">
              <span class="price">¥{{ Number(product.price).toFixed(2) }}</span>
              <span class="market-price" v-if="product.market_price">¥{{ Number(product.market_price).toFixed(2) }}</span>
              <span class="discount-tag" v-if="product.market_price && product.price < product.market_price">{{ Math.round((1 - product.price / product.market_price) * 100) }}% OFF</span>
            </div>
            <div class="price-meta">
              <span class="meta-item">{{ t('product.sold') }} {{ product.sales || 0 }}</span>
              <span class="meta-item">{{ t('product.stock') }} {{ product.stock || 0 }}</span>
              <span class="meta-item" v-if="product.merchant_name">{{ product.merchant_name }}</span>
            </div>
          </div>

          <!-- 规格选择 -->
          <div class="spec-section shein-spec-section" v-if="product.skus && product.skus.length">
            <div class="spec-row" v-for="spec in specOptions" :key="spec.name">
              <span class="spec-label">{{ spec.name }}</span>
              <div class="spec-values shein-spec-values">
                <div class="spec-value shein-spec-value" v-for="val in spec.values" :key="val" :class="{active: selectedSpecs[spec.name] === val}" @click="selectSpec(spec.name, val)">
                  <img loading="lazy" v-if="getSkuImage(spec.name, val)" :src="getImageUrl(getSkuImage(spec.name, val))" class="spec-img shein-spec-img" :alt="val" />
                  <span class="spec-text">{{ val }}</span>
                </div>
              </div>
            </div>
            <!-- 尺码表入口（仅服装类商品显示） -->
            <div class="size-guide-entry" v-if="isClothingProduct" @click="showSizeGuide = true">
              <el-icon><Guide /></el-icon>
              <span>尺码对照表</span>
              <el-icon><ArrowRight /></el-icon>
            </div>
          </div>

          <!-- 数量选择 -->
          <div class="quantity-row">
            <span class="spec-label">{{ t('product.quantity') }}</span>
            <div class="quantity-input">
              <el-button size="small" @click="quantity > 1 && quantity--">-</el-button>
              <input type="number" v-model.number="quantity" min="1" :max="product.stock || 999" />
              <el-button size="small" @click="quantity < (product.stock || 999) && quantity++">+</el-button>
            </div>
          </div>

          <!-- 操作按钮 -->
          <div class="action-buttons">
            <el-button type="warning" size="large" class="btn-add-cart" @click="addToCart">
              <el-icon><ShoppingCart /></el-icon> {{ t('product.addToCart') }}
            </el-button>
            <el-button type="danger" size="large" class="btn-buy-now" @click="buyNow">
              <el-icon><CreditCard /></el-icon> {{ t('product.buyNow') }}
            </el-button>
            <el-button size="large" class="btn-favorite" @click="toggleFavorite">
              <el-icon :size="20"><Star v-if="!isFavorite" /><StarFilled v-else style="color:#f56c6c" /></el-icon>
              <span>{{ isFavorite ? t('product.favorited') : t('product.favorite') }}</span>
            </el-button>
          </div>

          <!-- 服务保障 -->
          <div class="service-guarantee">
            <span class="service-item"><el-icon><CircleCheck /></el-icon> {{ t('product.authentic') }}</span>
            <span class="service-item"><el-icon><Truck /></el-icon> {{ t('product.fastShipping') }}</span>
            <span class="service-item"><el-icon><Refresh /></el-icon> {{ t('product.return7days') }}</span>
            <span class="service-item"><el-icon><Service /></el-icon> {{ t('product.onlineService') }}</span>
          </div>
        </div>
      </div>

      <!-- 详情Tab区 -->
      <div v-if="product" class="detail-tabs">
        <el-tabs v-model="activeTab">
          <el-tab-pane :label="t('product.description')" name="detail">
            <div class="detail-content" v-if="product.detail" v-html="sanitizeHtml(product.detail)"></div>
            <div class="detail-content" v-else-if="product.description" v-html="sanitizeHtml(product.description)"></div>
            <div class="empty-detail" v-else>{{ t('product.noDetail') }}</div>
          </el-tab-pane>
          <el-tab-pane :label="t('product.specs')" name="specs">
            <el-table :data="specTableData" border size="small" class="spec-table">
              <el-table-column prop="name" :label="t('product.paramName')" width="150" />
              <el-table-column prop="value" :label="t('product.paramValue')" />
            </el-table>
          </el-tab-pane>
          <el-tab-pane :label="t('product.reviews') + '(' + (reviews.length || 0) + ')'" name="reviews">
            <div class="review-list" v-if="reviews.length">
              <div class="review-item" v-for="r in reviews" :key="r.id">
                <div class="review-header">
                  <el-avatar :size="32">{{ r.user_name?.charAt(0) || 'U' }}</el-avatar>
                  <span class="review-user">{{ r.user_name || t('product.anonymous') }}</span>
                  <el-rate v-model="r.rating" disabled size="small" />
                  <span class="review-date">{{ r.created_at }}</span>
                </div>
                <div class="review-content">{{ r.content }}</div>
                <div class="review-images" v-if="r.images && r.images.length">
                  <img loading="lazy" v-for="(img, idx) in r.images" :key="idx" :src="getImageUrl(img)" class="review-img" />
                </div>
              </div>
            </div>
            <div class="empty-reviews" v-else>{{ t('product.noReviews') }}</div>
          </el-tab-pane>
        </el-tabs>
      </div>

      <!-- 相关推荐 -->
      <div v-if="relatedProducts.length" class="related-section">
        <h3 class="related-title">{{ t('product.related') }}</h3>
        <div class="product-grid">
          <div class="product-card" v-for="p in relatedProducts" :key="p.id" @click="goRelatedDetail(p.id)">
            <div class="product-image">
              <img loading="lazy" :src="getImageUrl(p.main_image || p.image)" :alt="p.name" />
            </div>
            <div class="product-info">
              <div class="product-name">{{ p.name }}</div>
              <div class="product-price-row">
                <span class="product-price">¥{{ Number(p.price).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 商品评价区域 -->
    <div class="review-section" v-if="product">
      <div class="section-header">
        <h3 class="section-title">用户评价</h3>
        <span class="review-count" v-if="reviewStats.total">{{ reviewStats.total }}条评价</span>
      </div>
      <!-- 评价统计 -->
      <div class="review-stats" v-if="reviewStats.total > 0">
        <div class="avg-rating">
          <span class="avg-number">{{ reviewStats.avg_rating || '0.0' }}</span>
          <div class="star-rating">
            <el-rate :model-value="Number(reviewStats.avg_rating || 0)" disabled show-score text-color="#ff9900" score-template="{value}" />
          </div>
        </div>
        <div class="rating-bars">
          <div class="rating-bar" v-for="n in 5" :key="n">
            <span class="bar-label">{{ n }}星</span>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: getRatingPercent(6-n) + '%' }"></div>
            </div>
            <span class="bar-count">{{ reviewStats['rating_' + (6-n)] || 0 }}</span>
          </div>
        </div>
      </div>
      <!-- 空状态 -->
      <div class="review-empty" v-if="reviewStats.total === 0 && !reviewsLoading">
        <el-icon :size="48" color="#ccc"><ChatDotRound /></el-icon>
        <p>暂无评价，快来抢沙发吧~</p>
      </div>
      <!-- 评价列表 -->
      <div class="review-list" v-if="reviews.length > 0">
        <div class="review-item" v-for="review in reviews" :key="review.id">
          <div class="review-user">
            <el-avatar :size="40" :src="getImageUrl(review.user_avatar || review.avatar)">
              {{ (review.nickname || '用户').charAt(0) }}
            </el-avatar>
            <div class="user-info">
              <span class="user-name">{{ review.nickname || '匿名用户' }}</span>
              <el-rate :model-value="Number(review.rating || 5)" disabled size="small" />
            </div>
            <span class="review-time">{{ formatTime(review.created_at) }}</span>
          </div>
          <div class="review-content">{{ review.content }}</div>
          <div class="review-images" v-if="review.images && review.images.length">
            <img v-for="(img, idx) in review.images" :key="idx" :src="getImageUrl(img)" class="review-img" @click="previewImage(img)" />
          </div>
          <div class="merchant-reply" v-if="review.reply">
            <div class="reply-label">商家回复：</div>
            <div class="reply-content">{{ review.reply }}</div>
          </div>
        </div>
      </div>
      <!-- 加载更多 -->
      <div class="review-load-more" v-if="hasMoreReviews && !reviewsLoading">
        <el-button type="primary" plain @click="loadMoreReviews">加载更多评价</el-button>
      </div>
      <div class="review-loading" v-if="reviewsLoading">
        <el-icon class="is-loading" :size="20"><Loading /></el-icon>
        <span>加载中...</span>
      </div>
    </div>

    <!-- 移动端底部固定操作栏 -->
    <div class="mobile-bottom-bar" v-if="product && isMobile">
      <div class="bar-icons">
        <div class="bar-icon" @click="toggleFavorite">
          <el-icon :size="22"><Star v-if="!isFavorite" /><StarFilled v-else style="color:#f56c6c" /></el-icon>
          <span>{{ t('product.favorite') }}</span>
        </div>
        <div class="bar-icon" @click="$router.push('/cart')">
          <el-icon :size="22"><ShoppingCart /></el-icon>
          <span>{{ t('product.cart') }}</span>
        </div>
      </div>
      <div class="bar-buttons">
        <el-button class="btn-add" @click="addToCart">{{ t('product.addToCart') }}</el-button>
        <el-button class="btn-buy" @click="buyNow">{{ t('product.buyNow') }}</el-button>
      </div>
    </div>
  </div>

    <!-- 尺码表弹窗（仅服装类商品显示） -->
    <el-dialog v-if="isClothingProduct" v-model="showSizeGuide" title="尺码对照表" width="500px">
      <div class="size-guide-content">
        <table class="size-table">
          <thead>
            <tr>
              <th>尺码</th>
              <th>胸围(cm)</th>
              <th>腰围(cm)</th>
              <th>臀围(cm)</th>
              <th>肩宽(cm)</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>S</td><td>84-88</td><td>64-68</td><td>88-92</td><td>36-37</td></tr>
            <tr><td>M</td><td>88-92</td><td>68-72</td><td>92-96</td><td>37-38</td></tr>
            <tr><td>L</td><td>92-96</td><td>72-76</td><td>96-100</td><td>38-39</td></tr>
            <tr><td>XL</td><td>96-100</td><td>76-80</td><td>100-104</td><td>39-40</td></tr>
            <tr><td>XXL</td><td>100-104</td><td>80-84</td><td>104-108</td><td>40-41</td></tr>
          </tbody>
        </table>
        <p class="size-tip">* 以上数据仅供参考，实际尺码请以商品详情为准。测量方式不同，可能存在1-2cm误差。</p>
      </div>
    </el-dialog>

    <!-- CEO隐身编辑弹窗 -->
    <el-dialog v-model="ceoPasswordDialog" title="CEO编辑验证" width="400px" :close-on-click-modal="false">
      <el-input type="password" v-model="ceoPassword" placeholder="请输入CEO编辑密码" @keyup.enter="verifyCeoPassword" />
      <template #footer>
        <el-button @click="ceoPasswordDialog = false">取消</el-button>
        <el-button type="primary" @click="verifyCeoPassword">验证</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="ceoEditDialog" title="CEO商品编辑" width="600px" :close-on-click-modal="false">
      <el-form :model="ceoEditForm" label-width="80px">
        <el-form-item label="商品标题">
          <el-input v-model="ceoEditForm.name" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="商品价格">
          <el-input-number v-model="ceoEditForm.price" :min="0" :precision="2" :step="1" />
        </el-form-item>
        <el-form-item label="商品描述">
          <div class="ceo-rich-editor">
            <div class="editor-toolbar">
              <el-button size="small" @click="execCmd('bold')"><b>B</b></el-button>
              <el-button size="small" @click="execCmd('italic')"><i>I</i></el-button>
              <el-button size="small" @click="execCmd('underline')"><u>U</u></el-button>
              <el-button size="small" @click="execCmd('insertUnorderedList')">• 列表</el-button>
              <el-button size="small" @click="execCmd('insertOrderedList')">1. 列表</el-button>
              <el-button size="small" @click="insertImage">插入图片</el-button>
              <el-button size="small" @click="insertLink">插入链接</el-button>
            </div>
            <div ref="richEditorRef" class="rich-editor-content" contenteditable="true" @input="onRichEditorInput"></div>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="ceoEditDialog = false">取消</el-button>
        <el-button type="primary" @click="saveCeoEdit" :loading="ceoSaving">保存</el-button>
      </template>
    </el-dialog>

</template>

<script setup>
import PageHeader from "@/components/PageHeader.vue"
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { VideoPlay } from '@element-plus/icons-vue'
import { getProductDetail, getProductList } from '@/api/product'
import { addToCart as addCartApi } from '@/api/cart'
import { ChatDotRound, Loading } from '@element-plus/icons-vue'
import request from '@/utils/request'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const product = ref(null)
const loading = ref(true)
const currentMediaIndex = ref(0)
const quantity = ref(1)
const activeTab = ref('detail')
const isFavorite = ref(false)
const reviews = ref([])
const reviewsLoading = ref(false)
const reviewPage = ref(1)
const reviewPageSize = 10
const hasMoreReviews = ref(true)
const reviewStats = ref({ total: 0, avg_rating: '0.0', rating_5: 0, rating_4: 0, rating_3: 0, rating_2: 0, rating_1: 0 })
const relatedProducts = ref([])
const selectedSpecs = ref({})
const isMobile = ref(false)
const showSizeGuide = ref(false)

// 仅服装类商品显示尺码对照表（根据分类名称或ID判断）
const clothingCategoryIds = [] // 服装类分类ID列表，如有服装类商品请在此添加ID
const clothingKeywords = ['服装', '衣服', '女装', '男装', '童装', '内衣', '鞋', '靴', '裤', '裙', 'T恤', '衬衫', '外套', '毛衣']
const isClothingProduct = computed(() => {
  const p = product.value
  if (!p) return false
  // 根据分类ID判断
  if (p.category_id && clothingCategoryIds.includes(p.category_id)) return true
  // 根据分类名称判断
  const catName = p.category_name || p.category?.name || ''
  if (catName && clothingKeywords.some(kw => catName.includes(kw))) return true
  // 根据商品名称判断（兜底）
  if (p.name && clothingKeywords.some(kw => p.name.includes(kw))) return true
  return false
})

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchDetail()
})


// 生成商品JSON-LD结构化数据
const generateProductJsonLd = (productData) => {
  if (!productData) return

  // 移除旧的JSON-LD
  const oldScript = document.getElementById('product-jsonld')
  if (oldScript) oldScript.remove()

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    'name': productData.name,
    'description': productData.description || productData.detail || productData.name,
    'image': productData.main_image ? (productData.main_image.startsWith('http') ? productData.main_image : 'https://mall.tllos.com' + productData.main_image) : '',
    'sku': 'TLLOS-' + productData.id,
    'mpn': 'TLLOS-' + productData.id,
    'brand': {
      '@type': 'Brand',
      'name': productData.merchant_name || 'TLLOS'
    },
    'offers': {
      '@type': 'Offer',
      'url': 'https://mall.tllos.com/product/' + productData.id,
      'priceCurrency': 'CNY',
      'price': productData.price,
      'availability': productData.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
      'itemCondition': 'https://schema.org/NewCondition'
    },
    'aggregateRating': {
      '@type': 'AggregateRating',
      'ratingValue': reviewStats.value.avg_rating || '5.0',
      'reviewCount': reviewStats.value.total || 0
    }
  }

  const script = document.createElement('script')
  script.type = 'application/ld+json'
  script.id = 'product-jsonld'
  script.text = JSON.stringify(jsonLd)
  document.head.appendChild(script)
}

// 组件卸载时移除JSON-LD
onUnmounted(() => {
  const oldScript = document.getElementById('product-jsonld')
  if (oldScript) oldScript.remove()
})

// 监听商品数据变化，生成JSON-LD
watch(product, (newProduct) => {
  if (newProduct) {
    setTimeout(() => generateProductJsonLd(newProduct), 100)
  }
}, { deep: true })


// 获取评价列表
const fetchReviews = async (page = 1) => {
  if (!product.value) return
  reviewsLoading.value = true
  try {
    const res = await request({
      url: '/comments/product',
      method: 'get',
      params: { product_id: product.value.id, page, limit: reviewPageSize.value }
    })
    const data = res.data || res
    if (page === 1) {
      reviews.value = data.list || []
      reviewStats.value = data.stats || { total: 0, avg_rating: '0.0' }
    } else {
      reviews.value = [...reviews.value, ...(data.list || [])]
    }
    hasMoreReviews.value = (data.list || []).length >= reviewPageSize.value
    reviewPage.value = page
  } catch (e) {
    console.error('获取评价列表失败:', e)
  } finally {
    reviewsLoading.value = false
  }
}

const loadMoreReviews = () => {
  fetchReviews(reviewPage.value + 1)
}

const getRatingPercent = (rating) => {
  const total = reviewStats.value.total || 1
  const count = reviewStats.value['rating_' + rating] || 0
  return Math.round((count / total) * 100)
}

const formatTime = (time) => {
  if (!time) return ''
  return time.substring(0, 10)
}

const previewImage = (url) => {
  // 简单实现：新窗口打开图片
  window.open(getImageUrl(url), '_blank')
}

const getImageUrl = (url) => {
  if (!url) return '/assets/placeholder.jpg' + Date.now()
  if (url.startsWith('http')) return url
  return 'https://mall.tllos.com' + (url.startsWith('/') ? '' : '/') + url
}

const handleMainImgError = (event) => {
  event.target.src = '/assets/placeholder.jpg' + Date.now()
}

// 图片放大
const zoomShow = ref(false)
const mainImgRef = ref(null)
const lensStyle = ref({})
const resultStyle = ref({})
const zoomImgStyle = ref({})

const allMedia = computed(() => {
  if (!product.value) return []
  const media = []
  if (product.value.video) {
    media.push({ type: 'video', url: product.value.video })
  }
  if (product.value.main_image) {
    media.push({ type: 'image', url: product.value.main_image })
  }
  if (product.value.images && Array.isArray(product.value.images)) {
    product.value.images.forEach(img => {
      if (img && !media.find(m => m.url === img)) {
        media.push({ type: 'image', url: img })
      }
    })
  }
  return media.length ? media : [{ type: 'image', url: '' }]
})

const currentMedia = computed(() => allMedia.value[currentMediaIndex.value] || allMedia.value[0])

const holdProgressStyle = computed(() => ({
  width: titleHoldProgress.value + "%"
}))

const skuImageMap = computed(() => {
  if (!product.value?.skus) return {}
  const map = {}
  product.value.skus.forEach(sku => {
    if (sku.specs && sku.image) {
      Object.entries(sku.specs).forEach(([k, v]) => {
        const key = `${k}::${v}`
        if (!map[key]) map[key] = sku.image
      })
    }
  })
  return map
})

const specOptions = computed(() => {
  if (!product.value?.skus) return []
  const options = {}
  product.value.skus.forEach(sku => {
    if (sku.specs) {
      Object.entries(sku.specs).forEach(([k, v]) => {
        if (!options[k]) options[k] = new Set()
        options[k].add(v)
      })
    }
  })
  return Object.entries(options).map(([name, values]) => ({ name, values: Array.from(values) }))
})

const specTableData = computed(() => {
  if (!product.value?.specs) return []
  return Object.entries(product.value.specs).map(([name, value]) => ({ name, value }))
})

const getSkuImage = (specName, specValue) => {
  return skuImageMap.value[`${specName}::${specValue}`] || ''
}

const selectSpec = (name, value) => {
  selectedSpecs.value = { ...selectedSpecs.value, [name]: value }
}

const handleZoom = (e) => {
  if (!mainImgRef.value) return
  const rect = mainImgRef.value.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const lensSize = 100
  lensStyle.value = {
    left: Math.max(0, Math.min(x - lensSize / 2, rect.width - lensSize)) + 'px',
    top: Math.max(0, Math.min(y - lensSize / 2, rect.height - lensSize)) + 'px',
    width: lensSize + 'px',
    height: lensSize + 'px'
  }
  const zoomX = (x / rect.width) * 100
  const zoomY = (y / rect.height) * 100
  zoomImgStyle.value = {
    transform: `scale(2)`,
    transformOrigin: `${zoomX}% ${zoomY}%`
  }
}

const fetchDetail = async () => {
  loading.value = true
  try {
    const id = route.params.id || route.query.id
    const res = await getProductDetail(id)
    product.value = res.data?.product || res.data || null
    reviews.value = res.data?.reviews || []
    if (product.value) {
      fetchRelated()
    }
  } catch (e) {
    console.error(e)
    ElMessage.error(t('product.loadFailed'))
  } finally {
    loading.value = false
  }
}

const fetchRelated = async () => {
  try {
    const res = await getProductList({ category_id: product.value?.category_id, limit: 8 })
    relatedProducts.value = (res.data?.list || res.data || []).filter(p => p.id != product.value?.id).slice(0, 8)
  } catch (e) {
    console.error(e)
  }
}

const getSelectedSkuId = () => {
  if (!product.value?.skus || !product.value.skus.length) {
    return null // 无SKU，使用默认
  }
  // 找匹配的SKU
  for (const sku of product.value.skus) {
    if (!sku.specs) continue
    let match = true
    for (const [k, v] of Object.entries(selectedSpecs.value)) {
      if (sku.specs[k] !== v) { match = false; break }
    }
    // 必须所有规格都选了
    const requiredKeys = Object.keys(product.value.skus[0]?.specs || {})
    const selectedKeys = Object.keys(selectedSpecs.value)
    if (requiredKeys.length !== selectedKeys.length) match = false
    if (match) return sku.id
  }
  return null
}

const addToCart = async () => {
  try {
    // P2-03: 必须选择SKU
    let skuId = getSelectedSkuId()
    if (product.value?.skus?.length > 0 && !skuId) {
      ElMessage.warning('请选择商品规格')
      return
    }
    await addCartApi({ product_id: product.value.id, sku_id: skuId || 0, quantity: quantity.value })
    ElMessage.success(t('product.addCartSuccess'))
  } catch (e) {
    ElMessage.error(t('product.addCartFailed'))
  }
}

const buyNow = () => {
  // 立即购买：不加入购物车，直接存储商品信息到sessionStorage
  const buyNowItem = {
    product_id: product.value.id,
    name: product.value.name,
    price: product.value.price,
    main_image: product.value.main_image,
    quantity: quantity.value,
    specs: selectedSpecs.value
  }
  sessionStorage.setItem('buy_now_item', JSON.stringify(buyNowItem))
  router.push('/checkout')
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  ElMessage.success(isFavorite.value ? t('product.favoriteSuccess') : t('product.cancelFavorite'))
}

const goRelatedDetail = (id) => {
  router.push('/product/' + id)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ========== CEO隐身编辑功能 ==========
const ceoPasswordDialog = ref(false)
const ceoEditDialog = ref(false)
const ceoPassword = ref('')
const ceoToken = ref('')
const ceoSaving = ref(false)
const titleHoldTimer = ref(null)
const titleHoldProgress = ref(0)
const titleClickCount = ref(0)
const titleClickTimer = ref(null)
const richEditorRef = ref(null)

const ceoEditForm = ref({
  name: '',
  price: 0,
  description: ''
})

// XSS过滤函数
const sanitizeHtml = (html) => {
  if (!html) return ''
  let clean = html
  clean = clean.replace(/<script\b[^>]*>(.*?)<\/script>/gis, '')
  clean = clean.replace(/<iframe\b[^>]*>(.*?)<\/iframe>/gis, '')
  clean = clean.replace(/\son\w+="[^"]*"/gi, '')
  clean = clean.replace(/\son\w+='[^']*'/gi, '')
  clean = clean.replace(/javascript:/gi, '')
  clean = clean.replace(/<\?php/gi, '')
  return clean
}

// 长按标题3秒触发CEO编辑
const startTitleHold = () => {
  titleHoldProgress.value = 0
  let progress = 0
  titleHoldTimer.value = setInterval(() => {
    progress += 3.33
    titleHoldProgress.value = Math.min(progress, 100)
    if (progress >= 100) {
      clearInterval(titleHoldTimer.value)
      titleHoldProgress.value = 0
      triggerCeoEdit()
    }
  }, 100)
}

const endTitleHold = () => {
  if (titleHoldTimer.value) {
    clearInterval(titleHoldTimer.value)
    titleHoldTimer.value = null
  }
  titleHoldProgress.value = 0
}

// 连续点击5次触发CEO编辑
const handleTitleClick = () => {
  titleClickCount.value++
  if (titleClickTimer.value) clearTimeout(titleClickTimer.value)
  titleClickTimer.value = setTimeout(() => {
    titleClickCount.value = 0
  }, 2000)
  if (titleClickCount.value >= 5) {
    titleClickCount.value = 0
    triggerCeoEdit()
  }
}

const triggerCeoEdit = () => {
  if (ceoToken.value) {
    openCeoEditDialog()
  } else {
    ceoPassword.value = ''
    ceoPasswordDialog.value = true
  }
}

const verifyCeoPassword = async () => {
  try {
    const res = await fetch('/api/v1/ceo/verify-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password: ceoPassword.value })
    })
    const data = await res.json()
    if (data.code === 200 && data.data?.token) {
      ceoToken.value = data.data.token
      ceoPasswordDialog.value = false
      ElMessage.success('验证成功')
      openCeoEditDialog()
    } else {
      ElMessage.error(data.message || '密码错误')
    }
  } catch (e) {
    ElMessage.error('验证失败')
  }
}

const openCeoEditDialog = () => {
  ceoEditForm.value = {
    name: product.value?.name || '',
    price: Number(product.value?.price || 0),
    description: product.value?.description || product.value?.detail || ''
  }
  ceoEditDialog.value = true
  setTimeout(() => {
    if (richEditorRef.value) {
      richEditorRef.value.innerHTML = ceoEditForm.value.description
    }
  }, 100)
}

// 富文本编辑器
const execCmd = (cmd) => {
  document.execCommand(cmd, false, null)
  richEditorRef.value?.focus()
}

const insertImage = () => {
  const url = prompt('请输入图片URL:')
  if (url) {
    document.execCommand('insertImage', false, url)
  }
}

const insertLink = () => {
  const url = prompt('请输入链接URL:')
  if (url) {
    document.execCommand('createLink', false, url)
  }
}

const onRichEditorInput = () => {
  if (richEditorRef.value) {
    ceoEditForm.value.description = richEditorRef.value.innerHTML
  }
}

const saveCeoEdit = async () => {
  ceoSaving.value = true
  try {
    const res = await fetch('/api/v1/ceo/products/' + (route.params.id || route.query.id), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CEO-Token': ceoToken.value
      },
      body: JSON.stringify(ceoEditForm.value)
    })
    const data = await res.json()
    if (data.code === 200) {
      ElMessage.success('保存成功')
      ceoEditDialog.value = false
      fetchDetail()
    } else {
      ElMessage.error(data.message || '保存失败')
      if (data.code === 401) {
        ceoToken.value = ''
      }
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    ceoSaving.value = false
  }
}
// ========== CEO隐身编辑功能结束 ==========

</script>

<style scoped>
.product-detail-page {
  min-height: calc(100vh - 200px);
  padding-bottom: 80px;
}

.breadcrumb {
  padding: 16px 0;
  font-size: 13px;
  color: var(--color-text-secondary);
}
.breadcrumb a { color: var(--color-text-secondary); }
.breadcrumb a:hover { color: var(--color-primary); }
.breadcrumb .sep { margin: 0 8px; color: var(--color-text-placeholder); }
.breadcrumb .current { color: var(--color-text-regular); }

/* 加载中 */
.detail-loading { padding: 40px 0; }
.loading-skeleton { display: flex; gap: 30px; }
.skeleton-image-lg {
  width: 480px;
  height: 480px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: var(--radius-md);
}
.skeleton-info { flex: 1; padding-top: 20px; }
.skeleton-line {
  height: 16px;
  margin-bottom: 16px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: 4px;
}
@keyframes skeleton-loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.detail-wrapper {
  display: flex;
  gap: 30px;
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 24px;
  margin-bottom: 24px;
}

/* 图片区 */
.detail-images { width: 480px; flex-shrink: 0; }
.main-image-wrap { position: relative; }
.main-image {
  width: 100%;
  padding-top: 100%;
  position: relative;
  background: var(--color-bg-page);
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: crosshair;
}
.main-image img, .main-image video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.zoom-lens {
  position: absolute;
  border: 2px solid var(--color-primary);
  background: rgba(255, 107, 0, 0.1);
  pointer-events: none;
  z-index: 10;
}
.zoom-result {
  position: absolute;
  top: 0;
  left: calc(100% + 20px);
  width: 400px;
  height: 400px;
  border: 1px solid var(--color-border);
  background: #fff;
  overflow: hidden;
  z-index: 100;
  display: none;
}
.main-image-wrap:hover .zoom-result { display: block; }
.zoom-result img { width: 100%; height: 100%; object-fit: contain; }

.thumb-list {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  flex-wrap: wrap;
}
.thumb-item {
  width: 70px;
  height: 70px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color var(--transition-fast);
  position: relative;
}
.thumb-item:hover, .thumb-item.active {
  border-color: var(--color-primary);
}
.thumb-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.thumb-video { position: relative; width: 100%; height: 100%; }
.thumb-video .play-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #fff;
  background: rgba(0,0,0,.5);
  border-radius: 50%;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* SHEIN风格主图 - 3:4竖版 */
.shein-main-image {
  aspect-ratio: 3/4;
  max-height: 600px;
}
.image-counter {
  position: absolute;
  bottom: 12px;
  right: 12px;
  background: rgba(0,0,0,.6);
  color: #fff;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

/* SHEIN风格规格选择 */
.shein-spec-section {
  padding: 16px 0;
  border-top: 1px solid #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 16px;
}
.shein-spec-values {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.shein-spec-value {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  border: 2px solid #e0e0e0;
  overflow: hidden;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .2s;
}
.shein-spec-value:hover {
  border-color: #ff6b00;
}
.shein-spec-value.active {
  border-color: #ff6b00;
  box-shadow: 0 0 0 2px rgba(255,107,0,.2);
}
.shein-spec-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.shein-spec-value .spec-text {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0,0,0,.6);
  color: #fff;
  font-size: 10px;
  padding: 2px 4px;
  text-align: center;
}

/* 尺码表入口 */
.size-guide-entry {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 8px 12px;
  background: #f8f8f8;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  color: #666;
  transition: background .2s;
}
.size-guide-entry:hover {
  background: #f0f0f0;
  color: #ff6b00;
}

/* 尺码表内容 */
.size-guide-content {
  padding: 10px 0;
}
.size-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.size-table th, .size-table td {
  border: 1px solid #e0e0e0;
  padding: 10px 8px;
  text-align: center;
}
.size-table th {
  background: #f8f8f8;
  font-weight: 600;
  color: #333;
}
.size-table td {
  color: #666;
}
.size-tip {
  margin-top: 12px;
  font-size: 12px;
  color: #999;
  line-height: 1.6;
}

/* 信息区 */
.detail-info { flex: 1; min-width: 0; }
.product-name {
  font-size: 22px;
  font-weight: 600;
  margin: 0 0 8px;
  color: var(--color-text-primary);
  line-height: 1.4;
}
.product-subtitle {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin: 0 0 16px;
}

.price-box {
  background: var(--color-primary-bg);
  border-radius: var(--radius-md);
  padding: 16px 20px;
  margin-bottom: 20px;
}
.price-row {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 8px;
}
.price {
  font-size: 32px;
  font-weight: 700;
  color: var(--color-danger);
}
.market-price {
  font-size: 16px;
  color: var(--color-text-placeholder);
  text-decoration: line-through;
}
.discount-tag {
  background: var(--color-danger);
  color: #fff;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-weight: 600;
}
.price-meta {
  display: flex;
  gap: 20px;
  font-size: 13px;
  color: var(--color-text-secondary);
}
.meta-item { display: flex; align-items: center; gap: 4px; }

/* 规格选择 */
.spec-section { margin-bottom: 20px; }
.spec-row {
  display: flex;
  align-items: flex-start;
  margin-bottom: 16px;
}
.spec-label {
  width: 70px;
  flex-shrink: 0;
  font-size: 14px;
  color: var(--color-text-secondary);
  padding-top: 8px;
}
.spec-values {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  flex: 1;
}
.spec-value {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: 1px solid var(--color-border-dark);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 13px;
  transition: all var(--transition-fast);
  background: #fff;
}
.spec-value:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.spec-value.active {
  border-color: var(--color-primary);
  background: var(--color-primary-bg);
  color: var(--color-primary);
  font-weight: 500;
}
.spec-img {
  width: 24px;
  height: 24px;
  border-radius: var(--radius-sm);
  object-fit: cover;
}

/* 数量选择 */
.quantity-row {
  display: flex;
  align-items: center;
  margin-bottom: 24px;
}
.quantity-input {
  display: flex;
  align-items: center;
  border: 1px solid var(--color-border-dark);
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.quantity-input input {
  width: 60px;
  text-align: center;
  border: none;
  outline: none;
  font-size: 14px;
  padding: 6px 0;
}
.quantity-input .el-button {
  border: none;
  border-radius: 0;
  background: #f5f5f5;
}

/* 操作按钮 */
.action-buttons {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}
.btn-add-cart {
  flex: 1;
  background: var(--color-warning);
  border-color: var(--color-warning);
  height: 48px;
  font-size: 16px;
  font-weight: 600;
}
.btn-add-cart:hover {
  background: #ffad33;
  border-color: #ffad33;
}
.btn-buy-now {
  flex: 1;
  background: var(--color-danger);
  border-color: var(--color-danger);
  height: 48px;
  font-size: 16px;
  font-weight: 600;
}
.btn-buy-now:hover {
  background: #ff6b7a;
  border-color: #ff6b7a;
}
.btn-favorite {
  width: 100px;
  height: 48px;
  border-color: var(--color-border-dark);
}

/* 服务保障 */
.service-guarantee {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border-light);
}
.service-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--color-text-secondary);
}
.service-item .el-icon { color: var(--color-success); }

/* 详情Tab */
.detail-tabs {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 0 24px 24px;
  margin-bottom: 24px;
}
.detail-content {
  padding: 20px 0;
  line-height: 1.8;
  color: var(--color-text-regular);
}
.detail-content img {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-sm);
  margin: 10px 0;
}
.empty-detail, .empty-reviews {
  padding: 40px 0;
  text-align: center;
  color: var(--color-text-placeholder);
}
.spec-table { margin-top: 20px; }

/* 评价 */
.review-list { padding: 20px 0; }
.review-item {
  padding: 16px 0;
  border-bottom: 1px solid var(--color-border-light);
}
.review-item:last-child { border-bottom: none; }
.review-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}
.review-user { font-size: 14px; font-weight: 500; }
.review-date { font-size: 12px; color: var(--color-text-placeholder); margin-left: auto; }
.review-content {
  font-size: 14px;
  color: var(--color-text-regular);
  line-height: 1.6;
  margin-bottom: 10px;
}
.review-images { display: flex; gap: 10px; flex-wrap: wrap; }
.review-img {
  width: 80px;
  height: 80px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  cursor: pointer;
}

/* 相关推荐 */
.related-section { margin-bottom: 40px; }
.related-title {
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 20px;
  color: var(--color-text-primary);
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
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
}
.product-info { padding: 12px; }
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
.product-price-row { display: flex; align-items: baseline; }
.product-price {
  font-size: 16px;
  color: var(--color-danger);
  font-weight: 700;
}

/* 移动端底部操作栏 */
.mobile-bottom-bar {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 -2px 10px rgba(0,0,0,.08);
  z-index: 200;
}
.bar-icons {
  display: flex;
  gap: 16px;
}
.bar-icon {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  font-size: 10px;
  color: var(--color-text-secondary);
  cursor: pointer;
}
.bar-buttons {
  flex: 1;
  display: flex;
  gap: 8px;
}
.bar-buttons .el-button {
  flex: 1;
  height: 40px;
  font-size: 14px;
  font-weight: 600;
  border: none;
}
.btn-add {
  background: #ff6b00 !important;
  color: #fff !important;
  border-radius: 20px !important;
}
.btn-add:hover {
  background: #ff8c33 !important;
}
.btn-buy {
  background: #f56c6c !important;
  color: #fff !important;
  border-radius: 20px !important;
}
.btn-buy:hover {
  background: #f78989 !important;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .breadcrumb { padding: 12px 0; font-size: 12px; }
  .detail-wrapper {
    flex-direction: column;
    gap: 20px;
    padding: 16px;
  }
  .detail-images { width: 100%; }
  .main-image { border-radius: var(--radius-sm); }
  .shein-main-image {
    aspect-ratio: 3/4;
    max-height: none;
  }
  .image-counter {
    bottom: 8px;
    right: 8px;
    font-size: 11px;
    padding: 3px 8px;
  }
  .zoom-result { display: none !important; }
  .thumb-item { width: 56px; height: 56px; }
  .product-name { font-size: 18px; }
  .price { font-size: 26px; }
  .price-box { padding: 12px 16px; }
  .spec-label { width: 60px; font-size: 13px; }
  .shein-spec-value {
    width: 64px;
    height: 64px;
  }
  .shein-spec-value .spec-text {
    font-size: 9px;
  }
  .size-guide-entry {
    font-size: 12px;
    padding: 6px 10px;
  }
  .size-table {
    font-size: 12px;
  }
  .size-table th, .size-table td {
    padding: 8px 4px;
  }
  .action-buttons { display: none; }
  .service-guarantee { gap: 10px; }
  .service-item { font-size: 12px; }
  .detail-tabs { padding: 0 16px 16px; }
  .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .related-title { font-size: 18px; }
  .mobile-bottom-bar { display: flex; }
  .product-detail-page { padding-bottom: 70px; }
}

/* CEO隐身编辑样式 */
.ceo-edit-trigger {
  position: relative;
  cursor: pointer;
  user-select: none;
}
.hold-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, #ff6b00, #ff8c33);
  transition: width 0.1s linear;
}
.ceo-rich-editor {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;
}
.editor-toolbar {
  background: #f5f7fa;
  padding: 8px;
  border-bottom: 1px solid #dcdfe6;
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}
.rich-editor-content {
  min-height: 200px;
  padding: 12px;
  outline: none;
  font-size: 14px;
  line-height: 1.6;
}
.rich-editor-content img {
  max-width: 100%;
  height: auto;
}


/* 商品评价区域 */
.review-section {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  margin-top: 20px;
}
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 0;
}
.review-count {
  font-size: 14px;
  color: #999;
}
.review-stats {
  display: flex;
  gap: 40px;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
  margin-bottom: 20px;
}
.avg-rating {
  text-align: center;
  min-width: 120px;
}
.avg-number {
  font-size: 36px;
  font-weight: bold;
  color: #ff9900;
  display: block;
}
.star-rating {
  margin-top: 8px;
}
.rating-bars {
  flex: 1;
}
.rating-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.bar-label {
  width: 30px;
  font-size: 12px;
  color: #666;
}
.bar-track {
  flex: 1;
  height: 8px;
  background: #eee;
  border-radius: 4px;
  overflow: hidden;
}
.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #ff9900, #ff6b6b);
  border-radius: 4px;
  transition: width 0.3s;
}
.bar-count {
  width: 40px;
  font-size: 12px;
  color: #999;
  text-align: right;
}
.review-empty {
  text-align: center;
  padding: 40px 20px;
  color: #999;
}
.review-empty p {
  margin-top: 12px;
  font-size: 14px;
}
.review-list {
  border-top: 1px solid #f0f0f0;
}
.review-item {
  padding: 20px 0;
  border-bottom: 1px solid #f0f0f0;
}
.review-item:last-child {
  border-bottom: none;
}
.review-user {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.user-info {
  flex: 1;
}
.user-name {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  display: block;
  margin-bottom: 4px;
}
.review-time {
  font-size: 12px;
  color: #999;
}
.review-content {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin-bottom: 12px;
}
.review-images {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}
.review-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 6px;
  cursor: pointer;
}
.merchant-reply {
  background: #f9f9f9;
  border-radius: 6px;
  padding: 12px;
}
.reply-label {
  font-size: 12px;
  color: #ff6b6b;
  font-weight: 500;
  margin-bottom: 6px;
}
.reply-content {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
}
.review-load-more {
  text-align: center;
  padding: 20px 0;
}
.review-loading {
  text-align: center;
  padding: 20px 0;
  color: #999;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
@media (max-width: 768px) {
  .review-section {
    padding: 16px;
    margin-top: 12px;
  }
  .review-stats {
    flex-direction: column;
    gap: 20px;
  }
}
</style>
