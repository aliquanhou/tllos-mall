<template>
  <div class="pc-layout" :class="{ 'mobile-view': isMobile }">
    <!-- 顶部公告栏 -->
    <div class="top-notice" v-if="!isMobile">
      <div class="notice-content">
        <el-icon><Bell /></el-icon>
        <span>{{ t('home.freeShipping') }} | {{ t('home.freeReturn') }} | {{ t('home.newUserCoupon') }}</span>
      </div>
      <div class="notice-right">
        <el-dropdown @command="changeLocale">
          <span class="locale-switcher">
            <el-icon><Setting /></el-icon>
            {{ locale === 'zh' ? '中文' : 'English' }}
            <el-icon><ArrowDown /></el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="zh">中文</el-dropdown-item>
              <el-dropdown-item command="en">English</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>

    <!-- 主导航栏 -->
    <header class="main-header">
      <div class="header-container">
        <!-- 左侧：菜单按钮(移动端) + Logo -->
        <div class="header-left">
          <el-button v-if="isMobile" text class="menu-btn" @click="showMobileMenu = true">
            <el-icon :size="22"><Menu /></el-icon>
          </el-button>
          <router-link to="/" class="logo">
            <span class="logo-text">TLLOS</span>
            <span class="logo-sub">{{ t('home.mall') }}</span>
          </router-link>
        </div>

        <!-- 中间：搜索框 -->
        <div class="header-center">
          <div class="search-box" @click="goSearch">
            <el-icon><Search /></el-icon>
            <input type="text" :placeholder="t('home.searchPlaceholder')" v-model="searchKeyword" @keyup.enter="doSearch" />
            <button class="search-btn">{{ t('home.search') }}</button>
          </div>
          <div class="hot-keywords" v-if="!isMobile">
            <span class="hot-label">{{ t('home.hotSearch') }}:</span>
            <router-link to="/product/list?keyword=智能手表" class="hot-word">智能手表</router-link>
            <router-link to="/product/list?keyword=箱包" class="hot-word">箱包</router-link>
            <router-link to="/product/list?keyword=跨境好物" class="hot-word">跨境好物</router-link>
          </div>
        </div>

        <!-- 右侧：用户功能 -->
        <div class="header-right">
          <router-link to="/user" class="header-icon" v-if="!isMobile">
            <el-icon :size="20"><User /></el-icon>
            <span>{{ t('home.account') }}</span>
          </router-link>
          <router-link to="/cart" class="header-icon cart-icon">
            <el-badge :value="cartCount" :hidden="cartCount === 0" class="cart-badge">
              <el-icon :size="22"><ShoppingCart /></el-icon>
            </el-badge>
            <span v-if="!isMobile">{{ t('home.cart') }}</span>
          </router-link>
        </div>
      </div>
    </header>

    <!-- 分类导航栏 -->
    <nav class="category-nav" v-if="!isMobile">
      <div class="category-container">
        <div class="all-categories" @mouseenter="showAllCategories = true" @mouseleave="showAllCategories = false">
          <el-icon><Grid /></el-icon>
          <span>{{ t('home.allCategories') }}</span>
          <!-- 全部分类下拉 -->
          <div class="category-dropdown" v-if="showAllCategories">
            <div class="category-item" v-for="cat in categories" :key="cat.id" @click="goCategory(cat.id)">
              <span>{{ cat.name }}</span>
              <el-icon><ArrowRight /></el-icon>
            </div>
          </div>
        </div>
        <div class="nav-links">
          <router-link to="/" class="nav-link active">{{ t('home.home') }}</router-link>
          <router-link to="/product/list" class="nav-link">{{ t('home.newArrivals') }}</router-link>
          <router-link to="/product/list?sort=sales" class="nav-link">{{ t('home.bestSellers') }}</router-link>
          <router-link to="/product/list?tag=flash_sale" class="nav-link flash-sale">{{ t('home.flashSale') }}</router-link>
        </div>
      </div>
    </nav>

    <!-- 移动端分类横向滚动 -->
    <nav class="mobile-category-nav" v-if="isMobile">
      <div class="mobile-categories-scroll">
        <router-link to="/" class="mobile-cat active">{{ t('home.home') }}</router-link>
        <router-link to="/product/list" class="mobile-cat">{{ t('home.newArrivals') }}</router-link>
        <router-link to="/product/list?sort=sales" class="mobile-cat">{{ t('home.bestSellers') }}</router-link>
        <router-link to="/product/list?tag=flash_sale" class="mobile-cat">{{ t('home.flashSale') }}</router-link>
        <router-link to="/category" class="mobile-cat">{{ t('home.allCategories') }}</router-link>
      </div>
    </nav>

    <!-- 主内容区 -->
    <main class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- 移动端底部导航 -->
    <nav class="mobile-bottom-nav" v-if="isMobile">
      <router-link to="/" class="bottom-nav-item" :class="{ active: $route.path === '/' }">
        <el-icon :size="22"><HomeFilled /></el-icon>
        <span>{{ t('home.home') }}</span>
      </router-link>
      <router-link to="/category" class="bottom-nav-item">
        <el-icon :size="22"><Grid /></el-icon>
        <span>{{ t('home.category') }}</span>
      </router-link>
      <router-link to="/product/list" class="bottom-nav-item">
        <el-icon :size="22"><TrendCharts /></el-icon>
        <span>{{ t('home.newArrivals') }}</span>
      </router-link>
      <router-link to="/cart" class="bottom-nav-item">
        <el-badge :value="cartCount" :hidden="cartCount === 0">
          <el-icon :size="22"><ShoppingCart /></el-icon>
        </el-badge>
        <span>{{ t('home.cart') }}</span>
      </router-link>
      <router-link to="/user" class="bottom-nav-item">
        <el-icon :size="22"><User /></el-icon>
        <span>{{ t('home.me') }}</span>
      </router-link>
    </nav>

    <!-- PC端页脚 -->
    <footer class="tllos-footer" v-if="!isMobile">
      <div class="tllos-container">
        <div class="tllos-footer__main">
          <div class="tllos-footer__brand">
            <div class="tllos-footer__logo">TLLOS</div>
            <p class="tllos-footer__slogan">科技点亮生活 · 品质触手可及</p>
            <div class="tllos-footer__contact">
              <div><el-icon><Phone /></el-icon> 0532-85501573</div>
              <div><el-icon><Message /></el-icon> service@tllos.com</div>
            </div>
          </div>
          <div class="tllos-footer__links">
            <div class="tllos-footer__col">
              <h4>购物指南</h4>
              <router-link to="/help">购物流程</router-link>
              <router-link to="/help">会员介绍</router-link>
              <router-link to="/help">常见问题</router-link>
              <router-link to="/help">联系客服</router-link>
            </div>
            <div class="tllos-footer__col">
              <h4>配送方式</h4>
              <router-link to="/help">上门自提</router-link>
              <router-link to="/help">配送方式</router-link>
              <router-link to="/help">配送范围</router-link>
              <router-link to="/help">配送时间</router-link>
            </div>
            <div class="tllos-footer__col">
              <h4>支付方式</h4>
              <router-link to="/help">货到付款</router-link>
              <router-link to="/help">在线支付</router-link>
              <router-link to="/help">分期付款</router-link>
              <router-link to="/help">公司转账</router-link>
            </div>
            <div class="tllos-footer__col">
              <h4>售后服务</h4>
              <router-link to="/after-sale">售后政策</router-link>
              <router-link to="/after-sale">退换货流程</router-link>
              <router-link to="/after-sale">退款说明</router-link>
              <router-link to="/help">价格保护</router-link>
            </div>
          </div>
        </div>
        <div class="tllos-footer__bottom">
          <span>© 2026 TLLOS 商城 版权所有</span>
          <span>鲁ICP备2025191415号-2</span>
        </div>
      </div>
    </footer>

    <!-- 移动端侧边菜单 -->
    <el-drawer v-model="showMobileMenu" direction="ltr" size="80%" :with-header="false" class="mobile-menu-drawer">
      <div class="mobile-menu">
        <div class="menu-header">
          <span class="menu-logo">TLLOS</span>
          <el-button text @click="showMobileMenu = false"><el-icon :size="22"><Close /></el-icon></el-button>
        </div>
        <div class="menu-user" @click="goUserCenter">
          <el-avatar :size="48" icon="User" />
          <span>{{ t('home.loginRegister') }}</span>
        </div>
        <div class="menu-categories">
          <div class="menu-title">{{ t('home.allCategories') }}</div>
          <div class="menu-cat-item" v-for="cat in categories" :key="cat.id" @click="goCategory(cat.id); showMobileMenu = false">
            <span>{{ cat.name }}</span>
            <el-icon><ArrowRight /></el-icon>
          </div>
        </div>
        <div class="menu-links">
          <router-link to="/" @click="showMobileMenu = false">{{ t('home.home') }}</router-link>
          <router-link to="/product/list" @click="showMobileMenu = false">{{ t('home.newArrivals') }}</router-link>
          <router-link to="/order/list" @click="showMobileMenu = false">{{ t('home.myOrders') }}</router-link>
          <router-link to="/user" @click="showMobileMenu = false">{{ t('home.myAccount') }}</router-link>
        </div>
        <div class="menu-locale">
          <el-radio-group v-model="locale" size="small" @change="changeLocale">
            <el-radio-button value="zh">中文</el-radio-button>
            <el-radio-button value="en">English</el-radio-button>
          </el-radio-group>
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getConfig } from '@/api/home'
import {
  Menu, Search, User, ShoppingCart, Grid, HomeFilled, TrendCharts,
  Bell, Setting, ArrowDown, ArrowRight, Close, ChatDotRound, Share, Message, Phone
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const { t, locale } = useI18n()

const isMobile = ref(false)
const showMobileMenu = ref(false)
const showAllCategories = ref(false)
const searchKeyword = ref('')
const cartCount = ref(0)
const siteConfig = ref({ site_icp: "鲁ICP备2025191415号-2", site_copyright: "© 2026 TLLOS商城 版权所有" })
const categories = ref([
  { id: 1, name: '智能手表' },
  { id: 2, name: '箱包配饰' },
  { id: 3, name: '数码电子' },
  { id: 4, name: '家居生活' },
  { id: 5, name: '美妆个护' },
  { id: 6, name: '运动户外' },
  { id: 7, name: '母婴玩具' },
  { id: 8, name: '更多分类' }
])

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  loadCartCount()
  loadSiteConfig()
})

