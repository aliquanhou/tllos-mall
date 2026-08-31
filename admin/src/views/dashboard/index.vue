<template>
  <div class="dashboard">
    <el-alert :title="t('dashboard.welcome')" type="info" :closable="false" style="margin-bottom:20px" />
    <el-row :gutter="20">
      <el-col :span="6" v-for="card in statCards" :key="card.title">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-icon" :style="{ background: card.color }">
            <el-icon :size="28" color="#fff"><component :is="card.icon" /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ card.value }}</div>
            <div class="stat-label">{{ t(card.title) }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="20" style="margin-top:20px">
      <el-col :span="16">
        <el-card>
          <template #header><span>{{ t('dashboard.recentOrders') }}</span></template>
          <el-table :data="recentOrders" stripe>
            <el-table-column prop="orderNo" label="订单号" width="180" />
            <el-table-column prop="customer" label="客户" />
            <el-table-column prop="amount" label="金额" width="100">
              <template #default="{ row }">¥{{ row.amount }}</template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.statusType" size="small">{{ row.status }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="time" label="时间" />
          </el-table>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card>
          <template #header><span>系统信息</span></template>
          <el-descriptions :column="1" border size="small">
            <el-descriptions-item label="系统">TLLOS Mall v1.0</el-descriptions-item>
            <el-descriptions-item label="后端">Laravel 11 / PHP 8.2</el-descriptions-item>
            <el-descriptions-item label="前端">Vue3 + Element Plus</el-descriptions-item>
            <el-descriptions-item label="数据库">MySQL / Redis</el-descriptions-item>
            <el-descriptions-item label="多端">H5 / 小程序 / Flutter</el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const statCards = [
  { title: 'dashboard.totalUsers', value: '1,286', icon: 'User', color: '#409eff' },
  { title: 'dashboard.totalOrders', value: '3,452', icon: 'List', color: '#67c23a' },
  { title: 'dashboard.totalSales', value: '¥286,500', icon: 'Money', color: '#e6a23c' },
  { title: 'dashboard.totalProducts', value: '568', icon: 'Goods', color: '#f56c6c' }
]
const recentOrders = [
  { orderNo: 'TLL202608310001', customer: '张三', amount: '299.00', status: '已支付', statusType: 'success', time: '2026-08-31 14:30' },
  { orderNo: 'TLL202608310002', customer: '李四', amount: '1,580.00', status: '待发货', statusType: 'warning', time: '2026-08-31 13:15' },
  { orderNo: 'TLL202608310003', customer: '王五', amount: '89.90', status: '已完成', statusType: 'info', time: '2026-08-31 11:20' },
  { orderNo: 'TLL202608310004', customer: '赵六', amount: '2,300.00', status: '退款中', statusType: 'danger', time: '2026-08-31 10:05' }
]
</script>

<style scoped>
.stat-card { margin-bottom: 0; }
.stat-card :deep(.el-card__body) { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.stat-value { font-size: 24px; font-weight: bold; color: #303133; }
.stat-label { font-size: 13px; color: #909399; margin-top: 4px; }
</style>
