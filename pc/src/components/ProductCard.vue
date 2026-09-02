<template>
  <div class="product-card" @click="goDetail">
    <!-- 标签 -->
    <div class="product-tags">
      <span class="tag tag-hot" v-if="product.sales > 50">热销</span>
      <span class="tag tag-new" v-if="isNew">新品</span>
      <span class="tag tag-sale" v-if="product.market_price && product.price < product.market_price">特惠</span>
    </div>
    <!-- 图片区 -->
    <div class="product-image-wrap">
      <div class="product-image">
        <img :src="product.main_image || placeholder" :alt="product.name" @error="imgError" class="img-main" />
        <img :src="product.images?.[0] || product.main_image || placeholder" :alt="product.name" @error="imgError" class="img-hover" />
      </div>
      <!-- Hover操作层 -->
      <div class="product-actions">
        <el-button type="primary" size="small" class="action-btn" @click.stop="quickView">
          <el-icon><View /></el-icon> 快速查看
        </el-button>
        <el-button type="warning" size="small" class="action-btn" @click.stop="addToCart">
          <el-icon><ShoppingCart /></el-icon> 加入购物车
        </el-button>
      </div>
    </div>
    <!-- 信息区 -->
    <div class="product-info">
      <div class="product-name">{{ product.name }}</div>
      <div class="product-price-row">
        <span class="product-price">¥{{ product.price }}</span>
        <span class="product-market-price" v-if="product.market_price">¥{{ product.market_price }}</span>
      </div>
      <div class="product-meta">
        <span class="product-sales">已售{{ product.sales || 0 }}</span>
        <span class="product-shop" v-if="product.merchant_name">{{ product.merchant_name }}</span>
      </div>
    </div>
    <!-- 快速查看弹窗 -->
    <el-dialog v-model="showQuickView" title="商品详情" width="600px" class="quick-view-dialog">
      <div class="quick-view-content">
        <div class="qv-image"><img :src="product.main_image || placeholder" :alt="product.name" /></div>
        <div class="qv-info">
          <h3>{{ product.name }}</h3>
          <p class="qv-price">¥{{ product.price }} <span class="qv-market" v-if="product.market_price">¥{{ product.market_price }}</span></p>
          <p class="qv-desc">{{ product.description || '暂无描述' }}</p>
          <div class="qv-actions">
            <el-button type="warning" size="large" @click="goDetail">查看详情</el-button>
            <el-button type="primary" size="large" @click="addToCart">加入购物车</el-button>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useCartStore } from '@/stores/cart'

const props = defineProps({
  product: { type: Object, required: true }
})

const router = useRouter()
const cartStore = useCartStore()
const showQuickView = ref(false)
const placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23f5f5f5" width="200" height="200"/%3E%3Ctext fill="%23ccc" font-size="14" x="50%25" y="50%25" text-anchor="middle"%3E暂无图片%3C/text%3E%3C/svg%3E'

const isNew = computed(() => {
  if (!props.product.created_at) return false
  const created = new Date(props.product.created_at).getTime()
  return Date.now() - created < 7 * 24 * 60 * 60 * 1000
})

const goDetail = () => router.push(`/product/${props.product.id}`)
const quickView = () => { showQuickView.value = true }
const addToCart = async () => {
  try {
    await cartStore.addToCart(props.product.id, 1)
    ElMessage.success('已加入购物车')
  } catch (e) { ElMessage.error('加入购物车失败') }
}
const imgError = (e) => { e.target.src = placeholder }
</script>

<style scoped>
.product-card { background: #fff; border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.25s, box-shadow 0.25s; position: relative; border: 1px solid #f0f0f0; }
.product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 28px rgba(0,0,0,0.12); z-index: 10; }
.product-tags { position: absolute; top: 10px; left: 10px; z-index: 5; display: flex; gap: 6px; }
.tag { padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #fff; }
.tag-hot { background: #f56c6c; }
.tag-new { background: #67c23a; }
.tag-sale { background: #e6a23c; }
.product-image-wrap { position: relative; overflow: hidden; }
.product-image { position: relative; width: 100%; padding-top: 100%; background: #fafafa; }
.product-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s; }
.img-hover { opacity: 0; }
.product-card:hover .img-hover { opacity: 1; }
.product-card:hover .img-main { opacity: 0; }
.product-actions { position: absolute; bottom: 0; left: 0; right: 0; padding: 12px; background: linear-gradient(transparent, rgba(0,0,0,0.6)); display: flex; flex-direction: column; gap: 8px; transform: translateY(100%); transition: transform 0.3s; }
.product-card:hover .product-actions { transform: translateY(0); }
.action-btn { width: 100%; }
.product-info { padding: 12px; }
.product-name { font-size: 13px; color: #333; line-height: 1.4; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 8px; }
.product-price-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 6px; }
.product-price { font-size: 18px; color: #f56c6c; font-weight: bold; }
.product-market-price { font-size: 12px; color: #ccc; text-decoration: line-through; }
.product-meta { display: flex; justify-content: space-between; font-size: 11px; color: #999; }
.quick-view-dialog :deep(.el-dialog__body) { padding: 0; }
.quick-view-content { display: flex; gap: 20px; padding: 20px; }
.qv-image { width: 250px; height: 250px; flex-shrink: 0; border-radius: 8px; overflow: hidden; background: #fafafa; }
.qv-image img { width: 100%; height: 100%; object-fit: cover; }
.qv-info { flex: 1; }
.qv-info h3 { font-size: 18px; margin: 0 0 12px 0; color: #333; }
.qv-price { font-size: 24px; color: #f56c6c; font-weight: bold; margin: 0 0 12px 0; }
.qv-market { font-size: 14px; color: #ccc; text-decoration: line-through; font-weight: normal; }
.qv-desc { font-size: 13px; color: #666; line-height: 1.6; margin-bottom: 20px; max-height: 100px; overflow: hidden; }
.qv-actions { display: flex; gap: 12px; }
</style>
