<template>
  <div class="mobile-header">
    <div class="mobile-header-top">
      <div class="mobile-logo" @click="goHome">TLLOS商城</div>
      <div class="mobile-header-actions">
        <el-icon size="20" @click="goSearch"><Search /></el-icon>
        <el-badge :value="cartCount" :hidden="cartCount === 0" class="cart-badge">
          <el-icon size="20" @click="goCart"><ShoppingCart /></el-icon>
        </el-badge>
        <el-icon size="20" @click="toggleMenu"><Menu /></el-icon>
      </div>
    </div>
    <div class="mobile-search-bar" @click="goSearch">
      <el-icon><Search /></el-icon>
      <span>搜索商品</span>
    </div>
    <!-- 移动端抽屉菜单 -->
    <el-drawer v-model="menuVisible" direction="rtl" size="80%" :with-header="false">
      <div class="mobile-menu">
        <div class="menu-user" v-if="isLogin">
          <el-avatar :size="40">{{ userInfo?.nickname?.charAt(0) || 'U' }}</el-avatar>
          <div class="menu-user-info">
            <span class="menu-user-name">{{ userInfo?.nickname }}</span>
            <span class="menu-user-level">会员</span>
          </div>
        </div>
        <div class="menu-user" v-else @click="goLogin">
          <el-avatar :size="40"><el-icon><User /></el-icon></el-avatar>
          <div class="menu-user-info">
            <span class="menu-user-name">点击登录</span>
            <span class="menu-user-level">登录享受更多优惠</span>
          </div>
        </div>
        <div class="menu-list">
          <div class="menu-item" @click="goHome"><el-icon><HomeFilled /></el-icon> 首页</div>
          <div class="menu-item" @click="goCategory"><el-icon><CollectionTag /></el-icon> 商品分类</div>
          <div class="menu-item" @click="goProducts"><el-icon><Goods /></el-icon> 全部商品</div>
          <div class="menu-item" @click="goOrders"><el-icon><List /></el-icon> 我的订单</div>
          <div class="menu-item" @click="goCart"><el-icon><ShoppingCart /></el-icon> 购物车</div>
          <div class="menu-item" @click="goProfile"><el-icon><User /></el-icon> 个人中心</div>
          <div class="menu-item" @click="goCollects"><el-icon><Star /></el-icon> 我的收藏</div>
        </div>
        <div class="menu-footer" v-if="isLogin" @click="logout">
          <el-icon><SwitchButton /></el-icon> 退出登录
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useCartStore } from '@/stores/cart'
import { ElMessage } from 'element-plus'

const router = useRouter()
const userStore = useUserStore()
const cartStore = useCartStore()
const menuVisible = ref(false)

const isLogin = computed(() => userStore.isLogin)
const userInfo = computed(() => userStore.userInfo)
const cartCount = computed(() => cartStore.count)

const toggleMenu = () => { menuVisible.value = !menuVisible.value }
const goHome = () => { router.push('/home'); menuVisible.value = false }
const goCategory = () => { router.push('/category'); menuVisible.value = false }
const goProducts = () => { router.push('/products'); menuVisible.value = false }
const goOrders = () => { router.push('/orders'); menuVisible.value = false }
const goCart = () => { router.push('/cart'); menuVisible.value = false }
const goProfile = () => { router.push('/profile'); menuVisible.value = false }
const goCollects = () => { router.push('/collects'); menuVisible.value = false }
const goSearch = () => { router.push('/products'); menuVisible.value = false }
const goLogin = () => { router.push('/login'); menuVisible.value = false }
const logout = () => { userStore.logout(); ElMessage.success('已退出登录'); menuVisible.value = false; router.push('/home') }
</script>

<style scoped>
.mobile-header { background: #fff; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.mobile-header-top { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; }
.mobile-logo { font-size: 18px; font-weight: bold; color: #e6a23c; cursor: pointer; }
.mobile-header-actions { display: flex; gap: 16px; align-items: center; color: #333; }
.cart-badge { display: flex; align-items: center; }
.mobile-search-bar { display: flex; align-items: center; gap: 8px; margin: 0 15px 10px 15px; padding: 8px 12px; background: #f5f5f5; border-radius: 20px; color: #999; font-size: 13px; cursor: pointer; }
.mobile-menu { height: 100%; display: flex; flex-direction: column; }
.menu-user { display: flex; align-items: center; gap: 12px; padding: 20px 16px; background: linear-gradient(135deg, #e6a23c, #f56c6c); color: #fff; cursor: pointer; }
.menu-user-info { display: flex; flex-direction: column; }
.menu-user-name { font-size: 16px; font-weight: bold; }
.menu-user-level { font-size: 12px; opacity: 0.9; margin-top: 2px; }
.menu-list { flex: 1; padding: 10px 0; }
.menu-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; font-size: 15px; color: #333; cursor: pointer; transition: background 0.2s; }
.menu-item:hover { background: #f5f7fa; color: #e6a23c; }
.menu-item .el-icon { font-size: 18px; }
.menu-footer { display: flex; align-items: center; gap: 12px; padding: 16px; border-top: 1px solid #eee; color: #f56c6c; font-size: 15px; cursor: pointer; }
</style>