const loadSiteConfig = async () => {
  try {
    const res = await getConfig()
    if (res.data) {
      siteConfig.value = { ...siteConfig.value, ...res.data }
    }
  } catch (e) {
    console.error("加载站点配置失败", e)
  }
}

const loadCartCount = () => {
  const cart = JSON.parse(localStorage.getItem('tllos_cart') || '[]')
  cartCount.value = cart.reduce((sum, item) => sum + item.quantity, 0)
}

const changeLocale = (lang) => {
  locale.value = lang
  localStorage.setItem('tllos_locale', lang)
}

const goSearch = () => {
  router.push('/product/list?keyword=' + encodeURIComponent(searchKeyword.value))
}

const doSearch = () => {
  goSearch()
}

const goCategory = (id) => {
  router.push('/product/list?category=' + id)
}

const goUserCenter = () => {
  showMobileMenu.value = false
  router.push('/user')
}

watch(() => route.path, () => {
  showMobileMenu.value = false
})
</script>

<style scoped>
/* 全局溢出保护：禁止横向滚动 */
:deep(html), :deep(body) {
  overflow-x: hidden;
  max-width: 100%;
}
:deep(*) {
  box-sizing: border-box;
}
.pc-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f5f5;
  overflow-x: hidden;
  max-width: 100%;
}

