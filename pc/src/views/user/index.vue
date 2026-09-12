<template>
  <div class="mine-page">
    <!-- 用户头部卡片 -->
    <div class="user-header">
      <div v-if="userStore.userInfo" class="user-info" @click="$router.push('/user/profile')">
        <div class="avatar">
          <img v-if="userStore.userInfo.avatar" :src="userStore.userInfo.avatar" />
          <span v-else>{{ userStore.userInfo.nickname?.charAt(0) || 'U' }}</span>
        </div>
        <div class="user-detail">
          <div class="nickname">{{ userStore.userInfo.nickname || '未设置昵称' }}</div>
          <div class="mobile">{{ userStore.userInfo.mobile || '' }}</div>
        </div>
        <span class="edit-arrow">编辑 ›</span>
      </div>
      <div v-else class="login-prompt" @click="$router.push('/login')">
        <div class="avatar">👤</div>
        <span>点击登录/注册</span>
      </div>
    </div>

    <!-- 资产卡片 -->
    <div v-if="userStore.userInfo" class="asset-card">
      <div class="asset-item">
        <div class="asset-value">{{ userStore.userInfo.points || 0 }}</div>
        <div class="asset-label">积分</div>
      </div>
      <div class="asset-item">
        <div class="asset-value">¥{{ userStore.userInfo.balance || '0.00' }}</div>
        <div class="asset-label">余额</div>
      </div>
      <div class="asset-item">
        <div class="asset-value">{{ userStore.userInfo.coupon_count || 0 }}</div>
        <div class="asset-label">优惠券</div>
      </div>
      <div class="asset-item">
        <div class="asset-value">{{ userStore.userInfo.favorite_count || 0 }}</div>
        <div class="asset-label">收藏</div>
      </div>
    </div>

    <!-- 我的订单 -->
    <div class="section-card">
      <div class="section-header">
        <span class="section-title">我的订单</span>
        <span class="section-more" @click="$router.push('/orders')">全部订单 ›</span>
      </div>
      <div class="order-grid">
        <div v-for="tab in orderTabs" :key="tab.key" class="order-item" @click="goOrder(tab.key)">
          <div class="order-icon">{{ tab.icon }}</div>
          <span>{{ tab.name }}</span>
        </div>
      </div>
    </div>

    <!-- 分销推广（醒目位置） -->
    <div v-if="userStore.userInfo" class="distribute-banner" @click="goDistribute">
      <div class="distribute-left">
        <div class="distribute-title">分销推广赚佣金</div>
        <div class="distribute-desc">分享商品，好友下单你拿钱</div>
      </div>
      <div class="distribute-right">
        <span class="distribute-btn">{{ isAgent ? '进入分销中心' : '申请分销' }}</span>
      </div>
    </div>

    <!-- 功能宫格 -->
    <div class="section-card">
      <div class="section-header"><span class="section-title">常用功能</span></div>
      <div class="menu-grid">
        <div v-for="menu in menus" :key="menu.name" class="menu-item" @click="handleMenu(menu)">
          <div class="menu-icon" :style="{background: menu.color}">{{ menu.icon }}</div>
          <span>{{ menu.name }}</span>
        </div>
      </div>
    </div>

    <!-- 退出登录 -->
    <div v-if="userStore.userInfo" class="logout-area">
      <button class="logout-btn" @click="handleLogout">退出登录</button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const userStore = useUserStore()
const isAgent = ref(false)

const orderTabs = [
  { key: 'pending', name: '待付款', icon: '💳' },
  { key: 'paid', name: '待发货', icon: '📦' },
  { key: 'shipped', name: '待收货', icon: '🚚' },
  { key: 'completed', name: '已完成', icon: '✅' },
  { key: 'refund', name: '退款/售后', icon: '↩️' },
]

const menus = [
  { name: '收货地址', icon: '📍', path: '/address', color: '#e6f7ff' },
  { name: '我的收藏', icon: '❤️', path: '/collects', color: '#fff0f6' },
  { name: '优惠券', icon: '🎫', path: '/coupons', color: '#fff7e6' },
  { name: '我的评价', icon: '⭐', path: '/my-reviews', color: '#f6ffed' },
  { name: '消息通知', icon: '🔔', path: '/messages', color: '#fff1f0' },
  { name: '帮助中心', icon: '💬', path: '/help', color: '#f9f0ff' },
]

const goOrder = status => router.push('/orders?status=' + status)
const handleMenu = menu => {
  if (menu.path) router.push(menu.path)
}
const goDistribute = () => {
  if (isAgent.value) {
    router.push('/distribution')
  } else {
    router.push('/distribution/apply')
  }
}
const handleLogout = async () => {
  await userStore.logout()
  router.push('/home')
}

onMounted(async () => {
  if (userStore.token) {
    try {
      await userStore.fetchProfile()
      // 查分销状态
      const res = await fetch('/api/v1/distribution/apply-status', {
        headers: { 'Authorization': 'Bearer ' + userStore.token }
      })
      const data = await res.json()
      if (data.data) isAgent.value = data.data.is_agent || false
    } catch (e) {
      console.error(e)
    }
  }
})
</script>

<style scoped>
.mine-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 20px;
}

/* 用户头部 */
.user-header {
  background: linear-gradient(135deg, #ff6a00, #ff9500);
  padding: 40px 20px 50px;
}
.user-info {
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
}
.avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255,255,255,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  color: #fff;
  overflow: hidden;
  flex-shrink: 0;
}
.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.user-detail { flex: 1; color: #fff; }
.nickname { font-size: 18px; font-weight: 600; }
.mobile { font-size: 13px; opacity: 0.85; margin-top: 4px; }
.edit-arrow { color: #fff; font-size: 13px; opacity: 0.8; }
.login-prompt {
  display: flex;
  align-items: center;
  gap: 14px;
  color: #fff;
  cursor: pointer;
  font-size: 16px;
}

/* 资产卡片 */
.asset-card {
  margin: -30px 12px 12px;
  background: #fff;
  border-radius: 12px;
  padding: 16px 0;
  display: flex;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.asset-item {
  flex: 1;
  text-align: center;
}
.asset-value {
  font-size: 20px;
  font-weight: 700;
  color: #333;
}
.asset-label {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}

/* 通用卡片 */
.section-card {
  margin: 0 12px 12px;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}
.section-more {
  font-size: 12px;
  color: #999;
  cursor: pointer;
}

/* 订单宫格 */
.order-grid {
  display: flex;
  justify-content: space-around;
}
.order-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}
.order-icon { font-size: 26px; }
.order-item span { font-size: 12px; color: #666; }

/* 分销横幅 */
.distribute-banner {
  margin: 0 12px 12px;
  background: linear-gradient(135deg, #ff4d4f, #ff7a45);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
}
.distribute-title {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
}
.distribute-desc {
  font-size: 12px;
  color: rgba(255,255,255,0.85);
  margin-top: 4px;
}
.distribute-btn {
  background: #fff;
  color: #ff4d4f;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
}

/* 功能宫格 */
.menu-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px 8px;
}
.menu-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.menu-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}
.menu-item span {
  font-size: 12px;
  color: #666;
}

/* 退出 */
.logout-area {
  margin: 20px 12px;
}
.logout-btn {
  width: 100%;
  padding: 13px;
  background: #fff;
  border: none;
  border-radius: 12px;
  color: #ff4d4f;
  font-size: 15px;
  cursor: pointer;
}
</style>
