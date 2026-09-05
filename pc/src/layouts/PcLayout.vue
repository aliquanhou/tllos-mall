<template>
  <div class="pc-layout">
    <!-- 移动端Header -->
    <div class="show-mobile"><MobileHeader /></div>
    <!-- 桌面端Header -->
    <div class="hide-mobile">
      <!-- 促销条 -->
      <div class="promo-bar">
        <div class="container">
          <span class="promo-text">🎉 限时特惠：全场满200减30，新用户注册即送100元优惠券！</span>
          <a href="javascript:;" class="promo-link">立即查看 →</a>
        </div>
      </div>
      <!-- 顶部工具栏 -->
      <header class="pc-header">
        <div class="header-top">
          <div class="container">
            <span class="welcome">欢迎来到TLLOS商城</span>
            <div class="header-links">
              <template v-if="!userStore.isLogin">
                <router-link to="/login">登录</router-link>
                <router-link to="/login?type=register" class="register-link">免费注册</router-link>
              </template>
              <template v-else>
                <router-link to="/profile" class="user-link"><el-icon><User /></el-icon> {{ userStore.userInfo?.nickname || '用户中心' }}</router-link>
                <router-link to="/orders">我的订单</router-link>
                <router-link to="/collects">我的收藏</router-link>
                <a href="javascript:;" @click="logout">退出</a>
              </template>
              <router-link to="/cart" class="cart-link"><el-badge :value="cartStore.count" :hidden="cartStore.count === 0"><el-icon><ShoppingCart /></el-icon> 购物车</el-badge></router-link>
            </div>
          </div>
        </div>
        <!-- Logo+搜索区 -->
        <div class="header-main">
          <div class="container">
            <div class="logo" @click="goHome">
              <h1>TLLOS<span class="logo-sub">商城</span></h1>
              <p class="logo-slogan">品质生活 优选好物</p>
            </div>
            <div class="search-box">
              <div class="search-tabs"><span class="tab active">商品</span><span class="tab">店铺</span></div>
              <div class="search-input-wrap">
                <el-input v-model="searchKeyword" placeholder="搜索商品" size="large" @keyup.enter="doSearch">
                  <template #append><el-button type="warning" @click="doSearch"><el-icon><Search /></el-icon> 搜索</el-button></template>
                </el-input>
              </div>
              <div class="hot-words"><span>热搜：</span><a href="javascript:;" @click="searchHot('男装')">男装</a><a href="javascript:;" @click="searchHot('手机')">手机</a><a href="javascript:;" @click="searchHot('零食')">零食</a><a href="javascript:;" @click="searchHot('美妆')">美妆</a></div>
            </div>
            <div class="header-service"><div class="service-item"><el-icon size="24"><Phone /></el-icon><div class="service-text"><span class="service-label">客服热线</span><span class="service-phone">400-888-8888</span></div></div></div>
          </div>
        </div>
        <!-- 主导航 + Mega Menu -->
        <nav class="main-nav">
          <div class="container">
            <div class="nav-all-category" @mouseenter="showAllCategory = true" @mouseleave="showAllCategory = false">
              <el-icon><Menu /></el-icon> 全部商品分类
              <!-- 全部分类Mega Panel -->
              <div class="mega-panel all-category-panel" v-show="showAllCategory">
                <div class="mega-col" v-for="cat in categories.slice(0, 8)" :key="cat.id">
                  <h4 class="mega-title">{{ cat.name }}</h4>
                  <a href="javascript:;" class="mega-link" v-for="i in 4" :key="i" @click="goCategory(cat.id)">{{ cat.name }}子类{{ i }}</a>
                </div>
                <div class="mega-banner"><div class="mega-banner-img">编辑推荐</div></div>
              </div>
            </div>
            <router-link to="/home" class="nav-link active">首页</router-link>
            <router-link to="/products" class="nav-link">全部商品</router-link>
            <!-- 分类Mega Menu -->
            <div class="nav-item" v-for="cat in categories.slice(0, 6)" :key="cat.id" @mouseenter="activeNav = cat.id" @mouseleave="activeNav = 0">
              <router-link :to="{ path: '/products', query: { category_id: cat.id } }" class="nav-link">{{ cat.name }}</router-link>
              <div class="mega-panel" v-show="activeNav === cat.id">
                <div class="mega-col" v-for="i in 3" :key="i">
                  <h4 class="mega-title">{{ cat.name }}分类{{ i }}</h4>
                  <a href="javascript:;" class="mega-link" v-for="j in 5" :key="j" @click="goCategory(cat.id)">{{ cat.name }}子类{{ j }}</a>
                </div>
                <div class="mega-banner"><div class="mega-banner-img">{{ cat.name }}推荐</div></div>
              </div>
            </div>
            <router-link to="/products" class="nav-link">限时特惠</router-link>
            <router-link to="/orders" class="nav-link">我的订单</router-link>
          </div>
        </nav>
      </header>
    </div>
    <!-- 主内容区 -->
    <main class="pc-main"><router-view /></main>
    <!-- 页脚 -->
    <footer class="pc-footer hide-mobile">
      <div class="footer-service">
        <div class="container">
          <div class="service-col"><el-icon size="28"><Van /></el-icon><div><h4>正品保障</h4><p>正品行货 放心购买</p></div></div>
          <div class="service-col"><el-icon size="28"><Truck /></el-icon><div><h4>极速配送</h4><p>全国包邮 快速送达</p></div></div>
          <div class="service-col"><el-icon size="28"><Refresh /></el-icon><div><h4>7天无理由</h4><p>退换无忧 售后保障</p></div></div>
          <div class="service-col"><el-icon size="28"><Service /></el-icon><div><h4>在线客服</h4><p>7x24小时 贴心服务</p></div></div>
        </div>
      </div>
      <div class="footer-links">
        <div class="container">
          <div class="footer-col"><h4>购物指南</h4><a href="javascript:;">购物流程</a><a href="javascript:;">会员介绍</a><a href="javascript:;">常见问题</a><a href="javascript:;">联系客服</a></div>
          <div class="footer-col"><h4>配送方式</h4><a href="javascript:;">上门自提</a><a href="javascript:;">211限时达</a><a href="javascript:;">配送服务查询</a><a href="javascript:;">海外配送</a></div>
          <div class="footer-col"><h4>支付方式</h4><a href="javascript:;">货到付款</a><a href="javascript:;">在线支付</a><a href="javascript:;">分期付款</a><a href="javascript:;">公司转账</a></div>
          <div class="footer-col"><h4>售后服务</h4><a href="javascript:;">售后政策</a><a href="javascript:;">价格保护</a><a href="javascript:;">退款说明</a><a href="javascript:;">返修/退换货</a></div>
          <div class="footer-col"><h4>关于我们</h4><a href="javascript:;">公司简介</a><a href="javascript:;">联系我们</a><a href="javascript:;">人才招聘</a><a href="javascript:;">商家入驻</a></div>
        </div>
      </div>
      <div class="footer-bottom"><div class="container"><p>© 2026 TLLOS商城 版权所有 | <a href="https://beian.miit.gov.cn" target="_blank" style="color:#999;text-decoration:none;">ICP备案号：粤ICP备2026000000号</a></p></div></div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useCartStore } from '@/stores/cart'