/* 顶部公告栏 */
.top-notice {
  background: linear-gradient(90deg, #ff6b00, #ff8c33);
  color: #fff;
  font-size: 12px;
  padding: 6px 0;
}
.notice-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 20px;
}
.notice-right {
  margin-left: auto;
}
.locale-switcher {
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* 主导航栏 */
.main-header {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
  position: sticky;
  top: 0;
  z-index: 100;
}
.header-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  padding: 12px 20px;
  gap: 16px;
}
/* 移动端顶部导航适配 */
@media (max-width: 768px) {
  .header-container {
    padding: 10px 12px;
    gap: 8px;
    flex-wrap: nowrap;
  }
  .header-left {
    flex-shrink: 0;
    gap: 6px;
  }
  .logo {
    align-items: center;
    gap: 2px;
  }
  .logo-text {
    font-size: 22px;
    white-space: nowrap;
  }
  .logo-sub {
    font-size: 11px;
    white-space: nowrap;
  }
  .header-center {
    flex: 1;
    min-width: 0;
  }
  .search-box {
    padding: 0 4px 0 10px;
  }
  .search-box input {
    font-size: 13px;
    padding: 6px 0;
    min-width: 0;
  }
  .search-btn {
    padding: 6px 12px;
    font-size: 13px;
    flex-shrink: 0;
  }
  .header-right {
    flex-shrink: 0;
    gap: 8px;
  }
}
.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.menu-btn {
  padding: 4px;
}
.logo {
  text-decoration: none;
  display: flex;
  align-items: baseline;
  gap: 6px;
}
.logo-text {
  font-size: 28px;
  font-weight: 900;
  color: #ff6b00;
  letter-spacing: -1px;
}
.logo-sub {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}
.header-center {
  flex: 1;
  max-width: 500px;
}
.search-box {
  display: flex;
  align-items: center;
  border: 2px solid #ff6b00;
  border-radius: 24px;
  padding: 0 4px 0 16px;
  background: #fff;
  cursor: text;
}
.search-box .el-icon {
  color: #999;
  margin-right: 8px;
}
.search-box input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  padding: 8px 0;
}
.search-btn {
  background: #ff6b00;
  color: #fff;
  border: none;
  padding: 8px 24px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}
