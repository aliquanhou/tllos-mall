<template>
  <div class="dashboard">
    <el-alert title="欢迎使用 TLLOS 商城管理后台" type="info" :closable="false" style="margin-bottom:20px" />

    <!-- 全局搜索框 -->
    <div class="global-search">
      <el-input
        v-model="searchKeyword"
        placeholder="搜索功能或配置（如：商品、订单、支付、地图、短信、物流...）"
        clearable
        size="large"
        class="search-input"
        @input="handleSearch"
        @focus="showSearchResults = true"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>

      <!-- 搜索结果下拉 -->
      <div v-if="showSearchResults && filteredSearchItems.length > 0" class="search-results">
        <div class="search-results-header">
          <span>找到 {{ filteredSearchItems.length }} 个结果</span>
        </div>
        <div class="search-results-list">
          <div
            v-for="item in filteredSearchItems"
            :key="item.path"
            class="search-result-item"
            @click="goToSearchResult(item)"
          >
            <div class="result-icon" :style="{ background: item.color + '15', color: item.color }">
              <el-icon><component :is="item.icon" /></el-icon>
            </div>
            <div class="result-info">
              <div class="result-title">{{ item.title }}</div>
              <div class="result-desc">{{ item.description }}</div>
            </div>
            <div class="result-category">{{ item.category }}</div>
          </div>
        </div>
      </div>

      <!-- 无结果 -->
      <div v-if="showSearchResults && searchKeyword && filteredSearchItems.length === 0" class="search-results no-result">
        <el-icon :size="32" color="#ddd"><Search /></el-icon>
        <p>未找到匹配的功能或配置</p>
        <el-button type="primary" link @click="goToConfigCenter">进入配置中心 →</el-button>
      </div>

      <!-- 热门搜索 -->
      <div v-if="!searchKeyword" class="hot-search-tags">
        <span class="tag-label">快捷入口：</span>
        <el-tag
          v-for="tag in hotSearchTags"
          :key="tag.keyword"
          class="hot-tag"
          @click="quickSearch(tag.keyword)"
        >
          {{ tag.label }}
        </el-tag>
      </div>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="20">
      <el-col :span="6" v-for="card in statCards" :key="card.title">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-icon" :style="{ background: card.color }">
            <el-icon :size="28" color="#fff"><component :is="card.icon" /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ card.value }}</div>
            <div class="stat-label">{{ card.title }}</div>
            <div class="stat-sub" v-if="card.sub">{{ card.sub }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 待处理事项 -->
    <el-row :gutter="20" style="margin-top:20px">
      <el-col :span="24">
        <el-card>
          <template #header><span>待处理事项</span></template>
          <el-row :gutter="20">
            <el-col :span="4" v-for="item in pendingItems" :key="item.title">
              <div class="pending-item" @click="goTo(item.path)">
                <div class="pending-count" :style="{color: item.color}">{{ item.count }}</div>
                <div class="pending-title">{{ item.title }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>
      </el-col>
    </el-row>

    <!-- 销售趋势 -->
    <el-row :gutter="20" style="margin-top:20px">
      <el-col :span="24">
        <el-card>
          <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span>近7天销售趋势</span>
              <span style="font-size:12px;color:#909399">销售额 ¥{{ totalSalesTrend }} / 订单 {{ totalOrdersTrend }} 单</span>
            </div>
          </template>
          <div class="sales-trend-chart">
            <div class="trend-bar" v-for="(day, index) in salesTrend.days" :key="index">
              <div class="bar-wrapper">
                <div class="bar" :style="{ height: getBarHeight(salesTrend.sales[index]) + '%' }" :title="'¥' + salesTrend.sales[index]"></div>
              </div>
              <div class="bar-label">{{ day.slice(5) }}</div>
              <div class="bar-value" v-if="salesTrend.orders[index] > 0">{{ salesTrend.orders[index] }}单</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 最近订单 + 系统信息 -->
    <el-row :gutter="20" style="margin-top:20px">
      <el-col :span="16">
        <el-card>
          <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span>最近订单</span>
              <el-button type="primary" link @click="$router.push('/order/list')">查看全部</el-button>
            </div>
          </template>
          <el-table :data="recentOrders" stripe v-loading="loading">
            <el-table-column prop="order_no" label="订单号" width="180" />
            <el-table-column prop="customer" label="客户" />
            <el-table-column prop="total_amount" label="金额" width="100">
              <template #default="{ row }">¥{{ row.total_amount }}</template>
            </el-table-column>
            <el-table-column prop="status_text" label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.status_type" size="small">{{ row.status_text }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="时间" width="170" />
          </el-table>
          <el-empty v-if="!loading && recentOrders.length===0" description="暂无订单" />
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card>
          <template #header><span>系统信息</span></template>
          <el-descriptions :column="1" border size="small">
            <el-descriptions-item label="系统版本">TLLOS Mall v{{ systemInfo.version || '1.0.0' }}</el-descriptions-item>
            <el-descriptions-item label="后端框架">Laravel 11 / PHP {{ systemInfo.php_version || '8.2' }}</el-descriptions-item>
            <el-descriptions-item label="前端框架">Vue3 + Element Plus</el-descriptions-item>
            <el-descriptions-item label="数据库">{{ systemInfo.mysql_version || 'MySQL' }}</el-descriptions-item>
            <el-descriptions-item label="Web服务器">{{ systemInfo.server_software || 'Nginx' }}</el-descriptions-item>
            <el-descriptions-item label="多端支持">PC响应式 / 小程序 / Flutter APK</el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import request from '@/utils/request'
import {
  Search, User, List, Wallet, Goods, Setting, LocationFilled,
  Van, Message, Folder, Monitor, Bell, Clock, DataAnalysis,
  Files, Refresh, ShoppingCart, Lock, Tools, Box
} from '@element-plus/icons-vue'

const router = useRouter()
const loading = ref(false)
const stats = ref({})
const recentOrders = ref([])
const systemInfo = ref({})
const salesTrend = ref({ days: [], sales: [], orders: [] })

// ========== 全局搜索 ==========
const searchKeyword = ref('')
const showSearchResults = ref(false)

// 搜索项列表（配置项 + 常用功能）
const searchItems = [
  // 常用功能
  { title: '商品列表', description: '管理所有商品，支持上下架、编辑', path: '/product/list', icon: Goods, color: '#f56c6c', category: '商品管理' },
  { title: '商品分类', description: '商品分类树管理', path: '/product/category', icon: List, color: '#f56c6c', category: '商品管理' },
  { title: '订单列表', description: '查看和管理所有订单', path: '/order/list', icon: ShoppingCart, color: '#67c23a', category: '订单管理' },
  { title: '用户列表', description: '管理平台用户', path: '/user/list', icon: User, color: '#409eff', category: '用户管理' },
  { title: '商家管理', description: '入驻商家管理', path: '/merchant/list', icon: Van, color: '#e6a23c', category: '商家管理' },
  { title: '装修管理', description: '页面装修模板管理', path: '/decorate/list', icon: Monitor, color: '#909399', category: '装修管理' },
  // 配置项
  { title: '支付配置', description: '微信、支付宝、余额、积分支付配置', path: '/system/payment', icon: Wallet, color: '#67c23a', category: '系统配置' },
  { title: '地图服务配置', description: '高德、腾讯、百度地图Key配置', path: '/system/map-config', icon: LocationFilled, color: '#e6a23c', category: '系统配置' },
  { title: '短信配置', description: '短信服务商配置', path: '/system/sms-config', icon: Message, color: '#909399', category: '系统配置' },
  { title: '物流配置', description: '物流公司管理', path: '/system/express', icon: Van, color: '#f56c6c', category: '系统配置' },
  { title: '存储配置', description: 'OSS、COS、七牛云存储配置', path: '/system/storage-config', icon: Folder, color: '#8e44ad', category: '系统配置' },
  { title: '基础配置', description: '系统基础参数设置', path: '/system/config', icon: Setting, color: '#409eff', category: '系统配置' },
  { title: '网站设置', description: '网站名称、LOGO、备案信息', path: '/system/web-setting', icon: Monitor, color: '#409eff', category: '系统配置' },
  { title: '订单设置', description: '订单规则与自动处理', path: '/system/order-setting', icon: ShoppingCart, color: '#67c23a', category: '系统配置' },
  { title: '用户设置', description: '用户注册与登录配置', path: '/system/user-setting', icon: User, color: '#409eff', category: '系统配置' },
  { title: '通知设置', description: '系统通知模板配置', path: '/system/notice-setting', icon: Bell, color: '#909399', category: '系统配置' },
  { title: '交易设置', description: '交易规则与手续费配置', path: '/system/transaction-setting', icon: Lock, color: '#67c23a', category: '系统配置' },
  { title: '配送方式', description: '配送方式管理', path: '/system/delivery-type', icon: Van, color: '#f56c6c', category: '系统配置' },
  { title: '快递模板', description: '运费模板配置', path: '/system/express-template', icon: Box, color: '#f56c6c', category: '系统配置' },
  { title: '支付场景', description: '支付场景与方式配置', path: '/system/pay-scene', icon: ShoppingCart, color: '#67c23a', category: '系统配置' },
  { title: '数据字典', description: '系统字典数据管理', path: '/system/dict', icon: Tools, color: '#409eff', category: '系统配置' },
  { title: '热门搜索', description: '搜索关键词管理', path: '/system/hot-search', icon: Search, color: '#409eff', category: '系统配置' },
  { title: '定时任务', description: '系统定时任务管理', path: '/system/crontab', icon: Clock, color: '#409eff', category: '系统配置' },
  { title: '地区管理', description: '省市区地区数据管理', path: '/system/area', icon: LocationFilled, color: '#e6a23c', category: '系统配置' },
  { title: '系统缓存', description: '缓存清理与管理', path: '/system/cache', icon: Refresh, color: '#409eff', category: '系统配置' },
  { title: '系统日志', description: '操作日志查看', path: '/system/log', icon: Files, color: '#409eff', category: '系统配置' },
  { title: '系统信息', description: '服务器信息、PHP版本', path: '/system/info', icon: DataAnalysis, color: '#409eff', category: '系统配置' },
  { title: '配置中心', description: '所有系统配置统一管理', path: '/system/config-center', icon: Setting, color: '#409eff', category: '系统配置' },
]

// 热门搜索标签
const hotSearchTags = [
  { label: '商品管理', keyword: '商品' },
  { label: '订单管理', keyword: '订单' },
  { label: '支付配置', keyword: '支付' },
  { label: '地图配置', keyword: '地图' },
  { label: '短信配置', keyword: '短信' },
  { label: '物流配置', keyword: '物流' },
  { label: '存储配置', keyword: '存储' },
  { label: '用户管理', keyword: '用户' },
]

// 过滤搜索结果
const filteredSearchItems = computed(() => {
  if (!searchKeyword.value) return searchItems.slice(0, 8)
  const keyword = searchKeyword.value.toLowerCase()
  return searchItems.filter(item =>
    item.title.toLowerCase().includes(keyword) ||
    item.description.toLowerCase().includes(keyword) ||
    item.category.toLowerCase().includes(keyword) ||
    item.path.toLowerCase().includes(keyword)
  )
})

// 处理搜索
const handleSearch = () => {
  showSearchResults.value = true
}

// 快捷搜索
const quickSearch = (keyword) => {
  searchKeyword.value = keyword
  showSearchResults.value = true
}

// 跳转到搜索结果
const goToSearchResult = (item) => {
  showSearchResults.value = false
  searchKeyword.value = ''
  router.push(item.path)
}

// 跳转到配置中心
const goToConfigCenter = () => {
  showSearchResults.value = false
  searchKeyword.value = ''
  router.push('/system/config-center')
}

// 点击外部关闭搜索结果
const closeSearchResults = () => {
  setTimeout(() => {
    showSearchResults.value = false
  }, 200)
}


const statCards = computed(() => [
  { title: '用户总数', value: stats.value.total_users || 0, icon: 'User', color: '#409eff', sub: `今日新增 ${stats.value.today_new_users || 0}` },
  { title: '订单总数', value: stats.value.total_orders || 0, icon: 'List', color: '#67c23a', sub: `今日 ${stats.value.today_orders || 0} 单` },
  { title: '销售总额', value: '¥' + (stats.value.total_sales || 0), icon: 'Money', color: '#e6a23c', sub: `今日 ¥${stats.value.today_sales || 0}` },
  { title: '商品总数', value: stats.value.total_products || 0, icon: 'Goods', color: '#f56c6c', sub: `商家 ${stats.value.total_merchants || 0} 家` },
])

const totalSalesTrend = computed(() => {
  return (salesTrend.value.sales || []).reduce((a, b) => a + Number(b), 0).toFixed(2)
})
const totalOrdersTrend = computed(() => {
  return (salesTrend.value.orders || []).reduce((a, b) => a + Number(b), 0)
})
const getBarHeight = (value) => {
  const max = Math.max(...(salesTrend.value.sales || [1]).map(Number))
  return max > 0 ? (Number(value) / max * 100) : 0
}

const pendingItems = computed(() => [
  { title: '待发货订单', count: stats.value.pending_orders || 0, color: '#e6a23c', path: '/order/list' },
  { title: '售后申请', count: stats.value.pending_after_sales || 0, color: '#f56c6c', path: '/order/after-sale' },
  { title: '商家入驻', count: stats.value.pending_merchants || 0, color: '#409eff', path: '/merchant/audit' },
  { title: '提现申请', count: stats.value.pending_withdraws || 0, color: '#67c23a', path: '/finance/withdraw' },
  { title: '库存预警', count: stats.value.stock_warning_count || 0, color: '#f56c6c', path: '/product/stock-warning' },
  { title: '本月订单', count: stats.value.month_orders || 0, color: '#909399', path: '/order/list' },
])

const goTo = (path) => router.push(path)

const loadStats = async () => {
  const res = await request({ url: '/admin/dashboard/stats' })
  stats.value = res.data || {}
}

const loadRecentOrders = async () => {
  loading.value = true
  try {
    const res = await request({ url: '/admin/dashboard/recent-orders' })
    recentOrders.value = res.data?.list || []
  } finally {
    loading.value = false
  }
}

const loadSalesTrend = async () => {
  try {
    const res = await request({ url: '/admin/dashboard/sales-trend' })
    salesTrend.value = res.data || { days: [], sales: [], orders: [] }
  } catch (e) {}
}

const loadSystemInfo = async () => {
  try {
    const res = await request({ url: '/admin/system-info' })
    systemInfo.value = res.data?.info || {}
  } catch (e) {}
}

onMounted(() => {
  loadStats()
  loadRecentOrders()
  loadSalesTrend()
  loadSystemInfo()
})
</script>

<style scoped>
.stat-card { margin-bottom: 0; }
.stat-card :deep(.el-card__body) { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.stat-value { font-size: 24px; font-weight: bold; color: #303133; }
.stat-label { font-size: 13px; color: #909399; margin-top: 4px; }
.stat-sub { font-size: 11px; color: #c0c4cc; margin-top: 2px; }
.pending-item { text-align: center; padding: 15px; cursor: pointer; border-radius: 8px; transition: all 0.2s; }
.pending-item:hover { background: #f5f7fa; }
.pending-count { font-size: 28px; font-weight: bold; }
.pending-title { font-size: 13px; color: #606266; margin-top: 5px; }
.sales-trend-chart { display: flex; align-items: flex-end; justify-content: space-around; height: 200px; padding: 20px 10px 0; }
.trend-bar { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; }
.bar-wrapper { flex: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; }
.bar { width: 40%; max-width: 50px; background: linear-gradient(180deg, #409eff 0%, #66b1ff 100%); border-radius: 4px 4px 0 0; min-height: 2px; transition: all 0.3s; }
.bar:hover { background: linear-gradient(180deg, #337ecc 0%, #409eff 100%); }
.bar-label { font-size: 12px; color: #909399; margin-top: 8px; }
.bar-value { font-size: 11px; color: #409eff; margin-top: 2px; }

/* 全局搜索 */
.global-search {
  position: relative;
  margin-bottom: 20px;
}
.search-input {
  max-width: 100%;
}
.search-input :deep(.el-input__wrapper) {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  padding: 4px 15px;
}
.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  margin-top: 8px;
  overflow: hidden;
}
.search-results-header {
  padding: 12px 16px;
  border-bottom: 1px solid #ebeef5;
  font-size: 13px;
  color: #909399;
}
.search-results-list {
  max-height: 400px;
  overflow-y: auto;
}
.search-result-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  transition: all 0.2s;
  border-bottom: 1px solid #f5f7fa;
}
.search-result-item:hover {
  background: #f5f7fa;
}
.search-result-item:last-child {
  border-bottom: none;
}
.result-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  flex-shrink: 0;
}
.result-info {
  flex: 1;
  min-width: 0;
}
.result-title {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 2px;
}
.result-desc {
  font-size: 12px;
  color: #909399;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.result-category {
  font-size: 12px;
  color: #409eff;
  background: #ecf5ff;
  padding: 2px 8px;
  border-radius: 4px;
  flex-shrink: 0;
  margin-left: 12px;
}
.no-result {
  text-align: center;
  padding: 40px 20px;
  color: #909399;
}
.no-result p {
  margin: 12px 0;
}
.hot-search-tags {
  margin-top: 12px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}
.tag-label {
  font-size: 13px;
  color: #909399;
}
.hot-tag {
  cursor: pointer;
  transition: all 0.2s;
}
.hot-tag:hover {
  transform: scale(1.05);
}

@media (max-width: 768px) {
  .search-results {
    position: fixed;
    top: auto;
    left: 10px;
    right: 10px;
  }
  .result-category {
    display: none;
  }
}
</style>
