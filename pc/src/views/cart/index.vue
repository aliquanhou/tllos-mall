<template>
  <div class="cart-page">
    <div class="cart-header">
      <span class="title">购物车 ({{ totalCount }})</span>
      <span class="edit-btn" @click="isEdit = !isEdit">{{ isEdit ? '完成' : '管理' }}</span>
    </div>

    <div class="cart-list" v-if="cartList.length">
      <div class="cart-item" v-for="item in cartList" :key="item.id">
        <div class="checkbox" :class="{checked: item.selected}" @click="toggleSelect(item)">
          <span v-if="item.selected">✓</span>
        </div>
        <img :src="item.image" :alt="item.name" class="item-image" @click="goDetail(item.product_id)" />
        <div class="item-info">
          <div class="item-name">{{ item.name }}</div>
          <div class="item-sku" v-if="item.spec_text">{{ item.spec_text }}</div>
          <div class="item-bottom">
            <span class="item-price">¥{{ item.price }}</span>
            <div class="quantity-control">
              <button class="qty-btn" @click="changeQty(item, -1)" :disabled="item.quantity <= 1">-</button>
              <span class="qty-num">{{ item.quantity }}</span>
              <button class="qty-btn" @click="changeQty(item, 1)">+</button>
            </div>
          </div>
        </div>
        <div class="delete-btn" v-if="isEdit" @click="deleteItem(item)">删除</div>
      </div>
    </div>

    <div class="empty-cart" v-else>
      <div class="empty-icon">🛒</div>
      <div class="empty-text">购物车空空如也</div>
      <button class="go-shop-btn" @click="goHome">去逛逛</button>
    </div>

    <div class="cart-bottom" v-if="cartList.length">
      <div class="select-all" @click="toggleSelectAll">
        <div class="checkbox" :class="{checked: allSelected}"><span v-if="allSelected">✓</span></div>
        <span>全选</span>
      </div>
      <div class="total-info">
        <span class="total-label">合计:</span>
        <span class="total-price">¥{{ totalAmount }}</span>
      </div>
      <button class="checkout-btn" @click="checkout" :disabled="selectedCount === 0">
        {{ isEdit ? '删除' : `结算(${selectedCount})` }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getCart, updateCart, deleteCart, selectAllCart, clearCart } from '@/api/cart'

const router = useRouter()
const cartList = ref([])
const isEdit = ref(false)

const fetchCart = async () => {
  try {
    const res = await getCart()
    cartList.value = res.data.list || []
  } catch (e) { console.error(e) }
}

const totalCount = computed(() => cartList.value.reduce((sum, i) => sum + i.quantity, 0))
const selectedItems = computed(() => cartList.value.filter(i => i.selected && i.available))
const selectedCount = computed(() => selectedItems.value.reduce((sum, i) => sum + i.quantity, 0))
const totalAmount = computed(() => selectedItems.value.reduce((sum, i) => sum + Number(i.subtotal), 0).toFixed(2))
const allSelected = computed(() => cartList.value.length > 0 && cartList.value.every(i => i.selected))

const toggleSelect = async (item) => {
  item.selected = item.selected ? 0 : 1
  try { await updateCart(item.id, { selected: item.selected }) } catch (e) { console.error(e) }
}

const toggleSelectAll = async () => {
  const newVal = allSelected.value ? 0 : 1
  cartList.value.forEach(i => i.selected = newVal)
  try { await selectAllCart({ selected: newVal }) } catch (e) { console.error(e) }
}

const changeQty = async (item, delta) => {
  const newQty = item.quantity + delta
  if (newQty < 1) return
  if (newQty > item.stock) { alert('库存不足'); return }
  item.quantity = newQty
  try { await updateCart(item.id, { quantity: newQty }) } catch (e) { console.error(e) }
}

const deleteItem = async (item) => {
  if (!confirm('确定删除该商品？')) return
  try {
    await deleteCart(item.id)
    cartList.value = cartList.value.filter(i => i.id !== item.id)
  } catch (e) { console.error(e) }
}

const checkout = () => {
  if (isEdit) {
    if (!confirm('确定删除选中商品？')) return
    selectedItems.value.forEach(async item => { await deleteCart(item.id) })
    fetchCart()
    return
  }
  if (selectedCount.value === 0) { alert('请选择商品'); return }
  alert('即将跳转到结算页')
}

const goDetail = id => router.push(`/product/${id}`)
const goHome = () => router.push('/')

onMounted(fetchCart)
</script>

<style scoped>
.cart-page { padding-bottom: 70px; background: #f5f5f5; min-height: 100vh; }
.cart-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #fff; }
.title { font-size: 17px; font-weight: bold; }
.edit-btn { font-size: 14px; color: #666; }
.cart-list { padding: 10px 15px; }
.cart-item { display: flex; align-items: center; background: #fff; border-radius: 8px; padding: 12px; margin-bottom: 10px; gap: 10px; }
.checkbox { width: 20px; height: 20px; border: 2px solid #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff; flex-shrink: 0; }
.checkbox.checked { background: #ff4444; border-color: #ff4444; }
.item-image { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.item-info { flex: 1; min-width: 0; }
.item-name { font-size: 14px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.item-sku { font-size: 12px; color: #999; margin-top: 4px; }
.item-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
.item-price { font-size: 16px; color: #ff4444; font-weight: bold; }
.quantity-control { display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
.qty-btn { width: 28px; height: 28px; border: none; background: #f5f5f5; font-size: 16px; color: #666; }
.qty-num { width: 36px; text-align: center; font-size: 14px; }
.delete-btn { color: #ff4444; font-size: 13px; padding: 5px 10px; }
.empty-cart { text-align: center; padding: 80px 20px; }
.empty-icon { font-size: 60px; margin-bottom: 16px; }
.empty-text { color: #999; font-size: 15px; margin-bottom: 20px; }
.go-shop-btn { padding: 10px 30px; background: #ff4444; color: #fff; border: none; border-radius: 20px; font-size: 14px; }
.cart-bottom { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; align-items: center; padding: 10px 15px; border-top: 1px solid #eee; gap: 10px; }
.select-all { display: flex; align-items: center; gap: 6px; font-size: 14px; color: #666; }
.total-info { flex: 1; text-align: right; }
.total-label { font-size: 14px; color: #666; }
.total-price { font-size: 18px; color: #ff4444; font-weight: bold; margin-left: 4px; }
.checkout-btn { padding: 10px 24px; background: #ff4444; color: #fff; border: none; border-radius: 20px; font-size: 14px; }
.checkout-btn:disabled { background: #ccc; }
</style>