.hot-keywords {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 6px;
  font-size: 12px;
}
.hot-label {
  color: #999;
}
.hot-word {
  color: #666;
  text-decoration: none;
}
.hot-word:hover {
  color: #ff6b00;
}
.header-right {
  display: flex;
  align-items: center;
  gap: 20px;
}
.header-icon {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  color: #333;
  font-size: 12px;
  gap: 2px;
}
.header-icon:hover {
  color: #ff6b00;
}
.cart-badge {
  --el-badge-bg-color: #ff6b00;
}

/* 分类导航栏 */
.category-nav {
  background: #fff;
  border-bottom: 1px solid #eee;
}
.category-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  padding: 0 20px;
  height: 44px;
}
.all-categories {
  background: #ff6b00;
  color: #fff;
  padding: 0 20px;
  height: 44px;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 500;
  position: relative;
}
.category-dropdown {
  position: absolute;
  top: 44px;
  left: 0;
  width: 200px;
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,.1);
  z-index: 200;
}
.category-item {
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #333;
  cursor: pointer;
  border-bottom: 1px solid #f5f5f5;
}
.category-item:hover {
  background: #fff5f0;
  color: #ff6b00;
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 28px;
  margin-left: 32px;
}
.nav-link {
  color: #333;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  padding: 10px 0;
}
.nav-link:hover, .nav-link.active {
  color: #ff6b00;
}
.flash-sale {
  color: #ff4757;
  font-weight: 600;
}

