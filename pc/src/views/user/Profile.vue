<template>
  <div class="profile-page">
    <div class="container">
      <div class="profile-wrapper">
        <!-- 左侧菜单（PC端） -->
        <aside class="profile-sidebar" v-if="!isMobile">
          <div class="user-card">
            <el-avatar :size="64">{{ userInfo?.nickname?.charAt(0) || 'U' }}</el-avatar>
            <div class="user-name">{{ userInfo?.nickname || t('profile.user') }}</div>
            <div class="user-level" v-if="userInfo?.level_name">{{ userInfo.level_name }}</div>
          </div>
          <div class="menu-list">
            <div class="menu-group">
              <div class="group-title">{{ t('profile.myOrders') }}</div>
              <div class="menu-item" :class="{active: activeMenu === 'orders'}" @click="activeMenu = 'orders'">
                <el-icon><List /></el-icon> {{ t('profile.allOrders') }}
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'after_sale'}" @click="activeMenu = 'after_sale'">
                <el-icon><RefreshLeft /></el-icon> {{ t('profile.afterSale') }}
              </div>
            </div>
            <div class="menu-group">
              <div class="group-title">{{ t('profile.myAssets') }}</div>
              <div class="menu-item" :class="{active: activeMenu === 'coupons'}" @click="goMenu('/coupons')">
                <el-icon><Ticket /></el-icon> {{ t('profile.coupons') }}
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'points'}" @click="activeMenu = 'points'">
                <el-icon><Medal /></el-icon> {{ t('profile.points') }}
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'balance'}" @click="activeMenu = 'balance'">
                <el-icon><Wallet /></el-icon> {{ t('profile.balance') }}
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'collects'}" @click="goMenu('/collects')">
                <el-icon><Star /></el-icon> {{ t('profile.collects') }}
              </div>
            </div>
            <div class="menu-group">
              <div class="group-title">{{ t('profile.promotion') }}</div>
              <div class="menu-item" :class="{active: activeMenu === 'distribution'}" @click="goMenu('/distribution/apply')">
                <el-icon><Money /></el-icon> {{ t('profile.applyDistribution') }}
              </div>
            </div>
            <div class="menu-group">
              <div class="group-title">{{ t('profile.accountSettings') }}</div>
              <div class="menu-item" :class="{active: activeMenu === 'profile'}" @click="activeMenu = 'profile'">
                <el-icon><User /></el-icon> {{ t('profile.personalInfo') }}
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'address'}" @click="goMenu('/address')">
                <el-icon><Location /></el-icon> {{ t('profile.address') }}
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'password'}" @click="activeMenu = 'password'">
                <el-icon><Lock /></el-icon> {{ t('profile.changePassword') }}
              </div>
            </div>
            <div class="menu-group">
              <div class="menu-item logout" @click="handleLogout">
                <el-icon><SwitchButton /></el-icon> {{ t('profile.logout') }}
              </div>
            </div>
          </div>
        </aside>

        <!-- 右侧内容 -->
        <div class="profile-content">
          <!-- 移动端用户信息卡 -->
          <div class="mobile-user-card" v-if="isMobile">
            <div class="mobile-user-info">
              <el-avatar :size="56">{{ userInfo?.nickname?.charAt(0) || 'U' }}</el-avatar>
              <div class="mobile-user-detail">
                <div class="mobile-user-name">{{ userInfo?.nickname || t('profile.user') }}</div>
                <div class="mobile-user-level" v-if="userInfo?.level_name">{{ userInfo.level_name }}</div>
              </div>
            </div>
          </div>

          <!-- 数据概览 -->
          <div class="stats-card" v-if="activeMenu === 'profile'">
            <div class="stats-grid">
              <div class="stat-item" @click="goMenu('/orders')">
                <div class="stat-icon" style="background:#fef0f0;color:#f56c6c"><el-icon><List /></el-icon></div>
                <div class="stat-info"><div class="stat-value">{{ stats.orders || 0 }}</div><div class="stat-label">{{ t('profile.allOrders') }}</div></div>
              </div>
              <div class="stat-item" @click="goMenu('/coupons')">
                <div class="stat-icon" style="background:#fdf6ec;color:#e6a23c"><el-icon><Ticket /></el-icon></div>
                <div class="stat-info"><div class="stat-value">{{ stats.coupons || 0 }}</div><div class="stat-label">{{ t('profile.coupons') }}</div></div>
              </div>
              <div class="stat-item">
                <div class="stat-icon" style="background:#f0f9eb;color:#67c23a"><el-icon><Medal /></el-icon></div>
                <div class="stat-info"><div class="stat-value">{{ stats.points || 0 }}</div><div class="stat-label">{{ t('profile.points') }}</div></div>
              </div>
              <div class="stat-item">
                <div class="stat-icon" style="background:#ecf5ff;color:#409eff"><el-icon><Wallet /></el-icon></div>
                <div class="stat-info"><div class="stat-value">¥{{ (stats.balance || 0).toFixed(2) }}</div><div class="stat-label">{{ t('profile.balance') }}</div></div>
              </div>
            </div>
          </div>

          <!-- 订单快捷入口 -->
          <div class="order-quick-card" v-if="activeMenu === 'profile'">
            <h3 class="card-title">{{ t('profile.myOrders') }}</h3>
            <div class="order-quick-grid">
              <div class="order-quick-item" @click="goMenu('/orders?status=pending')">
                <el-icon :size="28" color="#ff6b00"><Money /></el-icon>
                <span>{{ t('profile.pendingPayment') }}</span>
              </div>
              <div class="order-quick-item" @click="goMenu('/orders?status=paid')">
                <el-icon :size="28" color="#ff9f1a"><Box /></el-icon>
                <span>{{ t('profile.pendingShipment') }}</span>
              </div>
              <div class="order-quick-item" @click="goMenu('/orders?status=shipped')">
                <el-icon :size="28" color="#1890ff"><Van /></el-icon>
                <span>{{ t('profile.pendingReceipt') }}</span>
              </div>
              <div class="order-quick-item" @click="goMenu('/orders?status=completed')">
                <el-icon :size="28" color="#07c160"><CircleCheck /></el-icon>
                <span>{{ t('profile.completed') }}</span>
              </div>
              <div class="order-quick-item" @click="activeMenu = 'after_sale'">
                <el-icon :size="28" color="#f56c6c"><RefreshLeft /></el-icon>
                <span>{{ t('profile.afterSale') }}</span>
              </div>
            </div>
          </div>

          <!-- 个人资料 -->
          <div class="content-card" v-if="activeMenu === 'profile'">
            <h3 class="card-title">{{ t('profile.personalInfo') }}</h3>
            <el-form :model="profileForm" label-width="100px" class="profile-form">
              <el-form-item :label="t('profile.nickname')">
                <el-input v-model="profileForm.nickname" :placeholder="t('profile.inputNickname')" />
              </el-form-item>
              <el-form-item :label="t('profile.phone')">
                <el-input v-model="profileForm.phone" disabled />
              </el-form-item>
              <el-form-item :label="t('profile.email')">
                <el-input v-model="profileForm.email" :placeholder="t('profile.inputEmail')" />
              </el-form-item>
              <el-form-item :label="t('profile.gender')">
                <el-radio-group v-model="profileForm.gender">
                  <el-radio :value="1">{{ t('profile.male') }}</el-radio>
                  <el-radio :value="2">{{ t('profile.female') }}</el-radio>
                  <el-radio :value="0">{{ t('profile.secret') }}</el-radio>
                </el-radio-group>
              </el-form-item>
              <el-form-item :label="t('profile.birthday')">
                <el-date-picker v-model="profileForm.birthday" type="date" :placeholder="t('profile.selectBirthday')" style="width:100%" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="saveProfile">{{ t('profile.save') }}</el-button>
              </el-form-item>
            </el-form>
          </div>

          <!-- 订单列表 -->
          <div class="content-card" v-if="activeMenu === 'orders'">
            <h3 class="card-title">{{ t('profile.allOrders') }}</h3>
            <div class="order-list" v-if="orders.length">
              <div class="order-item" v-for="order in orders" :key="order.id">
                <div class="order-header">
                  <span class="order-no">{{ t('profile.orderNo') }}: {{ order.order_no }}</span>
                  <span class="order-status" :class="'status-' + order.status">{{ getOrderStatusText(order.status) }}</span>
                </div>
                <div class="order-goods" v-for="item in order.items" :key="item.id">
                  <img :src="getImageUrl(item.main_image || item.image)" class="goods-img" />
                  <div class="goods-info">
                    <div class="goods-name">{{ item.name }}</div>
                    <div class="goods-spec" v-if="item.specs">{{ item.specs }}</div>
                  </div>
                  <div class="goods-price">¥{{ Number(item.price).toFixed(2) }} x{{ item.quantity }}</div>
                </div>
                <div class="order-footer">
                  <span class="order-total">{{ t('profile.total') }}: <b>¥{{ Number(order.total_amount).toFixed(2) }}</b></span>
                  <div class="order-actions">
                    <el-button size="small" v-if="order.status === 'pending'" type="primary" @click="payOrder(order)">{{ t('profile.payNow') }}</el-button>
                    <el-button size="small" v-if="order.status === 'shipped'" type="success" @click="confirmReceipt(order)">{{ t('profile.confirmReceipt') }}</el-button>
                    <el-button size="small" v-if="order.status === 'completed'" @click="goReview(order)">{{ t('profile.review') }}</el-button>
                    <el-button size="small" @click="viewOrder(order)">{{ t('profile.viewDetail') }}</el-button>
                  </div>
                </div>
              </div>
            </div>
            <div class="empty-state" v-else>
              <el-icon :size="48" color="#ddd"><List /></el-icon>
              <p>{{ t('profile.noOrders') }}</p>
              <el-button type="primary" @click="$router.push('/products')">{{ t('profile.goShopping') }}</el-button>
            </div>
          </div>

          <!-- 其他菜单内容（简化显示） -->
          <div class="content-card" v-if="['after_sale', 'points', 'balance', 'password'].includes(activeMenu)">
            <h3 class="card-title">{{ getMenuTitle(activeMenu) }}</h3>
            <div class="empty-state">
              <el-icon :size="48" color="#ddd"><Setting /></el-icon>
              <p>{{ t('profile.underConstruction') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'

const { t } = useI18n()
const router = useRouter()

const isMobile = ref(false)
const activeMenu = ref('profile')
const userInfo = ref(null)
const stats = ref({ orders: 0, coupons: 0, points: 0, balance: 0 })
const orders = ref([])
const profileForm = ref({
  nickname: '',
  phone: '',
  email: '',
  gender: 0,
  birthday: ''
})

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  loadUserInfo()
  loadOrders()
})

