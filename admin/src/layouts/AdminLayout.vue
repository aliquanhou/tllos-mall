<template>
  <el-container class="admin-layout">
    <el-aside :width="appStore.sidebarCollapsed ? '64px' : '220px'" class="sidebar">
      <div class="logo" @click="$router.push('/')">
        <span v-if="!appStore.sidebarCollapsed">TLLOS 商城</span>
        <span v-else>T</span>
      </div>
      <el-scrollbar class="menu-scroll">
        <el-menu :default-active="$route.path" :collapse="appStore.sidebarCollapsed" router background-color="#001529" text-color="#b7bdc6" active-text-color="#1890ff">
          <template v-for="group in menuGroups" :key="group.title">
            <el-sub-menu v-if="group.children && group.children.length > 1" :index="group.key">
              <template #title>
                <el-icon><component :is="group.icon" /></el-icon>
                <span>{{ group.title }}</span>
              </template>
              <el-menu-item v-for="item in group.children" :key="item.path" :index="item.path">
                <el-icon><component :is="item.icon" /></el-icon>
                <template #title>{{ item.title }}</template>
              </el-menu-item>
            </el-sub-menu>
            <el-menu-item v-else-if="group.children && group.children.length === 1" :index="group.children[0].path">
              <el-icon><component :is="group.icon" /></el-icon>
              <template #title>{{ group.title }}</template>
            </el-menu-item>
          </template>
        </el-menu>
      </el-scrollbar>
    </el-aside>
    <el-container>
      <el-header class="header">
        <div class="header-left">
          <el-icon class="collapse-btn" @click="appStore.toggleSidebar()"><Fold v-if="!appStore.sidebarCollapsed" /><Expand v-else /></el-icon>
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/' }">首页</el-breadcrumb-item>
            <el-breadcrumb-item v-if="$route.meta.title">{{ $route.meta.title }}</el-breadcrumb-item>
          </el-breadcrumb>
        </div>
        <div class="header-right">
          <el-dropdown @command="handleLocale">
            <el-button text>{{ appStore.locale === 'zh' ? '中文' : 'EN' }}</el-button>
            <template #dropdown><el-dropdown-menu><el-dropdown-item command="zh">简体中文</el-dropdown-item><el-dropdown-item command="en">English</el-dropdown-item></el-dropdown-menu></template>
          </el-dropdown>
          <el-dropdown @command="handleCommand">
            <div class="user-info"><el-avatar :size="32" icon="UserFilled" /><span class="username">{{ userStore.userInfo?.nickname || '管理员' }}</span></div>
            <template #dropdown><el-dropdown-menu><el-dropdown-item command="profile">个人中心</el-dropdown-item><el-dropdown-item command="logout" divided>退出登录</el-dropdown-item></el-dropdown-menu></template>
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

