<template>
  <div class="system-info-page">
    <el-card>
      <template #header><span>系统信息</span></template>
      <el-descriptions :column="2" border>
        <el-descriptions-item label="系统版本">{{ info.version }}</el-descriptions-item>
        <el-descriptions-item label="PHP版本">{{ info.php_version }}</el-descriptions-item>
        <el-descriptions-item label="数据库版本">{{ info.mysql_version }}</el-descriptions-item>
        <el-descriptions-item label="服务器软件">{{ info.server_software }}</el-descriptions-item>
        <el-descriptions-item label="操作系统">{{ info.os }}</el-descriptions-item>
      </el-descriptions>
    </el-card>
    <el-card style="margin-top:20px">
      <template #header><span>数据统计</span></template>
      <el-row :gutter="16">
        <el-col :span="6"><el-card><div class="stat"><div class="val">{{ stats.goods_count }}</div><div class="label">商品总数</div></div></el-card></el-col>
        <el-col :span="6"><el-card><div class="stat"><div class="val">{{ stats.order_count }}</div><div class="label">订单总数</div></div></el-card></el-col>
        <el-col :span="6"><el-card><div class="stat"><div class="val">{{ stats.user_count }}</div><div class="label">用户总数</div></div></el-card></el-col>
        <el-col :span="6"><el-card><div class="stat"><div class="val">{{ stats.merchant_count }}</div><div class="label">商家总数</div></div></el-card></el-col>
      </el-row>
      <el-row :gutter="16" style="margin-top:16px">
        <el-col :span="6"><el-card><div class="stat"><div class="val">{{ stats.today_orders }}</div><div class="label">今日订单</div></div></el-card></el-col>
        <el-col :span="6"><el-card><div class="stat"><div class="val">¥{{ stats.today_sales }}</div><div class="label">今日销售额</div></div></el-card></el-col>
      </el-row>
    </el-card>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
const info = ref({}); const stats = ref({})
onMounted(async () => {
  const res = await request({ url:'/system-info' })
  info.value = res.data?.info || {}; stats.value = res.data?.stats || {}
})
</script>
<style scoped>.stat{text-align:center;padding:10px 0}.val{font-size:28px;font-weight:700;color:#333}.label{color:#999;font-size:14px;margin-top:8px}</style>
