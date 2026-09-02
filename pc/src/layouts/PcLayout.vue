<template>
  <div class="pc-layout">
    <div class="promo-bar">
      <div class="container">
        <span class="promo-text">🎉 限时特惠：全场满200减30，新用户注册即送100元优惠券！</span>
        <a href="javascript:;" class="promo-link">立即查看 →</a>
      </div>
    </div>
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
      <div class="header-main">
        <div class="container">
          <router-link to="/home" class="logo"><h1>TLLOS<span class="logo-sub">商城</span></h1><p class="logo-slogan">品质生活 优选好物</p></router-link>
          <div class="search-box">
            <div class="search-tabs"><span class="tab active">商品</span><span class="tab">店铺</span></div>
            <el-input v-model="searchKeyword" placeholder="搜索商品，如：男装、手机、零食" size="large" @keyup.enter="goSearch">
              <template #append><el-button type="warning" @click="goSearch"><el-icon><Search /></el-icon> 搜索</el-button></template>
            </el-input>
            <div class="hot-words"><span>热搜：</span><a href="javascript:;" @click="searchHot('男装')">男装</a><a href="javascript:;" @click="searchHot('手机')">手机</a><a href="javascript:;" @click="searchHot('零食')">零食</a><a href="javascript:;" @click="searchHot('美妆')">美妆</a></div>
          </div>
          <div class="header-service"><div class="service-item"><el-icon size="24"><Phone /></el-icon><div class="service-text"><span class="service-label">客服热线</span><span class="service-phone">400-888-8888</span></div></div></div>
        </div>
      </div>
      <nav class="main-nav">
        <div class="container">
          <div class="nav-all-category"><el-icon><Menu /></el-icon> 全部商品分类</div>
          <div class="nav-links">
            <router-link to="/home" class="nav-link active">首页</router-link>
            <router-link to="/products" class="nav-link">全部商品</router-link>
            <router-link to="/category" class="nav-link">商品分类</router-link>
            <a href="javascript:;" class="nav-link">限时特惠</a>
            <a href="javascript:;" class="nav-link">新品上市</a>
            <a href="javascript:;" class="nav-link">品牌专区</a>
            <router-link to="/orders" class="nav-link">我的订单</router-link>
          </div>
        </div>
      </nav>
    </header>
    <main class="pc-main"><router-view /></main>
    <footer class="pc-footer">
      <div class="container">
        <div class="footer-service">
          <div class="service-col"><el-icon size="28"><Van /></el-icon><div><h4>正品保障</h4><p>正品行货 放心购买</p></div></div>
          <div class="service-col"><el-icon size="28"><Truck /></el-icon><div><h4>极速配送</h4><p>全国包邮 快速送达</p></div></div>
          <div class="service-col"><el-icon size="28"><Refresh /></el-icon><div><h4>7天无理由</h4><p>退换无忧 售后保障</p></div></div>
          <div class="service-col"><el-icon size="28"><Service /></el-icon><div><h4>在线客服</h4><p>7x24小时 贴心服务</p></div></div>
        </div>
        <div class="footer-links">
          <div class="footer-col"><h4>购物指南</h4><a>购物流程</a><a>会员介绍</a><a>常见问题</a><a>联系客服</a></div>
          <div class="footer-col"><h4>配送方式</h4><a>上门自提</a><a>快递配送</a><a>配送范围</a><a>配送时间</a></div>
          <div class="footer-col"><h4>支付方式</h4><a>货到付款</a><a>在线支付</a><a>分期付款</a><a>公司转账</a></div>
          <div class="footer-col"><h4>售后服务</h4><a>退换货政策</a><a>退款说明</a><a>价格保护</a><a>投诉建议</a></div>
          <div class="footer-col"><h4>关于我们</h4><a>公司简介</a><a>加入我们</a><a>合作伙伴</a><a>联系方式</a></div>
        </div>
        <div class="footer-bottom"><p>© 2026 TLLOS商城 版权所有 | TLLOS Mall | 粤ICP备XXXXXXXX号</p></div>
      </div>
    </footer>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useCartStore } from '@/stores/cart'