const menuGroups = [
  { key: 'workbench', title: '工作台', icon: 'Odometer', children: [{ path: '/dashboard', title: '工作台', icon: 'Odometer' }] },
  { key: 'goods', title: '商品管理', icon: 'Goods', children: [
    { path: '/product/list', title: '商品列表', icon: 'List' },
    { path: '/product/category', title: '商品分类', icon: 'Menu' },
    { path: '/product/comment', title: '商品评价', icon: 'ChatDotRound' },
    { path: '/product/sku', title: '商品SKU', icon: 'Grid' },
    { path: '/product/brand', title: '品牌管理', icon: 'Flag' },
    { path: '/product/type', title: '商品类型', icon: 'Collection' },
    { path: '/product/stock-warning', title: '库存预警', icon: 'Warning' },
  ]},
  { key: 'order', title: '订单管理', icon: 'Document', children: [
    { path: '/order/list', title: '订单列表', icon: 'List' },
    { path: '/order/after-sale', title: '订单售后', icon: 'RefreshLeft' },
    { path: '/order/log', title: '订单日志', icon: 'Document' },
  ]},
  { key: 'merchant', title: '商家管理', icon: 'Shop', children: [
    { path: '/merchant/list', title: '商家列表', icon: 'List' },
    { path: '/merchant/audit', title: '入驻审核', icon: 'CircleCheck' },
    { path: '/merchant/category', title: '商家分类', icon: 'Menu' },
    { path: '/merchant/account-log', title: '账户日志', icon: 'Document' },
    { path: '/merchant/menu', title: '商家菜单', icon: 'Menu' },
    { path: '/merchant/permission', title: '商家权限', icon: 'Key' },
    { path: '/merchant/level', title: '商家等级', icon: 'Medal' },
  ]},
  { key: 'user', title: '用户管理', icon: 'User', children: [
    { path: '/user/list', title: '用户列表', icon: 'List' },
    { path: '/user/level', title: '用户等级', icon: 'Medal' },
    { path: '/user/recharge', title: '充值记录', icon: 'Wallet' },
    { path: '/user/withdraw', title: '提现管理', icon: 'CreditCard' },
    { path: '/user/address', title: '收货地址', icon: 'Location' },
    { path: '/user/account-log', title: '账户日志', icon: 'Document' },
    { path: '/user/auth', title: '实名认证', icon: 'Postcard' },
    { path: '/user-center/point', title: '用户积分', icon: 'Star' },
    { path: '/user-center/favorite', title: '用户收藏', icon: 'Heart' },
  ]},
  { key: 'distribute', title: '分销管理', icon: 'Share', children: [
    { path: '/distribute/overview', title: '分销概览', icon: 'DataAnalysis' },
    { path: '/distribute/goods', title: '分销商品', icon: 'Goods' },
    { path: '/distribute/order', title: '分销订单', icon: 'Document' },
    { path: '/distribute/level', title: '分销等级', icon: 'Medal' },
    { path: '/distribute/agent', title: '分销商', icon: 'User' },
    { path: '/distribute/apply', title: '分销申请', icon: 'CircleCheck' },
    { path: '/distribute/setting', title: '分销设置', icon: 'Setting' },
  ]},
  { key: 'marketing', title: '营销管理', icon: 'Present', children: [
    { path: '/marketing/coupon', title: '优惠券', icon: 'Ticket' },
    { path: '/marketing/member-discount', title: '会员折扣', icon: 'Discount' },
    { path: '/marketing/seckill', title: '限时秒杀', icon: 'AlarmClock' },
    { path: '/marketing/group', title: '拼团活动', icon: 'Users' },
    { path: '/marketing/pt-open', title: '拼团开团', icon: 'User' },
  ]},
  { key: 'application', title: '应用管理', icon: 'Grid', children: [
    { path: '/application/deposit', title: '充值管理', icon: 'Wallet' },
    { path: '/application/material', title: '素材管理', icon: 'Picture' },
    { path: '/application/article', title: '文章资讯', icon: 'Document' },
    { path: '/application/notice', title: '消息管理', icon: 'Bell' },
    { path: '/application/announcement', title: '商城公告', icon: 'Promotion' },
    { path: '/application/collect', title: '商品采集', icon: 'Download' },
    { path: '/application/kefu', title: '客服设置', icon: 'Service' },
  ]},
  { key: 'decoration', title: '装修管理', icon: 'Brush', children: [
    { path: '/decoration/index', title: '页面装修', icon: 'Monitor' },
    { path: '/decoration/page', title: '装修页面', icon: 'Document' },
    { path: '/decoration/tabbar', title: '底部导航', icon: 'Menu' },
    { path: '/decoration/template', title: '模板管理', icon: 'Files' },
    { path: '/decoration/banner', title: '轮播图', icon: 'Picture' },
    { path: '/decoration/navigation', title: '导航图标', icon: 'Grid' },
  ]},
  { key: 'finance', title: '财务管理', icon: 'Money', children: [
    { path: '/finance/income', title: '订单收款', icon: 'Money' },
    { path: '/finance/refund', title: '退款记录', icon: 'Refund' },
    { path: '/finance/withdraw', title: '提现管理', icon: 'CreditCard' },
    { path: '/finance/settlement', title: '商家结算', icon: 'AccountBook' },
    { path: '/finance/settlement-record', title: '结算记录', icon: 'Document' },
  ]},
  { key: 'channel', title: '渠道设置', icon: 'Connection', children: [
    { path: '/channel/setting', title: '渠道配置', icon: 'Setting' },
    { path: '/channel/oa-menu', title: '公众号菜单', icon: 'Menu' },
    { path: '/channel/oa-reply', title: '公众号回复', icon: 'ChatDotRound' },
  ]},
  { key: 'org', title: '组织管理', icon: 'OfficeBuilding', children: [
    { path: '/org/index', title: '组织架构', icon: 'Share' },
    { path: '/permission/dept', title: '部门管理', icon: 'OfficeBuilding' },
  ]},
  { key: 'permission', title: '权限管理', icon: 'Key', children: [
    { path: '/permission/role', title: '角色管理', icon: 'UserFilled' },
    { path: '/permission/menu', title: '菜单管理', icon: 'Menu' },
    { path: '/permission/admin', title: '管理员', icon: 'User' },
    { path: '/permission/job', title: '岗位管理', icon: 'Postcard' },
  ]},
  { key: 'system', title: '系统设置', icon: 'Setting', children: [
    { path: '/system/config', title: '基础配置', icon: 'Setting' },
    { path: '/system/payment', title: '支付配置', icon: 'Wallet' },
    { path: '/system/express', title: '物流配置', icon: 'Van' },
    { path: '/system/delivery-type', title: '配送方式', icon: 'Van' },
    { path: '/system/order-setting', title: '订单设置', icon: 'Setting' },
    { path: '/system/transaction-setting', title: '交易设置', icon: 'Money' },
    { path: '/system/user-setting', title: '用户设置', icon: 'User' },
    { path: '/system/web-setting', title: '网站设置', icon: 'Monitor' },
    { path: '/system/notice-setting', title: '通知设置', icon: 'Bell' },
    { path: '/system/sms-config', title: '短信配置', icon: 'Message' },
    { path: '/system/storage', title: '存储设置', icon: 'Folder' },
    { path: '/system/pay-scene', title: '支付场景', icon: 'Wallet' },
    { path: '/system/dict', title: '数据字典', icon: 'Collection' },
    { path: '/system/hot-search', title: '热门搜索', icon: 'Search' },
    { path: '/system/crontab', title: '定时任务', icon: 'AlarmClock' },
    { path: '/system/area', title: '地区管理', icon: 'Location' },
    { path: '/system/express-template', title: '快递模板', icon: 'Van' },
    { path: '/system/file', title: '文件管理', icon: 'Folder' },
    { path: '/system/cache', title: '系统缓存', icon: 'Refresh' },
    { path: '/system/info', title: '系统信息', icon: 'InfoFilled' },
    { path: '/system/upgrade', title: '系统升级', icon: 'Upload' },
    { path: '/system/log', title: '操作日志', icon: 'Document' },
  ]},
  { key: 'tools', title: '开发工具', icon: 'Tools', children: [
    { path: '/tools/generator', title: '代码生成器', icon: 'MagicStick' },
    { path: '/tools/export', title: '数据导出', icon: 'Download' },
  ]},
]

const handleLocale = cmd => { appStore.setLocale(cmd); locale.value = cmd; ElMessage.success(cmd === 'zh' ? '已切换为中文' : 'Switched to English') }
const handleCommand = async cmd => {
  if (cmd === 'logout') { await ElMessageBox.confirm('确定退出登录？', '提示', { type: 'warning' }); await userStore.logout(); router.push('/login') }
}
</script>
<style scoped>
.admin-layout { height: 100vh; }
.sidebar { background: #001529; transition: width 0.3s; overflow: hidden; }
.logo { height: 60px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; font-weight: bold; border-bottom: 1px solid #1f2d3d; cursor: pointer; }
.menu-scroll { height: calc(100vh - 60px); }
.sidebar :deep(.el-menu) { border-right: none; }
.header { background: #fff; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 0 20px; }
.header-left { display: flex; align-items: center; gap: 16px; }
.collapse-btn { font-size: 20px; cursor: pointer; color: #606266; }
.header-right { display: flex; align-items: center; gap: 16px; }
.user-info { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.username { font-size: 14px; color: #303133; }
.main-content { background: #f0f2f5; padding: 20px; overflow-y: auto; }
</style>
