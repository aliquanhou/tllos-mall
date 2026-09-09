<template>
  <div class="cart-page">
    <div class="cart-container">
      <h2 class="page-title">{{ t('cart.title') }} <span class="cart-count">({{ cartList.length }}件商品)</span></h2>

      <div v-if="cartList.length" class="cart-content">
        <!-- 商品列表 -->
        <div class="cart-list">
          <!-- 全选栏 -->
          <div class="cart-toolbar">
            <el-checkbox v-model="selectAll" @change="toggleSelectAll">{{ t('cart.selectAll') }}</el-checkbox>
            <span class="toolbar-tip">{{ t('cart.selected') }} {{ selectedCount }} {{ t('cart.items') }}</span>
            <el-button type="danger" link @click="clearInvalid">{{ t('cart.clearInvalid') }}</el-button>
          </div>

          <!-- 商品卡片 -->
          <div class="cart-item" v-for="item in cartList" :key="item.id" :class="{ invalid: item.status === 0 || item.stock === 0 }">
            <el-checkbox v-model="item.selected" @change="updateCart" :disabled="item.status === 0 || item.stock === 0" />

            <!-- 商品图片 -->
            <div class="item-image" @click="goDetail(item.product_id)">
              <img :src="getProductImage(item)" :alt="item.name" @error="handleImageError($event, item)" />
              <div v-if="item.status === 0 || item.stock === 0" class="invalid-mask">
                <span>{{ item.status === 0 ? t('cart.offShelf') : t('cart.outOfStock') }}</span>
              </div>
            </div>

            <!-- 商品信息 -->
            <div class="item-info">
              <div class="item-name" @click="goDetail(item.product_id)">{{ item.name }}</div>
              <div class="item-spec" v-if="item.specs || item.spec_text">{{ item.specs || item.spec_text }}</div>
              <div class="item-price-mobile">
                <span class="price-label">{{ t('cart.unitPrice') }}</span>
                <span class="price-value">¥{{ Number(item.price).toFixed(2) }}</span>
              </div>
            </div>

            <!-- 单价（PC端） -->
            <div class="item-price">¥{{ Number(item.price).toFixed(2) }}</div>

            <!-- 数量 -->
            <div class="item-quantity">
              <el-input-number
                v-model="item.quantity"
                :min="1"
                :max="item.stock || 999"
                size="small"
                @change="updateCart"
                :disabled="item.status === 0 || item.stock === 0"
              />
            </div>

            <!-- 小计 -->
            <div class="item-subtotal">
              <span class="subtotal-label">{{ t('cart.subtotal') }}</span>
              <span class="subtotal-value">¥{{ (Number(item.price) * (item.quantity || 1)).toFixed(2) }}</span>
            </div>

            <!-- 操作 -->
            <div class="item-action">
              <el-button type="danger" link @click="removeItem(item.id)">{{ t('cart.delete') }}</el-button>
            </div>
          </div>
        </div>

        <!-- 订单摘要（PC端） -->
        <div class="cart-summary">
          <div class="summary-card">
            <h3>{{ t('cart.orderSummary') }}</h3>
            <div class="summary-row">
              <span>{{ t('cart.itemCount') }}</span>
              <span>{{ selectedCount }} {{ t('cart.items') }}</span>
            </div>
            <div class="summary-row">
              <span>{{ t('cart.subtotal') }}</span>
              <span>¥{{ totalAmount.toFixed(2) }}</span>
            </div>
            <div class="summary-row">
              <span>{{ t('cart.shipping') }}</span>
              <span class="free">{{ t('cart.freeShipping') }}</span>
            </div>
            <div class="summary-total">
              <span>{{ t('cart.total') }}</span>
              <span class="total-price">¥{{ payAmount.toFixed(2) }}</span>
            </div>
            <el-button type="danger" size="large" class="checkout-btn" :disabled="selectedCount === 0" @click="goCheckout">
              {{ t('cart.checkout') }} ({{ selectedCount }})
            </el-button>
            <el-button type="primary" plain size="large" class="continue-btn" @click="$router.push('/products')">
              {{ t('cart.continueShopping') }}
            </el-button>
          </div>
        </div>
      </div>

      <!-- 空购物车 -->
      <div v-else class="empty-cart">
        <el-icon :size="80" color="#ddd"><ShoppingCart /></el-icon>
        <p>{{ t('cart.empty') }}</p>
        <el-button type="primary" size="large" @click="$router.push('/products')">{{ t('cart.goShopping') }}</el-button>
      </div>
    </div>

    <!-- 移动端底部结算栏 -->
    <div class="mobile-checkout-bar" v-if="cartList.length && isMobile">
      <el-checkbox v-model="selectAll" @change="toggleSelectAll">{{ t('cart.selectAll') }}</el-checkbox>
      <div class="mobile-total">
        <span class="total-label">{{ t('cart.total') }}</span>
        <span class="total-amount">¥{{ payAmount.toFixed(2) }}</span>
      </div>
      <el-button type="danger" class="mobile-checkout-btn" :disabled="selectedCount === 0" @click="goCheckout">
        {{ t('cart.checkout') }} ({{ selectedCount }})
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ShoppingCart } from '@element-plus/icons-vue'
import { getCart, updateCart as updateCartApi, deleteCart } from '@/api/cart'

