<template>
  <div class="product-detail-page">
    <div class="container">
      <!-- 面包屑 -->
      <div class="breadcrumb">
        <a href="javascript:;" @click="$router.push('/home')">首页</a>
        <span class="sep">/</span>
        <a href="javascript:;" @click="$router.push('/products')">全部商品</a>
        <span class="sep">/</span>
        <span class="current">{{ product?.name || '商品详情' }}</span>
      </div>

      <!-- 骨架屏 -->
      <Skeleton v-if="loading" type="product-detail" />

      <div v-else-if="product" class="detail-wrapper">
        <!-- 左侧图片区 -->
        <div class="detail-images">
          <div class="main-image" @mousemove="handleZoom" @mouseleave="zoomShow=false" @mouseenter="zoomShow=true">
            <img :src="currentImage" :alt="product.name" ref="mainImgRef" />
            <div class="zoom-lens" v-if="zoomShow" :style="lensStyle"></div>
          </div>
          <div class="zoom-result" v-if="zoomShow" :style="resultStyle">
            <img :src="currentImage" :style="zoomImgStyle" />
          </div>
          <div class="thumb-list">
            <div class="thumb-item" v-for="(img, idx) in allImages" :key="idx" :class="{active: currentImageIndex === idx}" @click="currentImageIndex = idx">
              <img :src="img" :alt="'缩略图'+(idx+1)" />
            </div>
          </div>
        </div>

        <!-- 右侧信息区 -->
        <div class="detail-info">
          <h1 class="product-name">{{ product.name }}</h1>
          <p class="product-subtitle" v-if="product.subtitle">{{ product.subtitle }}</p>

          <div class="price-box">
            <div class="price-row">
              <span class="price-label">价格</span>
              <span class="price">¥{{ product.price }}</span>
              <span class="market-price" v-if="product.market_price">¥{{ product.market_price }}</span>
              <span class="discount-tag" v-if="product.market_price && product.price < product.market_price">{{ Math.round((1 - product.price / product.market_price) * 100) }}% OFF</span>
            </div>
            <div class="price-meta">
              <span>已售 {{ product.sales || 0 }} 件</span>
              <span>库存 {{ product.stock || 0 }} 件</span>
              <span v-if="product.merchant_name">店铺：{{ product.merchant_name }}</span>
            </div>
          </div>

          <!-- 规格选择 -->
          <div class="spec-section" v-if="product.skus && product.skus.length">
            <div class="spec-row" v-for="spec in specOptions" :key="spec.name">
              <span class="spec-label">{{ spec.name }}</span>
              <div class="spec-values">
                <span class="spec-value" v-for="val in spec.values" :key="val" :class="{active: selectedSpecs[spec.name] === val}" @click="selectSpec(spec.name, val)">{{ val }}</span>
              </div>
            </div>
          </div>

          <!-- 数量选择 -->
          <div class="quantity-row">
            <span class="spec-label">数量</span>
            <div class="quantity-input">
              <el-button size="small" @click="quantity > 1 && quantity--">-</el-button>
              <input type="number" v-model.number="quantity" min="1" :max="product.stock || 999" />
              <el-button size="small" @click="quantity < (product.stock || 999) && quantity++">+</el-button>
            </div>
            <span class="stock-tip">库存 {{ product.stock || 0 }} 件</span>
          </div>

          <!-- 操作按钮 -->
          <div class="action-buttons">
            <el-button type="warning" size="large" class="btn-add-cart" @click="addToCart">
              <el-icon><ShoppingCart /></el-icon> 加入购物车
            </el-button>
            <el-button type="danger" size="large" class="btn-buy-now" @click="buyNow">
              <el-icon><CreditCard /></el-icon> 立即购买
            </el-button>
            <el-button size="large" class="btn-favorite" @click="toggleFavorite">
              <el-icon :size="20"><Star v-if="!isFavorite" /><StarFilled v-else style="color:#f56c6c" /></el-icon>
              <span>{{ isFavorite ? '已收藏' : '收藏' }}</span>
            </el-button>
          </div>

          <!-- 服务保障 -->
          <div class="service-guarantee">
            <span><el-icon><CircleCheck /></el-icon> 正品保障</span>
            <span><el-icon><Truck /></el-icon> 极速配送</span>
            <span><el-icon><Refresh /></el-icon> 7天无理由退换</span>
            <span><el-icon><Service /></el-icon> 在线客服</span>
          </div>
        </div>
      </div>

      <!-- 详情Tab区 -->
      <div v-if="product" class="detail-tabs">
        <el-tabs v-model="activeTab">
          <el-tab-pane label="商品详情" name="detail">
            <div class="detail-content" v-if="product.description" v-html="product.description"></div>
            <div class="detail-images" v-if="product.images && product.images.length">
              <img v-for="(img, idx) in product.images" :key="idx" :src="img" class="detail-img" />
            </div>
            <div class="empty-detail" v-else>暂无商品详情</div>
          </el-tab-pane>
          <el-tab-pane label="规格参数" name="specs">
            <el-table :data="specTableData" border size="small">
              <el-table-column prop="name" label="参数名称" width="150" />
              <el-table-column prop="value" label="参数值" />
            </el-table>
          </el-tab-pane>
          <el-tab-pane :label="'评价(' + (reviews.length || 0) + ')'" name="reviews">
            <div class="review-list" v-if="reviews.length">
              <div class="review-item" v-for="r in reviews" :key="r.id">
                <div class="review-header">
                  <el-avatar :size="32">{{ r.user_name?.charAt(0) || 'U' }}</el-avatar>
                  <span class="review-user">{{ r.user_name || '匿名用户' }}</span>
                  <el-rate v-model="r.rating" disabled size="small" />
                  <span class="review-date">{{ r.created_at }}</span>
                </div>
                <div class="review-content">{{ r.content }}</div>
                <div class="review-images" v-if="r.images && r.images.length">
                  <img v-for="(img, idx) in r.images" :key="idx" :src="img" class="review-img" />
                </div>
              </div>
            </div>
            <div class="empty-reviews" v-else>暂无评价，快来抢沙发吧~</div>
          </el-tab-pane>
        </el-tabs>
      </div>

      <!-- 相关推荐 -->
      <div v-if="relatedProducts.length" class="related-section">
        <h3 class="related-title">猜你喜欢</h3>
        <div class="product-grid">
          <ProductCard v-for="p in relatedProducts" :key="p.id" :product="p" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getProductDetail, getProductList } from '@/api/product'