import { ElMessage } from 'element-plus'
const router = useRouter()
const userStore = useUserStore()
const cartStore = useCartStore()
const searchKeyword = ref('')
const goSearch = () => { if (searchKeyword.value.trim()) router.push({ path: '/products', query: { keyword: searchKeyword.value } }) }
const searchHot = (word) => { searchKeyword.value = word; goSearch() }
const logout = () => { userStore.logout(); ElMessage.success('已退出登录'); router.push('/home') }
</script>
<style scoped>
.pc-layout { min-height: 100vh; display: flex; flex-direction: column; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.promo-bar { background: linear-gradient(90deg, #e6a23c, #f56c6c); color: #fff; padding: 8px 0; font-size: 13px; }
.promo-bar .container { display: flex; justify-content: center; align-items: center; gap: 20px; }
.promo-link { color: #fff; text-decoration: underline; font-weight: bold; }
.pc-header { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.header-top { background: #f7f7f7; border-bottom: 1px solid #eee; padding: 8px 0; font-size: 12px; color: #666; }
.header-top .container { display: flex; justify-content: space-between; align-items: center; }
.header-links { display: flex; gap: 16px; align-items: center; }
.header-links a { color: #666; text-decoration: none; display: flex; align-items: center; gap: 4px; }
.header-links a:hover { color: #e6a23c; }
.register-link { color: #e6a23c !important; font-weight: bold; }
.user-link { color: #e6a23c !important; font-weight: 500; }
.cart-link { display: flex; align-items: center; gap: 4px; }
.header-main { padding: 20px 0; }
.header-main .container { display: flex; align-items: center; gap: 40px; }
.logo { text-decoration: none; display: flex; flex-direction: column; }
.logo h1 { font-size: 32px; color: #e6a23c; margin: 0; font-weight: bold; letter-spacing: 1px; }
.logo-sub { color: #333; font-size: 24px; margin-left: 4px; }
.logo-slogan { font-size: 12px; color: #999; margin: 4px 0 0 0; }
.search-box { flex: 1; max-width: 550px; }
.search-tabs { display: flex; gap: 0; margin-bottom: -1px; }
.search-tabs .tab { padding: 6px 16px; font-size: 13px; color: #666; cursor: pointer; border: 1px solid transparent; border-bottom: none; }
.search-tabs .tab.active { color: #e6a23c; border-color: #e6a23c; background: #fff; font-weight: bold; }
.hot-words { margin-top: 6px; font-size: 12px; color: #999; }
.hot-words a { color: #666; margin: 0 8px; text-decoration: none; cursor: pointer; }
.hot-words a:hover { color: #e6a23c; }
.header-service { display: flex; align-items: center; }
.service-item { display: flex; align-items: center; gap: 10px; color: #e6a23c; }
.service-text { display: flex; flex-direction: column; }
.service-label { font-size: 12px; color: #999; }
.service-phone { font-size: 18px; font-weight: bold; color: #e6a23c; }
.main-nav { background: #e6a23c; }
.main-nav .container { display: flex; align-items: center; }
.nav-all-category { background: #d4922a; color: #fff; padding: 12px 24px; font-size: 15px; font-weight: bold; display: flex; align-items: center; gap: 8px; width: 200px; }
.nav-links { display: flex; flex: 1; }
.nav-link { color: #fff; text-decoration: none; padding: 12px 24px; font-size: 15px; font-weight: 500; transition: background 0.2s; }
.nav-link:hover, .nav-link.active { background: #d4922a; }
.pc-main { flex: 1; }
.pc-footer { background: #fff; border-top: 1px solid #eee; margin-top: 40px; }
.footer-service { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; padding: 30px 0; border-bottom: 1px solid #eee; }
.service-col { display: flex; align-items: center; gap: 12px; color: #e6a23c; }
.service-col h4 { margin: 0 0 4px 0; font-size: 15px; color: #333; }
.service-col p { margin: 0; font-size: 12px; color: #999; }
.footer-links { display: grid; grid-template-columns: repeat(5, 1fr); gap: 40px; padding: 30px 0; }
.footer-col h4 { font-size: 14px; color: #333; margin-bottom: 16px; }
.footer-col a { display: block; color: #999; font-size: 12px; line-height: 2; text-decoration: none; cursor: pointer; }
.footer-col a:hover { color: #e6a23c; }
.footer-bottom { text-align: center; padding: 20px 0; border-top: 1px solid #eee; color: #999; font-size: 12px; }
</style>