const { t } = useI18n()
const router = useRouter()

const cartList = ref([])
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchCart()
})

const fetchCart = async () => {
  try {
    const res = await getCart()
    const list = res.data?.list || res.data || []
    cartList.value = list.map(i => ({
      ...i,
      selected: i.status !== 0 && i.stock !== 0
    }))
  } catch (e) {
    console.error(e)
  }
}

// 兼容多种图片字段，相对路径拼接域名
const getProductImage = (item) => {
  const img = item.main_image || item.image || item.cover_image || (item.images && item.images[0]) || ''
  if (!img) return 'https://picsum.photos/200/200?random=' + item.id
  if (img.startsWith('http')) return img
  return 'https://mall.tllos.com' + (img.startsWith('/') ? '' : '/') + img
}

const handleImageError = (event, item) => {
  event.target.src = 'https://picsum.photos/200/200?random=' + item.id
}

const selectAll = computed({
  get: () => {
    const validItems = cartList.value.filter(i => i.status !== 0 && i.stock !== 0)
    return validItems.length > 0 && validItems.every(i => i.selected)
  },
  set: (v) => {
    cartList.value.forEach(i => {
      if (i.status !== 0 && i.stock !== 0) i.selected = v
    })
  }
})

const selectedItems = computed(() => cartList.value.filter(i => i.selected && i.status !== 0 && i.stock !== 0))
const selectedCount = computed(() => selectedItems.value.reduce((sum, i) => sum + (i.quantity || 1), 0))
const totalAmount = computed(() => selectedItems.value.reduce((sum, i) => sum + Number(i.price) * (i.quantity || 1), 0))
const payAmount = computed(() => totalAmount.value)

const toggleSelectAll = () => {
  updateCart()
}

const updateCart = async () => {
  // 本地更新，可加防抖
}

const removeItem = async (id) => {
  try {
    await ElMessageBox.confirm(t('cart.confirmDelete'), t('common.confirm'), { type: 'warning' })
    await deleteCart(id)
    cartList.value = cartList.value.filter(i => i.id !== id)
    ElMessage.success(t('cart.deleteSuccess'))
  } catch (e) {
    if (e !== 'cancel') ElMessage.error(t('cart.deleteFailed'))
  }
}

const clearInvalid = () => {
  cartList.value = cartList.value.filter(i => i.status !== 0 && i.stock !== 0)
}

const goDetail = (productId) => {
  if (productId) router.push('/product/' + productId)
}

const goCheckout = () => {
  if (selectedCount.value === 0) {
    ElMessage.warning(t('cart.selectItemsFirst'))
    return
  }
  router.push('/checkout')
}
</script>