import { addToCart as addCartApi } from '@/api/cart'
import ProductCard from '@/components/ProductCard.vue'
import Skeleton from '@/components/Skeleton.vue'

const route = useRoute()
const router = useRouter()
const product = ref(null)
const loading = ref(true)
const currentImageIndex = ref(0)
const quantity = ref(1)
const activeTab = ref('detail')
const isFavorite = ref(false)
const reviews = ref([])
const relatedProducts = ref([])
const selectedSpecs = ref({})

// 图片放大
const zoomShow = ref(false)
const mainImgRef = ref(null)
const lensStyle = ref({})
const resultStyle = ref({})
const zoomImgStyle = ref({})

const allImages = computed(() => {
  if (!product.value) return []
  const imgs = [product.value.main_image].filter(Boolean)
  if (product.value.images && Array.isArray(product.value.images)) {
    product.value.images.forEach(img => { if (img && !imgs.includes(img)) imgs.push(img) })
  }
  return imgs.length ? imgs : ['data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="400"%3E%3Crect fill="%23f5f5f5" width="400" height="400"/%3E%3Ctext fill="%23ccc" font-size="20" x="50%25" y="50%25" text-anchor="middle"%3E暂无图片%3C/text%3E%3C/svg%3E']
})

const currentImage = computed(() => allImages.value[currentImageIndex.value] || allImages.value[0])