/* 移动端分类横向滚动 */
.mobile-category-nav {
  background: #fff;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}
.mobile-categories-scroll {
  display: flex;
  overflow-x: auto;
  padding: 0 12px;
  gap: 16px;
  scrollbar-width: none;
}
.mobile-categories-scroll::-webkit-scrollbar {
  display: none;
}
.mobile-cat {
  white-space: nowrap;
  color: #333;
  text-decoration: none;
  font-size: 14px;
  padding: 6px 0;
}
.mobile-cat.active {
  color: #ff6b00;
  font-weight: 600;
  border-bottom: 2px solid #ff6b00;
}

/* 主内容区 */
.main-content {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 16px 20px;
}
.mobile-view .main-content {
  padding: 12px;
  padding-bottom: 70px;
}

/* 移动端底部导航 */
.mobile-bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  display: flex;
  justify-content: space-around;
  padding: 8px 0;
  box-shadow: 0 -2px 8px rgba(0,0,0,.06);
  z-index: 100;
}
.bottom-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  color: #666;
  font-size: 11px;
  gap: 2px;
  padding: 4px 8px;
}
.bottom-nav-item.active {
  color: #ff6b00;
}

/* PC端页脚 */
.tllos-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.tllos-footer { background: #1a1a2e; color: rgba(255,255,255,0.6); padding-top: 60px; }
.tllos-footer__main { display: flex; gap: 60px; padding-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.08); }
.tllos-footer__brand { flex: 0 0 280px; }
.tllos-footer__logo { font-size: 32px; font-weight: 900; color: #fff; letter-spacing: 0.05em; margin-bottom: 12px; }
.tllos-footer__slogan { font-size: 14px; color: rgba(255,255,255,0.5); margin-bottom: 20px; }
.tllos-footer__contact { display: flex; flex-direction: column; gap: 8px; font-size: 13px; }
.tllos-footer__contact > div { display: flex; align-items: center; gap: 8px; }
.tllos-footer__links { flex: 1; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
.tllos-footer__col h4 { font-size: 14px; font-weight: 700; color: #fff; margin: 0 0 16px; }
.tllos-footer__col a { display: block; font-size: 13px; color: rgba(255,255,255,0.5); padding: 5px 0; cursor: pointer; transition: color 0.2s; text-decoration: none; }
.tllos-footer__col a:hover { color: #409eff; }
.tllos-footer__bottom {
  display: flex; justify-content: center; gap: 24px;
  padding: 20px 0; font-size: 12px; color: rgba(255,255,255,0.4);
}
@media (max-width: 1024px) {
  .tllos-footer__main { flex-direction: column; }
}

/* 移动端侧边菜单 */
.mobile-menu-drawer :deep(.el-drawer__body) {
  padding: 0;
}
.mobile-menu {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #ff6b00;
  color: #fff;
}
.menu-logo {
  font-size: 24px;
  font-weight: 900;
}
.menu-user {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  background: #fff5f0;
  cursor: pointer;
}
.menu-categories {
  flex: 1;
  overflow-y: auto;
  padding: 16px 0;
}
.menu-title {
  padding: 0 20px 12px;
  font-weight: 600;
  color: #333;
}
.menu-cat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  color: #333;
  cursor: pointer;
}
.menu-cat-item:hover {
  background: #f5f5f5;
}
.menu-links {
  display: flex;
  flex-direction: column;
  padding: 16px 20px;
  border-top: 1px solid #eee;
  gap: 12px;
}
.menu-links a {
  color: #333;
  text-decoration: none;
  font-size: 14px;
}
.menu-locale {
  padding: 16px 20px;
  border-top: 1px solid #eee;
}

/* 过渡动画 */
.fade-enter-active, .fade-leave-active {
  transition: opacity .2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