<style scoped>
.cart-page {
  min-height: calc(100vh - 200px);
  background: #f5f5f5;
  padding-bottom: 80px;
}
.cart-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
.page-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 20px;
  color: #333;
}
.cart-count {
  font-size: 14px;
  color: #999;
  font-weight: normal;
}
.cart-content {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}
.cart-list {
  flex: 1;
  min-width: 0;
}
.cart-toolbar {
  background: #fff;
  padding: 12px 16px;
  border-radius: 8px 8px 0 0;
  display: flex;
  align-items: center;
  gap: 16px;
  border-bottom: 1px solid #f0f0f0;
}
.toolbar-tip {
  font-size: 13px;
  color: #666;
}
.cart-item {
  background: #fff;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  border-bottom: 1px solid #f0f0f0;
  transition: background .2s;
}
.cart-item:hover {
  background: #fafafa;
}
.cart-item.invalid {
  opacity: .6;
}
.item-image {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  position: relative;
  background: #f5f5f5;
}
.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.invalid-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,.5);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 12px;
}
.item-info {
  flex: 1;
  min-width: 0;
}
.item-name {
  font-size: 14px;
  color: #333;
  line-height: 1.4;
  margin-bottom: 6px;
  cursor: pointer;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.item-name:hover {
  color: #ff6b00;
}
.item-spec {
  font-size: 12px;
  color: #999;
}
.item-price-mobile {
  display: none;
  margin-top: 8px;
}
.price-label {
  font-size: 12px;
  color: #999;
  margin-right: 8px;
}
.price-value {
  font-size: 16px;
  color: #ff6b00;
  font-weight: 600;
}
.item-price {
  width: 80px;
  text-align: center;
  font-size: 15px;
  color: #333;
  font-weight: 600;
  flex-shrink: 0;
}
.item-quantity {
  width: 120px;
  flex-shrink: 0;
}
.item-subtotal {
  width: 100px;
  text-align: center;
  flex-shrink: 0;
}
.subtotal-label {
  display: none;
}
.subtotal-value {
  font-size: 16px;
  color: #ff4757;
  font-weight: 700;
}
.item-action {
  width: 60px;
  flex-shrink: 0;
}
.cart-summary {
  width: 300px;
  flex-shrink: 0;
}
.summary-card {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  position: sticky;
  top: 20px;
}
.summary-card h3 {
  font-size: 18px;
  margin: 0 0 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f0f0;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 14px;
  color: #666;
}
.summary-row .free {
  color: #67c23a;
}
.summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-top: 1px solid #f0f0f0;
  margin-top: 8px;
}
.summary-total span:first-child {
  font-size: 16px;
  font-weight: 600;
}
.total-price {
  font-size: 24px;
  color: #ff4757;
  font-weight: 700;
}
.checkout-btn {
  width: 100%;
  margin-top: 16px;
  background: #ff6b00;
  border-color: #ff6b00;
}
.checkout-btn:hover {
  background: #ff8c33;
  border-color: #ff8c33;
}
.continue-btn {
  width: 100%;
  margin-top: 12px;
}
.empty-cart {
  background: #fff;
  border-radius: 8px;
  padding: 60px 20px;
  text-align: center;
}
.empty-cart p {
  color: #999;
  margin: 16px 0 24px;
}

/* 移动端底部结算栏 */
.mobile-checkout-bar {
  display: none;
  position: fixed;
  bottom: 60px;
  left: 0;
  right: 0;
  background: #fff;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 -2px 8px rgba(0,0,0,.06);
  z-index: 99;
}
.mobile-total {
  flex: 1;
  text-align: right;
}
.total-label {
  font-size: 12px;
  color: #666;
  margin-right: 4px;
}
.total-amount {
  font-size: 20px;
  color: #ff4757;
  font-weight: 700;
}
.mobile-checkout-btn {
  background: #ff6b00;
  border-color: #ff6b00;
  padding: 10px 24px;
  font-size: 14px;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .cart-container {
    padding: 12px;
  }
  .page-title {
    font-size: 18px;
    margin-bottom: 12px;
  }
  .cart-content {
    flex-direction: column;
    gap: 12px;
  }
  .cart-toolbar {
    padding: 10px 12px;
    border-radius: 8px;
  }
  .cart-item {
    flex-wrap: wrap;
    padding: 12px;
    gap: 10px;
    border-radius: 8px;
    margin-bottom: 8px;
    border-bottom: none;
  }
  .item-image {
    width: 80px;
    height: 80px;
  }
  .item-info {
    flex: 1;
    min-width: 0;
  }
  .item-name {
    font-size: 13px;
  }
  .item-price-mobile {
    display: block;
  }
  .item-price {
    display: none;
  }
  .item-quantity {
    width: 100%;
    padding-left: 24px;
  }
  .item-subtotal {
    width: auto;
    text-align: left;
    padding-left: 24px;
  }
  .subtotal-label {
    display: inline;
    font-size: 12px;
    color: #999;
    margin-right: 8px;
  }
  .subtotal-value {
    font-size: 18px;
  }
  .item-action {
    margin-left: auto;
    width: auto;
  }
  .cart-summary {
    display: none;
  }
  .mobile-checkout-bar {
    display: flex;
  }
  .empty-cart {
    padding: 40px 16px;
  }
}
</style>