const getImageUrl = (url) => {
  if (!url) return 'https://picsum.photos/80/80'
  if (url.startsWith('http')) return url
  return 'https://mall.tllos.com' + (url.startsWith('/') ? '' : '/') + url
}

const loadUserInfo = async () => {
  try {
    const userStr = localStorage.getItem('userInfo')
    if (userStr) {
      userInfo.value = JSON.parse(userStr)
      profileForm.value = {
        ...profileForm.value,
        nickname: userInfo.value.nickname || '',
        phone: userInfo.value.phone || ''
      }
    }
  } catch (e) {
    console.error(e)
  }
}

const loadOrders = async () => {
  try {
    orders.value = []
  } catch (e) {
    console.error(e)
  }
}

const goMenu = (path) => {
  router.push(path)
}

const getMenuTitle = (menu) => {
  const titles = {
    after_sale: t('profile.afterSale'),
    points: t('profile.points'),
    balance: t('profile.balance'),
    password: t('profile.changePassword')
  }
  return titles[menu] || ''
}

const getOrderStatusText = (status) => {
  const statusMap = {
    pending: t('profile.pendingPayment'),
    paid: t('profile.pendingShipment'),
    shipped: t('profile.pendingReceipt'),
    completed: t('profile.completed'),
    cancelled: t('profile.cancelled')
  }
  return statusMap[status] || status
}

