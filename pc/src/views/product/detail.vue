<template>
  <div class="product-detail-page">
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
            <div class="main-image" @mousemove="handleZoom" @mouseleave="zoomShow=false" @mouseenter="zoomShow=true">
              <video v-if="currentMedia.type === 'video'" :src="currentMedia.url" :poster="product.video_poster || ''" controls class="main-video" @mouseenter.stop @mousemove.stop></video>
              <img v-else :src="getImageUrl(currentMedia.url)" :alt="product.name" ref="mainImgRef" @error="handleMainImgError" />
              <div class="zoom-lens" v-if="zoomShow && currentMedia.type === 'image'" :style="lensStyle"></div>
            </div>
            <div class="zoom-result" v-if="zoomShow && currentMedia.type === 'image'" :style="resultStyle">
              <img :src="getImageUrl(currentMedia.url)" :style="zoomImgStyle" />
            </div>
          </div>
          <div class="thumb-list">
            <div class="thumb-item" v-for="(media, idx) in allMedia" :key="idx" :class="{active: currentMediaIndex === idx}" @click="currentMediaIndex = idx">
              <img v-if="media.type === 'image'" :src="getImageUrl(media.url)" :alt="'缩略图'+(idx+1)" />
              <div v-else class="thumb-video">
                <img :src="getImageUrl(product.video_poster || media.url)" alt="视频缩略图" />
                <div class="play-icon"><el-icon :size="20"><VideoPlay /></el-icon></div>
              </div>
            </div>
          </div>
        </div>

        <!-- 右侧信息区 -->
        <div class="detail-info">
          <h1 class="product-name">{{ product.name }}</h1>
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
          <div class="spec-section" v-if="product.skus && product.skus.length">
            <div class="spec-row" v-for="spec in specOptions" :key="spec.name">
              <span class="spec-label">{{ spec.name }}</span>
              <div class="spec-values">
                <div class="spec-value" v-for="val in spec.values" :key="val" :class="{active: selectedSpecs[spec.name] === val}" @click="selectSpec(spec.name, val)">
                  <img v-if="getSkuImage(spec.name, val)" :src="getImageUrl(getSkuImage(spec.name, val))" class="spec-img" :alt="val" />
                  <span class="spec-text">{{ val }}</span>
                </div>
              </div>
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
            <div class="detail-content" v-if="product.detail" v-html="product.detail"></div>
            <div class="detail-content" v-else-if="product.description" v-html="product.description"></div>
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
                  <img v-for="(img, idx) in r.images" :key="idx" :src="getImageUrl(img)" class="review-img" />
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
              <img :src="getImageUrl(p.main_image || p.image)" :alt="p.name" />
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { VideoPlay } from '@element-plus/icons-vue'
import { getProductDetail, getProductList } from '@/api/product'
import { addToCart as addCartApi } from '@/api/cart'

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
const relatedProducts = ref([])
const selectedSpecs = ref({})
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchDetail()
})

const getImageUrl = (url) => {
  if (!url) return 'https://picsum.photos/400/400?random=' + Date.now()
  if (url.startsWith('http')) return url
  return 'https://mall.tllos.com' + (url.startsWith('/') ? '' : '/') + url
}

const handleMainImgError = (event) => {
  event.target.src = 'https://picsum.photos/400/400?random=' + Date.now()
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

const addToCart = async () => {
  try {
    await addCartApi({ product_id: product.value.id, quantity: quantity.value, specs: selectedSpecs.value })
    ElMessage.success(t('product.addCartSuccess'))
  } catch (e) {
    ElMessage.error(t('product.addCartFailed'))
  }
}

const buyNow = () => {
  addToCart().then(() => {
    router.push('/checkout')
  })
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  ElMessage.success(isFavorite.value ? t('product.favoriteSuccess') : t('product.cancelFavorite'))
}

const goRelatedDetail = (id) => {
  router.push('/product/' + id)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
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
  z-index: 100;
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
  background: var(--color-warning);
  color: #fff;
}
.btn-buy {
  background: var(--color-danger);
  color: #fff;
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
  .zoom-result { display: none !important; }
  .thumb-item { width: 56px; height: 56px; }
  .product-name { font-size: 18px; }
  .price { font-size: 26px; }
  .price-box { padding: 12px 16px; }
  .spec-label { width: 60px; font-size: 13px; }
  .action-buttons { display: none; }
  .service-guarantee { gap: 10px; }
  .service-item { font-size: 12px; }
  .detail-tabs { padding: 0 16px 16px; }
  .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .related-title { font-size: 18px; }
  .mobile-bottom-bar { display: flex; }
  .product-detail-page { padding-bottom: 70px; }
}
</style>
