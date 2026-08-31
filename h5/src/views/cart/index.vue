<template>
  <div class="cart-page">
    <div v-if="cartStore.items.length === 0" class="empty">
      <div class="empty-icon">🛒</div>
      <p>{{ t('cart.empty') }}</p>
      <button class="btn-primary" @click="$router.push('/home')">{{ t('cart.goShopping') }}</button>
    </div>
    <template v-else>
      <div class="cart-list">
        <div v-for="item in cartStore.items" :key="item.id" class="cart-item card">
          <div class="checkbox" :class="{ checked: item.selected }" @click="cartStore.toggleSelect(item.id)">
            <span v-if="item.selected">✓</span>
          </div>
          <div class="item-img" :style="{ background: item.color || '#eee' }"></div>
          <div class="item-info">
            <div class="item-name ellipsis-2">{{ item.name }}</div>
            <div class="item-bottom">
              <span class="price">{{ item.price }}</span>
              <div class="quantity">
                <button @click="cartStore.updateQuantity(item.id, item.quantity - 1)">-</button>
                <span>{{ item.quantity }}</span>
                <button @click="cartStore.updateQuantity(item.id, item.quantity + 1)">+</button>
              </div>
            </div>
          </div>
          <div class="delete-btn" @click="cartStore.removeItem(item.id)">🗑️</div>
        </div>
      </div>
      <div class="cart-footer">
        <div class="select-all" @click="toggleSelectAll">
          <div class="checkbox" :class="{ checked: allSelected }"><span v-if="allSelected">✓</span></div>
          <span>{{ t('common.selectAll') }}</span>
        </div>
        <div class="footer-right">
          <div class="total-text">{{ t('common.total') }}: <span class="price">¥{{ cartStore.totalPrice.toFixed(2) }}</span></div>
          <button class="btn-primary checkout-btn" @click="checkout">{{ t('common.checkout') }}({{ cartStore.selectedItems.length }})</button>
        </div>
      </div>
    </template>
  </div>
</template>
<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
const { t } = useI18n()
const cartStore = useCartStore()
const allSelected = computed(() => cartStore.items.length > 0 && cartStore.items.every(i => i.selected))
const toggleSelectAll = () => {
  const val = !allSelected.value
  cartStore.items.forEach(i => i.selected = val)
  cartStore.save()
}
const checkout = () => {
  if (cartStore.selectedItems.length === 0) { alert('请选择商品') ; return }
  alert('订单功能开发中')
}
</script>
<style scoped>
.cart-page { height: 100%; display: flex; flex-direction: column; }
.empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; }
.empty-icon { font-size: 64px; }
.empty p { color: var(--text-secondary); font-size: 14px; }
.cart-list { flex: 1; overflow-y: auto; padding: 10px; }
.cart-item { display: flex; align-items: center; gap: 10px; position: relative; }
.checkbox { width: 20px; height: 20px; border: 2px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff; flex-shrink: 0; }
.checkbox.checked { background: var(--primary); border-color: var(--primary); }
.item-img { width: 80px; height: 80px; border-radius: 8px; flex-shrink: 0; }
.item-info { flex: 1; min-width: 0; }
.item-name { font-size: 13px; line-height: 1.4; margin-bottom: 8px; }
.item-bottom { display: flex; justify-content: space-between; align-items: center; }
.quantity { display: flex; align-items: center; border: 1px solid var(--border); border-radius: 4px; }
.quantity button { width: 24px; height: 24px; border: none; background: none; font-size: 16px; cursor: pointer; color: var(--text); }
.quantity span { width: 32px; text-align: center; font-size: 13px; }
.delete-btn { position: absolute; top: 8px; right: 8px; font-size: 16px; cursor: pointer; }
.cart-footer { display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 10px 16px; border-top: 1px solid var(--border); padding-bottom: calc(10px + env(safe-area-inset-bottom)); }
.select-all { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.footer-right { display: flex; align-items: center; gap: 12px; }
.total-text { font-size: 13px; }
.checkout-btn { padding: 8px 20px; font-size: 13px; }
</style>
