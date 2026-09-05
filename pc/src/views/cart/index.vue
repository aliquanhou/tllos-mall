<template>
  <div class="cart-page">
    <div class="container">
      <h2 class="page-title">我的购物车 <span class="cart-count">({{ selectedCount }}件商品)</span></h2>
      <div class="cart-wrapper" v-if="cartList.length">
        <div class="cart-left">
          <div class="cart-header">
            <el-checkbox v-model="selectAll" @change="toggleSelectAll">全选</el-checkbox>
            <span class="header-info">商品信息</span>
            <span class="header-price">单价</span>
            <span class="header-quantity">数量</span>
            <span class="header-subtotal">小计</span>
            <span class="header-action">操作</span>
          </div>
          <div class="cart-item" v-for="item in cartList" :key="item.id">
            <el-checkbox v-model="item.selected" @change="updateCart"></el-checkbox>
            <div class="item-image" @click="goDetail(item.product_id)"><img :src="item.main_image" :alt="item.name" /></div>
            <div class="item-info">
              <div class="item-name" @click="goDetail(item.product_id)">{{ item.name }}</div>
              <div class="item-spec" v-if="item.specs">{{ item.specs }}</div>
            </div>
            <div class="item-price">¥{{ item.price }}</div>
            <div class="item-quantity">
              <el-input-number v-model="item.quantity" :min="1" :max="item.stock || 999" size="small" @change="updateCart" />
            </div>
            <div class="item-subtotal">¥{{ (item.price * item.quantity).toFixed(2) }}</div>
            <div class="item-action"><el-button type="danger" link @click="removeItem(item.id)">删除</el-button></div>
          </div>
        </div>
        <div class="cart-right">
          <div class="order-summary">
            <h3>订单摘要</h3>
            <div class="summary-row"><span>商品件数</span><span>{{ selectedCount }} 件</span></div>
            <div class="summary-row"><span>商品总额</span><span>¥{{ totalAmount.toFixed(2) }}</span></div>
            <div class="summary-row"><span>运费</span><span class="free">免运费</span></div>
            <div class="summary-row discount" v-if="discountAmount > 0"><span>优惠</span><span>-¥{{ discountAmount.toFixed(2) }}</span></div>
            <div class="summary-total"><span>应付总额</span><span class="total-price">¥{{ payAmount.toFixed(2) }}</span></div>
            <el-button type="danger" size="large" class="checkout-btn" :disabled="selectedCount === 0" @click="goCheckout">去结算 ({{ selectedCount }})</el-button>
            <el-button type="primary" plain size="large" class="continue-btn" @click="$router.push('/products')">继续购物</el-button>
          </div>
        </div>
      </div>
      <div class="empty-cart" v-else>
        <el-icon size="80" color="#ddd"><ShoppingCart /></el-icon>
        <p>购物车空空如也~</p>
        <el-button type="primary" size="large" @click="$router.push('/products')">去逛逛</el-button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getCart, updateCart as updateCartApi, deleteCart } from '@/api/cart'