const saveProfile = () => {
  ElMessage.success(t('profile.saveSuccess'))
}

const handleLogout = async () => {
  try {
    await ElMessageBox.confirm(t('profile.confirmLogout'), t('profile.logout'), { type: 'warning' })
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
    ElMessage.success(t('profile.logoutSuccess'))
    router.push('/login')
  } catch (e) {
    // 取消
  }
}

const payOrder = (order) => {
  ElMessage.info(t('profile.payFeature'))
}

const confirmReceipt = (order) => {
  ElMessage.success(t('profile.confirmSuccess'))
}

const goReview = (order) => {
  ElMessage.info(t('profile.reviewFeature'))
}

const viewOrder = (order) => {
  router.push('/order/' + order.id)
}
</script>

<style scoped>
.profile-page {
  min-height: calc(100vh - 200px);
  padding: 20px 0;
}

.profile-wrapper {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

/* 左侧菜单 */
.profile-sidebar {
  width: 220px;
  flex-shrink: 0;
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  overflow: hidden;
  position: sticky;
  top: 20px;
}

.user-card {
  padding: 24px 20px;
  text-align: center;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
  color: #fff;
}
.user-card .el-avatar {
  margin-bottom: 12px;
  border: 2px solid rgba(255,255,255,.3);
}
.user-name {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 4px;
}
.user-level {
  font-size: 12px;
  opacity: .9;
}

.menu-list {
  padding: 12px 0;
}
.menu-group {
  margin-bottom: 8px;
}
.group-title {
  padding: 8px 20px;
  font-size: 12px;
  color: var(--color-text-placeholder);
  font-weight: 600;
  text-transform: uppercase;
}
.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 20px;
  font-size: 14px;
  color: var(--color-text-regular);
  cursor: pointer;
  transition: all var(--transition-fast);
}
.menu-item:hover {
  background: var(--color-bg-hover);
  color: var(--color-primary);
}
.menu-item.active {
  background: var(--color-primary-bg);
  color: var(--color-primary);
  font-weight: 500;
  border-right: 3px solid var(--color-primary);
}
.menu-item.logout {
  color: var(--color-danger);
}
.menu-item.logout:hover {
  background: #fff1f0;
}

