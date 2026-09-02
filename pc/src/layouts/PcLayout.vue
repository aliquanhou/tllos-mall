<template>
  <div class="pc-layout">
    <!-- 顶部导航栏 -->
    <header class="pc-header">
      <div class="header-top">
        <div class="container">
          <span class="welcome">欢迎来到TLLOS商城</span>
          <div class="header-links">
            <template v-if="!userStore.isLogin">
              <router-link to="/login">登录</router-link>
              <router-link to="/login?type=register">注册</router-link>
            </template>
            <template v-else>
              <router-link to="/profile">{{ userStore.userInfo?.nickname || '用户中心' }}</router-link>
              <router-link to="/orders">我的订单</router-link>
              <a href="javascript:;" @click="logout">退出</a>
            </template>
            <router-link to="/cart" class="cart-link">
              <el-badge :value="cartStore.count" :hidden="cartStore.count === 0">
                <el-icon><ShoppingCart /></el-icon> 购物车
              </el-badge>
            </router-link>
          </div>
        </div>
      </div>
      <div class="header-main">
        <div class="container">
          <router-link to="/home" class="logo">
            <h1>TLLOS商城</h1>
          </router-link>
          <div class="search-box">
            <el-input v-model="searchKeyword" placeholder="搜索商品" size="large" @keyup.enter="goSearch">
              <template #append>
                <el-button @click="goSearch"><el-icon><Search /></el-icon></el-button>
              </template>
            </el-input>
          </div>
          <nav class="main-nav">
            <router-link to="/home">首页</router-link>
            <router-link to="/category">分类</router-link>
            <router-link to="/products">全部商品</router-link>
            <router-link to="/orders">我的订单</router-link>
          </nav>
        </div>
      </div>
    </header>

    <!-- 主内容区 -->
    <main class="pc-main">
      <div class="container">
        <router-view />
      </div>
    </main>

    <!-- 底部页脚 -->
    <footer class="pc-footer">
      <div class="container">
        <div class="footer-links">
          <div class="footer-col">
            <h4>购物指南</h4>
            <a>购物流程</a>
            <a>会员介绍</a>
            <a>常见问题</a>
          </div>
          <div class="footer-col">
            <h4>配送方式</h4>
            <a>上门自提</a>
            <a>快递配送</a>
            <a>配送范围</a>
          </div>
          <div class="footer-col">
            <h4>支付方式</h4>
            <a>货到付款</a>
            <a>在线支付</a>
            <a>分期付款</a>
          </div>
          <div class="footer-col">
            <h4>售后服务</h4>
            <a>退换货政策</a>
            <a>退款说明</a>
            <a>联系客服</a>
          </div>
        </div>
        <div class="footer-bottom">
          <p>© 2026 TLLOS商城 版权所有 | TLLOS Mall</p>
        </div>
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

const goSearch = () => {
  if (searchKeyword.value.trim()) {
    router.push({ path: '/products', query: { keyword: searchKeyword.value } })
  }
}

const logout = () => {
  userStore.logout()
  ElMessage.success('已退出登录')
  router.push('/home')
}
</script>

<style scoped>
.pc-layout { min-height: 100vh; display: flex; flex-direction: column; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

/* 顶部导航 */
.pc-header { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.header-top { background: #f7f7f7; border-bottom: 1px solid #eee; padding: 8px 0; font-size: 12px; color: #666; }
.header-top .container { display: flex; justify-content: space-between; align-items: center; }
.header-links { display: flex; gap: 16px; align-items: center; }
.header-links a { color: #666; text-decoration: none; }
.header-links a:hover { color: #e6a23c; }
.cart-link { display: flex; align-items: center; gap: 4px; }

.header-main { padding: 16px 0; }
.header-main .container { display: flex; align-items: center; gap: 40px; }
.logo h1 { font-size: 28px; color: #e6a23c; margin: 0; font-weight: bold; }
.logo { text-decoration: none; }
.search-box { flex: 1; max-width: 500px; }
.main-nav { display: flex; gap: 24px; }
.main-nav a { color: #333; text-decoration: none; font-size: 15px; font-weight: 500; padding: 8px 0; border-bottom: 2px solid transparent; }
.main-nav a:hover, .main-nav a.router-link-active { color: #e6a23c; border-bottom-color: #e6a23c; }

/* 主内容 */
.pc-main { flex: 1; padding: 20px 0; }

/* 底部页脚 */
.pc-footer { background: #fff; border-top: 1px solid #eee; padding: 40px 0 20px; margin-top: 40px; }
.footer-links { display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; margin-bottom: 30px; }
.footer-col h4 { font-size: 14px; color: #333; margin-bottom: 16px; }
.footer-col a { display: block; color: #999; font-size: 12px; line-height: 2; text-decoration: none; cursor: pointer; }
.footer-col a:hover { color: #e6a23c; }
.footer-bottom { text-align: center; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px; }
</style>