const specOptions = computed(() => {
  if (!product.value?.skus || !product.value.skus.length) return []
  const specs = {}
  product.value.skus.forEach(sku => {
    if (sku.specs) {
      Object.entries(sku.specs).forEach(([k, v]) => {
        if (!specs[k]) specs[k] = new Set()
        specs[k].add(v)
      })
    }
  })
  return Object.entries(specs).map(([name, values]) => ({ name, values: [...values] }))
})

const specTableData = computed(() => {
  if (!product.value) return []
  const data = []
  if (product.value.category_name) data.push({ name: '商品分类', value: product.value.category_name })
  if (product.value.brand_name) data.push({ name: '品牌', value: product.value.brand_name })
  if (product.value.stock) data.push({ name: '库存', value: product.value.stock + '件' })
  if (product.value.sales) data.push({ name: '销量', value: product.value.sales + '件' })
  data.push({ name: '发货地', value: '全国' })
  data.push({ name: '售后服务', value: '7天无理由退换' })
  return data
})

const handleZoom = (e) => {
  if (!mainImgRef.value) return
  const rect = mainImgRef.value.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const lensSize = 100
  const lensX = Math.max(0, Math.min(x - lensSize / 2, rect.width - lensSize))
  const lensY = Math.max(0, Math.min(y - lensSize / 2, rect.height - lensSize))
  lensStyle.value = { left: lensX + 'px', top: lensY + 'px', width: lensSize + 'px', height: lensSize + 'px' }
  const zoomRatio = 2
  resultStyle.value = { backgroundSize: rect.width * zoomRatio + 'px ' + rect.height * zoomRatio + 'px' }
  zoomImgStyle.value = { width: rect.width * zoomRatio + 'px', height: rect.height * zoomRatio + 'px', marginLeft: -lensX * zoomRatio + 'px', marginTop: -lensY * zoomRatio + 'px' }
}

const selectSpec = (name, val) => { selectedSpecs.value[name] = val }