import { ElMessage } from 'element-plus'
import MobileHeader from '@/components/MobileHeader.vue'
import { getCategories } from '@/api/product'

const router = useRouter()
const userStore = useUserStore()
const cartStore = useCartStore()
const searchKeyword = ref('')
const categories = ref([])
const activeNav = ref(0)
const showAllCategory = ref(false)

const fetchCategories = async () => {
  try {
    const res = await getCategories()
    categories.value = res.data?.list || res.data || []
  } catch (e) { console.error(e) }
}

const goHome = () => router.push('/home')
const goCategory = (id) => router.push({ path: '/products', query: { category_id: id } })
const doSearch = () => { if (searchKeyword.value) router.push({ path: '/products', query: { keyword: searchKeyword.value } }) }
const searchHot = (kw) => { searchKeyword.value = kw; doSearch() }
const logout = () => { userStore.logout(); ElMessage.success('已退出登录'); router.push('/home') }

onMounted(() => { fetchCategories() })
</script>

<style scoped>
.pc-layout { min-height: 100vh; display: flex; flex-direction: column; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.promo-bar { background: linear-gradient(90deg, #f56c6c, #e6a23c); color: #fff; padding: 8px 0; font-size: 13px; }
.promo-bar .container { display: flex; justify-content: space-between; align-items: center; }
.promo-link { color: #fff; text-decoration: none; font-weight: bold; }
.pc-header { background: #fff; }
.header-top { border-bottom: 1px solid #f0f0f0; padding: 8px 0; font-size: 12px; color: #999; }
.header-top .container { display: flex; justify-content: space-between; align-items: center; }
.header-links { display: flex; gap: 16px; align-items: center; }
.header-links a { color: #666; text-decoration: none; }
.header-links a:hover { color: #e6a23c; }
.register-link { color: #e6a23c !important; }
.user-link { display: flex; align-items: center; gap: 4px; }
.cart-link { display: flex; align-items: center; gap: 4px; }
.header-main { padding: 20px 0; }
.header-main .container { display: flex; align-items: center; gap: 40px; }
.logo { cursor: pointer; }
.logo h1 { font-size: 32px; color: #e6a23c; margin: 0; font-weight: bold; }
.logo-sub { color: #333; font-size: 24px; }
.logo-slogan { font-size: 12px; color: #999; margin: 4px 0 0 0; }
.search-box { flex: 1; max-width: 500px; }
.search-tabs { display: flex; gap: 0; margin-bottom: -1px; }
.search-tabs .tab { padding: 4px 16px; font-size: 12px; cursor: pointer; border: 1px solid #ddd; border-bottom: none; background: #f5f5f5; color: #666; }
.search-tabs .tab.active { background: #fff; color: #e6a23c; border-color: #e6a23c; }
.hot-words { margin-top: 6px; font-size: 12px; color: #999; }
.hot-words a { color: #999; margin-right: 12px; text-decoration: none; }
.hot-words a:hover { color: #e6a23c; }
.header-service { display: flex; align-items: center; }
.service-item { display: flex; align-items: center; gap: 8px; color: #e6a23c; }
.service-text { display: flex; flex-direction: column; }
.service-label { font-size: 11px; color: #999; }
.service-phone { font-size: 16px; font-weight: bold; color: #333; }

/* 主导航 + Mega Menu */
.main-nav { background: #e6a23c; position: relative; }
.main-nav .container { display: flex; align-items: center; }
.nav-all-category { background: #d4922a; color: #fff; padding: 14px 24px; font-weight: bold; display: flex; align-items: center; gap: 8px; cursor: pointer; position: relative; min-width: 200px; }
.nav-link { color: #fff; text-decoration: none; padding: 14px 20px; font-size: 15px; display: inline-block; transition: background 0.2s; }
.nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.15); }
.nav-item { position: relative; }

/* Mega Panel */
.mega-panel { position: absolute; top: 100%; left: 0; background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,0.12); border-radius: 0 0 8px 8px; padding: 20px; display: flex; gap: 24px; z-index: 1000; min-width: 600px; }
.all-category-panel { left: 0; min-width: 800px; }
.mega-col { flex: 1; min-width: 120px; }
.mega-title { font-size: 14px; color: #333; font-weight: bold; margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
.mega-link { display: block; font-size: 13px; color: #666; padding: 5px 0; text-decoration: none; }
.mega-link:hover { color: #e6a23c; }
.mega-banner { width: 180px; flex-shrink: 0; }
.mega-banner-img { width: 100%; height: 200px; background: linear-gradient(135deg, #fdf6ec, #faecd8); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #e6a23c; font-size: 14px; font-weight: bold; }

.pc-main { flex: 1; }
.pc-footer { background: #fff; border-top: 1px solid #eee; margin-top: 40px; }
.footer-service { border-bottom: 1px solid #f0f0f0; padding: 30px 0; }
.footer-service .container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.service-col { display: flex; align-items: center; gap: 12px; color: #e6a23c; }
.service-col h4 { margin: 0; font-size: 15px; color: #333; }
.service-col p { margin: 4px 0 0 0; font-size: 12px; color: #999; }
.footer-links { padding: 30px 0; }
.footer-links .container { display: grid; grid-template-columns: repeat(5, 1fr); gap: 30px; }
.footer-col h4 { font-size: 15px; color: #333; margin: 0 0 16px 0; }
.footer-col a { display: block; font-size: 13px; color: #666; padding: 5px 0; text-decoration: none; }
.footer-col a:hover { color: #e6a23c; }
.footer-bottom { background: #fafafa; padding: 20px 0; text-align: center; }
.footer-bottom p { margin: 0; font-size: 12px; color: #999; }

/* ========== 移动端适配 ========== */
.show-mobile { display: none; }
.hide-mobile { display: block; }

@media (max-width: 768px) {
  .show-mobile { display: block; }
  .hide-mobile { display: none !important; }
  .pc-layout { background: #f5f5f5; }
  .container { max-width: 100%; padding: 0 12px; }
  .pc-main { padding-bottom: 60px; }
  .pc-footer { display: none !important; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
}
</style>
