<template>
  <el-container class="merchant-layout">
    <el-aside :width="appStore.sidebarCollapsed ? '64px' : '220px'" class="sidebar">
      <div class="logo"><span v-if="!appStore.sidebarCollapsed">TLLOS 商家</span><span v-else>M</span></div>
      <el-menu :default-active="$route.path" :collapse="appStore.sidebarCollapsed" router background-color="#1a2332" text-color="#b7bdc6" active-text-color="#52c41a">
        <el-menu-item v-for="item in menuItems" :key="item.path" :index="item.path">
          <el-icon><component :is="item.icon" /></el-icon>
          <template #title>{{ t(item.title) }}</template>
        </el-menu-item>
      </el-menu>
    </el-aside>
    <el-container>
      <el-header class="header">
        <div class="header-left">
          <el-icon class="collapse-btn" @click="appStore.toggleSidebar()"><Fold v-if="!appStore.sidebarCollapsed" /><Expand v-else /></el-icon>
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/' }">{{ t('menu.dashboard') }}</el-breadcrumb-item>
            <el-breadcrumb-item v-if="$route.meta.title">{{ t($route.meta.title) }}</el-breadcrumb-item>
          </el-breadcrumb>
        </div>
        <div class="header-right">
          <el-tag type="success" size="small">营业中</el-tag>
          <el-dropdown @command="handleLocale">
            <el-button text>{{ appStore.locale === 'zh' ? '中文' : 'EN' }}</el-button>
            <template #dropdown><el-dropdown-menu><el-dropdown-item command="zh">简体中文</el-dropdown-item><el-dropdown-item command="en">English</el-dropdown-item></el-dropdown-menu></template>
          </el-dropdown>
          <el-dropdown @command="handleCommand">
            <div class="user-info"><el-avatar :size="32" icon="Shop" /><span class="username">{{ userStore.userInfo?.nickname || '商家' }}</span></div>
            <template #dropdown><el-dropdown-menu><el-dropdown-item command="profile">{{ t('common.profile') }}</el-dropdown-item><el-dropdown-item command="logout" divided>{{ t('common.logout') }}</el-dropdown-item></el-dropdown-menu></template>
          </el-dropdown>
        </div>
      </el-header>
      <el-main class="main-content"><router-view /></el-main>
    </el-container>
  </el-container>
</template>
<script setup>
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { ElMessageBox, ElMessage } from 'element-plus'
const router = useRouter()
const { t, locale } = useI18n()
const appStore = useAppStore()
const userStore = useUserStore()
const menuItems = [
  { path: '/dashboard', title: 'menu.dashboard', icon: 'Odometer' },
  { path: '/product/list', title: 'menu.productList', icon: 'Goods' },
  { path: '/order/list', title: 'menu.orderList', icon: 'List' },
  { path: '/shop/info', title: 'menu.shopInfo', icon: 'Shop' },
  { path: '/finance/list', title: 'menu.financeList', icon: 'Money' },
  { path: '/marketing/coupon', title: 'menu.coupon', icon: 'Ticket' }
]
const handleLocale = cmd => { appStore.setLocale(cmd); locale.value = cmd; ElMessage.success(cmd === 'zh' ? '已切换为中文' : 'Switched to English') }
const handleCommand = async cmd => {
  if (cmd === 'logout') { await ElMessageBox.confirm('确定退出登录？', '提示', { type: 'warning' }); await userStore.logout(); router.push('/login') }
}
</script>
<style scoped>
.merchant-layout { height: 100vh; }
.sidebar { background: #1a2332; transition: width 0.3s; overflow: hidden; }
.logo { height: 60px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; font-weight: bold; border-bottom: 1px solid #2a3441; }
.sidebar :deep(.el-menu) { border-right: none; }
.header { background: #fff; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 0 20px; }
.header-left { display: flex; align-items: center; gap: 16px; }
.collapse-btn { font-size: 20px; cursor: pointer; color: #606266; }
.header-right { display: flex; align-items: center; gap: 16px; }
.user-info { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.username { font-size: 14px; color: #303133; }
.main-content { background: #f0f2f5; padding: 20px; overflow-y: auto; }
</style>