const router = useRouter()
const cartList = ref([])
const fetchCart = async () => { try { const res = await getCart(); cartList.value = (res.data?.list || res.data || []).map(i => ({ ...i, selected: true })) } catch (e) { console.error(e) } }
const selectAll = computed({ get: () => cartList.value.length > 0 && cartList.value.every(i => i.selected), set: (v) => { cartList.value.forEach(i => i.selected = v) } })
const selectedItems = computed(() => cartList.value.filter(i => i.selected))
const selectedCount = computed(() => selectedItems.value.reduce((sum, i) => sum + (i.quantity || 1), 0))
const totalAmount = computed(() => selectedItems.value.reduce((sum, i) => sum + (i.price || 0) * (i.quantity || 1), 0))
const discountAmount = ref(0)
const payAmount = computed(() => Math.max(0, totalAmount.value - discountAmount.value))
const toggleSelectAll = () => { cartList.value.forEach(i => i.selected = selectAll.value) }
const updateCart = async (item) => { try { if (item?.id) await updateCartApi(item.id, { quantity: item.quantity }) } catch (e) { console.error(e) } }
const removeItem = async (id) => { try { await ElMessageBox.confirm('确定删除该商品？', '提示', { type: 'warning' }); await deleteCart(id); ElMessage.success('已删除'); fetchCart() } catch (e) {} }
const goDetail = (id) => router.push(`/product/${id}`)
const goCheckout = () => router.push('/checkout')
onMounted(fetchCart)
</script>
<style scoped>
.cart-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.page-title { font-size: 22px; color: #333; margin: 0 0 20px 0; }
.cart-count { font-size: 14px; color: #999; font-weight: normal; }
.cart-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.cart-left { flex: 1; background: #fff; border-radius: 8px; padding: 16px; }
.cart-header { display: grid; grid-template-columns: 40px 80px 1fr 100px 120px 100px 60px; gap: 12px; padding: 12px; background: #fafafa; border-radius: 4px; font-size: 13px; color: #666; font-weight: bold; }
.cart-item { display: grid; grid-template-columns: 40px 80px 1fr 100px 120px 100px 60px; gap: 12px; padding: 16px 12px; border-bottom: 1px solid #f0f0f0; align-items: center; }
.cart-item:last-child { border-bottom: none; }
.item-image { width: 80px; height: 80px; border-radius: 4px; overflow: hidden; cursor: pointer; }
.item-image img { width: 100%; height: 100%; object-fit: cover; }
.item-name { font-size: 14px; color: #333; cursor: pointer; margin-bottom: 4px; }
.item-name:hover { color: #e6a23c; }
.item-spec { font-size: 12px; color: #999; }
.item-price { font-size: 14px; color: #333; }
.item-subtotal { font-size: 16px; color: #f56c6c; font-weight: bold; }
.cart-right { width: 300px; flex-shrink: 0; position: sticky; top: 20px; }
.order-summary { background: #fff; border-radius: 8px; padding: 20px; }
.order-summary h3 { font-size: 16px; color: #333; margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
.summary-row { display: flex; justify-content: space-between; font-size: 14px; color: #666; margin-bottom: 12px; }
.summary-row.discount { color: #67c23a; }
.summary-row .free { color: #67c23a; }
.summary-total { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-top: 1px solid #f0f0f0; margin-top: 8px; }
.summary-total span { font-size: 14px; color: #333; }
.total-price { font-size: 24px !important; color: #f56c6c !important; font-weight: bold; }
.checkout-btn { width: 100%; margin-top: 16px; background: #f56c6c; border-color: #f56c6c; }
.continue-btn { width: 100%; margin-top: 10px; }
.empty-cart { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-cart p { color: #999; margin: 16px 0; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .cart-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .page-title { font-size: 18px; margin-bottom: 12px; }
  .cart-count { font-size: 13px; }
  
  /* 两栏布局改单列 */
  .cart-wrapper { flex-direction: column; gap: 10px; }
  .cart-left { width: 100%; padding: 10px; border-radius: 6px; }
  .cart-right { width: 100%; position: static; }
  
  /* 隐藏PC端表格表头 */
  .cart-header { display: none !important; }
  
  /* 商品项改为卡片式flex布局 */
  .cart-item {
    display: flex !important;
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px 8px !important;
    border-bottom: 1px solid #f0f0f0;
    align-items: flex-start;
  }
  .cart-item:last-child { border-bottom: none; }
  
  /* checkbox */
  .cart-item .el-checkbox { margin-right: 0; align-self: center; }
  
  /* 商品图片 */
  .item-image { width: 80px; height: 80px; flex-shrink: 0; border-radius: 6px; }
  
  /* 商品信息 */
  .item-info { flex: 1; min-width: 0; }
  .item-name { font-size: 13px; line-height: 1.4; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .item-spec { font-size: 11px; color: #999; }
  
  /* 单价 - 手机端显示在信息下方 */
  .item-price { width: 100%; font-size: 13px; color: #666; padding-left: 24px; }
  .item-price::before { content: "单价: "; color: #999; }
  
  /* 数量选择器 */
  .item-quantity { padding-left: 24px; }
  .item-quantity .el-input-number { transform: scale(0.9); transform-origin: left center; }
  
  /* 小计 */
  .item-subtotal { font-size: 15px; color: #f56c6c; font-weight: bold; padding-left: 24px; align-self: center; }
  .item-subtotal::before { content: "小计: "; color: #999; font-size: 12px; font-weight: normal; }
  
  /* 操作按钮 */
  .item-action { margin-left: auto; align-self: center; }
  .item-action .el-button { font-size: 12px; padding: 4px 8px; }
  
  /* 订单摘要 */
  .order-summary { padding: 14px; border-radius: 6px; }
  .order-summary h3 { font-size: 15px; margin-bottom: 10px; padding-bottom: 8px; }
  .summary-row { font-size: 13px; margin-bottom: 8px; }
  .summary-total { padding: 12px 0; margin-top: 6px; }
  .summary-total span { font-size: 13px; }
  .total-price { font-size: 20px !important; }
  .checkout-btn { margin-top: 12px; font-size: 14px !important; padding: 10px !important; }
  .continue-btn { margin-top: 8px; font-size: 13px !important; }
  
  /* 空购物车 */
  .empty-cart { padding: 40px 16px; border-radius: 6px; }
  .empty-cart .el-icon { font-size: 60px !important; }
  .empty-cart p { font-size: 13px; margin: 12px 0; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .cart-left { padding: 8px; }
  .item-image { width: 70px; height: 70px; }
  .item-name { font-size: 12px; }
  .item-price, .item-quantity, .item-subtotal { padding-left: 0; }
  .page-title { font-size: 16px; }
}
</style>