/* 右侧内容 */
.profile-content {
  flex: 1;
  min-width: 0;
}

/* 移动端用户卡 */
.mobile-user-card {
  display: none;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
  border-radius: var(--radius-md);
  padding: 20px;
  margin-bottom: 16px;
  color: #fff;
}
.mobile-user-info {
  display: flex;
  align-items: center;
  gap: 16px;
}
.mobile-user-detail {
  flex: 1;
}
.mobile-user-name {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
}
.mobile-user-level {
  font-size: 12px;
  opacity: .9;
}

/* 数据概览 */
.stats-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 20px;
  margin-bottom: 16px;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 8px;
  border-radius: var(--radius-sm);
  transition: background var(--transition-fast);
}
.stat-item:hover {
  background: var(--color-bg-hover);
}
.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon .el-icon {
  font-size: 22px;
}
.stat-info {
  flex: 1;
  min-width: 0;
}
.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-primary);
  line-height: 1.2;
}
.stat-label {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 2px;
}

/* 订单快捷入口 */
.order-quick-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 20px;
  margin-bottom: 16px;
}
.card-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 16px;
  color: var(--color-text-primary);
}
.order-quick-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
}
.order-quick-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 8px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-size: 13px;
  color: var(--color-text-secondary);
}
.order-quick-item:hover {
  background: var(--color-primary-bg);
  color: var(--color-primary);
}

/* 内容卡片 */
.content-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: 20px;
  margin-bottom: 16px;
}
.profile-form {
  max-width: 500px;
}

/* 订单列表 */
.order-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.order-item {
  border: 1px solid var(--color-border-light);
  border-radius: var(--radius-md);
  overflow: hidden;
}
.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--color-bg-hover);
  font-size: 13px;
}
.order-no {
  color: var(--color-text-secondary);
}
.order-status {
  font-weight: 600;
}
.status-pending { color: var(--color-warning); }
.status-paid { color: var(--color-info); }
.status-shipped { color: var(--color-primary); }
.status-completed { color: var(--color-success); }
.status-cancelled { color: var(--color-text-placeholder); }

.order-goods {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border-light);
}
.goods-img {
  width: 60px;
  height: 60px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  flex-shrink: 0;
}
.goods-info {
  flex: 1;
  min-width: 0;
}
.goods-name {
  font-size: 14px;
  color: var(--color-text-regular);
  margin-bottom: 4px;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.goods-spec {
  font-size: 12px;
  color: var(--color-text-placeholder);
}
.goods-price {
  font-size: 14px;
  color: var(--color-text-secondary);
  flex-shrink: 0;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
}
.order-total {
  font-size: 14px;
  color: var(--color-text-secondary);
}
.order-total b {
  color: var(--color-danger);
  font-size: 16px;
}
.order-actions {
  display: flex;
  gap: 8px;
}

/* 空状态 */
.empty-state {
  padding: 40px 20px;
  text-align: center;
  color: var(--color-text-placeholder);
}
.empty-state p {
  margin: 12px 0 20px;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .profile-page {
    padding: 12px 0;
  }
  .profile-wrapper {
    flex-direction: column;
    gap: 0;
  }
  .profile-sidebar {
    display: none;
  }
  .mobile-user-card {
    display: block;
  }
  .stats-card {
    padding: 16px;
  }
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  .stat-value {
    font-size: 18px;
  }
  .order-quick-card {
    padding: 16px;
  }
  .order-quick-grid {
    grid-template-columns: repeat(5, 1fr);
    gap: 4px;
  }
  .order-quick-item {
    padding: 12px 4px;
    font-size: 11px;
  }
  .content-card {
    padding: 16px;
  }
  .profile-form {
    max-width: 100%;
  }
  .order-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  .order-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .order-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
