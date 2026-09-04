<template>
  <div class="user-page">
    <div class="user-header">
      <div v-if="userStore.userInfo" class="user-info">
        <div class="avatar">{{ userStore.userInfo.nickname?.charAt(0) || 'U' }}</div>
        <div class="user-detail">
          <div class="nickname">{{ userStore.userInfo.nickname }}</div>
          <div class="mobile">{{ userStore.userInfo.mobile }}</div>
        </div>
      </div>
      <div v-else class="login-prompt" @click="$router.push('/login')">
        <div class="avatar">👤</div>
        <span>{{ t('user.loginFirst') }}</span>
      </div>
      <div class="lang-switch" @click="switchLang">{{ locale === 'zh' ? 'EN' : '中文' }}</div>
    </div>

    <div class="order-section card">
      <div class="section-title">
        <span>{{ t('user.orders') }}</span>
        <span class="more" @click="$router.push('/order')">全部订单 ></span>
      </div>
      <div class="order-tabs">
        <div v-for="tab in orderTabs" :key="tab.key" class="order-tab" @click="goOrder(tab.key)">
          <div class="order-icon">{{ tab.icon }}</div>
          <span>{{ tab.name }}</span>
        </div>
      </div>
    </div>

    <div class="menu-section card">
      <div v-for="menu in menus" :key="menu.name" class="menu-item" @click="handleMenu(menu)">
        <span class="menu-icon">{{ menu.icon }}</span>
        <span class="menu-name">{{ t(menu.name) }}</span>
        <span class="menu-arrow">></span>
      </div>
    </div>

    <div v-if="userStore.userInfo" class="logout-section">
      <button class="logout-btn" @click="handleLogout">{{ t('common.logout') }}</button>
    </div>
  </div>
</template>
<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
const { t, locale } = useI18n()
const router = useRouter()
const userStore = useUserStore()

const orderTabs = [
  { key: 'pending', name: '待付款', icon: '💰' },
  { key: 'paid', name: '待发货', icon: '📦' },
  { key: 'shipped', name: '待收货', icon: '🚚' },
  { key: 'completed', name: '已完成', icon: '✅' },
  { key: 'refund', name: '退款', icon: '↩️' }
]

const menus = [
  { name: 'user.address', icon: '📍', path: '' },
  { name: 'user.coupons', icon: '🎫', path: '' },
  { name: 'user.favorites', icon: '❤️', path: '' },
  { name: 'user.history', icon: '👁️', path: '' },
  { name: 'user.customerService', icon: '💬', path: '' },
  { name: 'user.settings', icon: '⚙️', path: '' },
  { name: 'user.about', icon: 'ℹ️', path: '' }
]

const goOrder = status => router.push('/order')
const handleMenu = menu => { if (menu.path) router.push(menu.path); else alert('功能开发中') }
const switchLang = () => {
  const newLang = locale.value === 'zh' ? 'en' : 'zh'
  locale.value = newLang
  localStorage.setItem('tllos_locale', newLang)
}
const handleLogout = async () => {
  await userStore.logout()
  router.push('/home')
}
</script>
<style scoped>
.user-page { padding-bottom: 20px; }
.user-header { background: linear-gradient(135deg, var(--primary), var(--primary-light)); padding: 40px 20px 30px; display: flex; align-items: center; gap: 12px; position: relative; }
.user-info { display: flex; align-items: center; gap: 12px; flex: 1; }
.avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; }
.user-detail { color: #fff; }
.nickname { font-size: 18px; font-weight: 500; margin-bottom: 4px; }
.mobile { font-size: 13px; opacity: 0.9; }
.login-prompt { display: flex; align-items: center; gap: 12px; color: #fff; cursor: pointer; flex: 1; }
.lang-switch { background: rgba(255,255,255,0.2); color: #fff; padding: 6px 14px; border-radius: 16px; font-size: 12px; cursor: pointer; }
.order-section { margin: -16px 10px 10px; position: relative; z-index: 1; }
.section-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 15px; font-weight: 500; }
.more { font-size: 12px; color: var(--text-secondary); }
.order-tabs { display: flex; justify-content: space-around; }
.order-tab { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.order-icon { font-size: 24px; }
.order-tab span { font-size: 12px; color: var(--text); }
.menu-section { margin: 0 10px 10px; padding: 0; }
.menu-item { display: flex; align-items: center; padding: 14px 12px; border-bottom: 1px solid var(--border); }
.menu-item:last-child { border-bottom: none; }
.menu-icon { font-size: 18px; margin-right: 12px; }
.menu-name { flex: 1; font-size: 14px; }
.menu-arrow { color: var(--text-secondary); font-size: 14px; }
.logout-section { padding: 20px 10px; }
.logout-btn { width: 100%; padding: 12px; background: #fff; border: 1px solid var(--border); border-radius: 8px; color: var(--danger); font-size: 14px; cursor: pointer; }
</style>