const fetchDetail = async () => {
  loading.value = true
  try {
    const res = await getProductDetail(route.params.id)
    product.value = res.data
    // 模拟评价数据
    reviews.value = res.data.reviews || []
    // 获取相关推荐
    const relatedRes = await getProductList({ category_id: product.value.category_id, limit: 5 })
    relatedProducts.value = (relatedRes.data?.list || relatedRes.data || []).filter(p => p.id != product.value.id).slice(0, 5)
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const addToCart = async () => {
  try {
    await addCartApi({ product_id: product.value.id, quantity: quantity.value, specs: selectedSpecs.value })
    ElMessage.success('已加入购物车')
  } catch (e) { ElMessage.error(e.message || '加入购物车失败') }
}

const buyNow = async () => {
  try {
    await addCartApi({ product_id: product.value.id, quantity: quantity.value, specs: selectedSpecs.value })
    router.push('/checkout')
  } catch (e) { ElMessage.error(e.message || '操作失败') }
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  ElMessage.success(isFavorite.value ? '已收藏' : '已取消收藏')
}

onMounted(fetchDetail)
</script>

<style scoped>
.product-detail-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.breadcrumb { font-size: 13px; color: #999; margin-bottom: 16px; }
.breadcrumb a { color: #666; text-decoration: none; }
.breadcrumb a:hover { color: #e6a23c; }
.breadcrumb .sep { margin: 0 8px; }
.breadcrumb .current { color: #333; }
.detail-wrapper { display: grid; grid-template-columns: 480px 1fr; gap: 30px; background: #fff; padding: 24px; border-radius: 8px; margin-bottom: 20px; }
.detail-images { position: relative; }
.main-image { width: 100%; padding-top: 100%; position: relative; background: #fafafa; border-radius: 8px; overflow: hidden; cursor: crosshair; }
.main-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; }
.zoom-lens { position: absolute; border: 2px solid #e6a23c; background: rgba(230,162,60,0.1); pointer-events: none; }
.zoom-result { position: absolute; top: 0; left: 105%; width: 100%; height: 100%; border: 1px solid #eee; border-radius: 8px; overflow: hidden; z-index: 100; background: #fff; }
.zoom-result img { position: absolute; top: 0; left: 0; }
.thumb-list { display: flex; gap: 10px; margin-top: 12px; }
.thumb-item { width: 60px; height: 60px; border: 2px solid #eee; border-radius: 4px; overflow: hidden; cursor: pointer; flex-shrink: 0; }
.thumb-item.active { border-color: #e6a23c; }
.thumb-item img { width: 100%; height: 100%; object-fit: cover; }
.detail-info { min-width: 0; }
.product-name { font-size: 22px; color: #333; margin: 0 0 8px 0; line-height: 1.4; }
.product-subtitle { font-size: 14px; color: #999; margin: 0 0 16px 0; }
.price-box { background: linear-gradient(135deg, #fef6e4, #fdf0d5); padding: 16px; border-radius: 8px; margin-bottom: 20px; }
.price-row { display: flex; align-items: baseline; gap: 12px; margin-bottom: 8px; }
.price-label { font-size: 14px; color: #999; }
.price { font-size: 32px; color: #f56c6c; font-weight: bold; }
.market-price { font-size: 14px; color: #999; text-decoration: line-through; }
.discount-tag { background: #f56c6c; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.price-meta { display: flex; gap: 20px; font-size: 13px; color: #666; }
.spec-section { margin-bottom: 20px; }
.spec-row { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
.spec-label { width: 60px; flex-shrink: 0; font-size: 14px; color: #666; padding-top: 6px; }
.spec-values { display: flex; flex-wrap: wrap; gap: 10px; }
.spec-value { padding: 6px 16px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; cursor: pointer; transition: all 0.2s; }
.spec-value:hover { border-color: #e6a23c; color: #e6a23c; }
.spec-value.active { border-color: #e6a23c; color: #e6a23c; background: #fdf6ec; font-weight: bold; }
.quantity-row { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.quantity-input { display: flex; align-items: center; }
.quantity-input input { width: 60px; text-align: center; border: 1px solid #ddd; border-left: none; border-right: none; height: 32px; font-size: 14px; }
.stock-tip { font-size: 13px; color: #999; }
.action-buttons { display: flex; gap: 12px; margin-bottom: 20px; }
.btn-add-cart { flex: 1; background: #e6a23c; border-color: #e6a23c; }
.btn-add-cart:hover { background: #d4922a; border-color: #d4922a; }
.btn-buy-now { flex: 1; background: #f56c6c; border-color: #f56c6c; }
.btn-buy-now:hover { background: #e45656; border-color: #e45656; }
.btn-favorite { border-color: #ddd; color: #666; }
.service-guarantee { display: flex; flex-wrap: wrap; gap: 20px; padding-top: 16px; border-top: 1px solid #f0f0f0; }
.service-guarantee span { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #666; }
.service-guarantee .el-icon { color: #67c23a; }
.detail-tabs { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.detail-content { font-size: 14px; color: #333; line-height: 1.8; }
.detail-content img { max-width: 100%; border-radius: 4px; margin: 10px 0; }
.detail-images { margin-top: 16px; }
.detail-img { width: 100%; border-radius: 4px; margin-bottom: 10px; }
.empty-detail, .empty-reviews { text-align: center; padding: 40px; color: #999; }
.review-list { }
.review-item { padding: 16px 0; border-bottom: 1px solid #f0f0f0; }
.review-item:last-child { border-bottom: none; }
.review-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.review-user { font-size: 14px; color: #333; font-weight: 500; }
.review-date { font-size: 12px; color: #999; margin-left: auto; }
.review-content { font-size: 14px; color: #666; line-height: 1.6; }
.review-images { display: flex; gap: 8px; margin-top: 10px; }
.review-img { width: 80px; height: 80px; border-radius: 4px; object-fit: cover; cursor: pointer; }
.related-section { background: #fff; border-radius: 8px; padding: 20px; }
.related-title { font-size: 18px; color: #333; margin: 0 0 16px 0; }
.product-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; }
</style>
