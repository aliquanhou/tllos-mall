<template>
  <div class="dashboard">
    <el-alert title="欢迎使用 TLLOS 商城管理后台" type="info" :closable="false" style="margin-bottom:20px" />

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
            <el-descriptions-item label="多端支持">H5 / 小程序 / Flutter APK</el-descriptions-item>
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

const router = useRouter()
const loading = ref(false)
const stats = ref({})
const recentOrders = ref([])
const systemInfo = ref({})

const statCards = computed(() => [
  { title: '用户总数', value: stats.value.total_users || 0, icon: 'User', color: '#409eff', sub: `今日新增 ${stats.value.today_new_users || 0}` },
  { title: '订单总数', value: stats.value.total_orders || 0, icon: 'List', color: '#67c23a', sub: `今日 ${stats.value.today_orders || 0} 单` },
  { title: '销售总额', value: '¥' + (stats.value.total_sales || 0), icon: 'Money', color: '#e6a23c', sub: `今日 ¥${stats.value.today_sales || 0}` },
  { title: '商品总数', value: stats.value.total_products || 0, icon: 'Goods', color: '#f56c6c', sub: `商家 ${stats.value.total_merchants || 0} 家` },
])

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

const loadSystemInfo = async () => {
  try {
    const res = await request({ url: '/system-info' })
    systemInfo.value = res.data?.info || {}
  } catch (e) {}
}

onMounted(() => {
  loadStats()
  loadRecentOrders()
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
</style>
