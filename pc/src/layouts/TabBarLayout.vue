<template>
  <div class="tabbar-layout">
    <div class="content"><router-view /></div>
    <div class="tabbar">
      <router-link to="/home" class="tab-item" active-class="active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>{{ t('common.home') }}</span>
      </router-link>
      <router-link to="/category" class="tab-item" active-class="active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        <span>{{ t('common.category') }}</span>
      </router-link>
      <router-link to="/cart" class="tab-item" active-class="active">
        <div class="cart-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
          <span v-if="cartStore.count > 0" class="badge">{{ cartStore.count > 99 ? '99+' : cartStore.count }}</span>
        </div>
        <span>{{ t('common.cart') }}</span>
      </router-link>
      <router-link to="/user" class="tab-item" active-class="active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>{{ t('common.user') }}</span>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
const { t } = useI18n()
const cartStore = useCartStore()
</script>

<style scoped>
.tabbar-layout { display: flex; flex-direction: column; height: 100vh; }
.content { flex: 1; overflow-y: auto; padding-bottom: 10px; }
.tabbar { display: flex; background: #fff; border-top: 1px solid var(--border); padding: 6px 0; padding-bottom: calc(6px + env(safe-area-inset-bottom)); }
.tab-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px; color: var(--text-secondary); font-size: 11px; text-decoration: none; }
.tab-item svg { width: 22px; height: 22px; }
.tab-item.active { color: var(--primary); }
.cart-icon { position: relative; }
.badge { position: absolute; top: -4px; right: -8px; background: var(--danger); color: #fff; font-size: 10px; min-width: 16px; height: 16px; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }
</style>
