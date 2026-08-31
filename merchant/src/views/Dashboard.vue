<template>
  <div class="dashboard">
    <el-row :gutter="16">
      <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">{{data.today_orders||0}}</div><div class="stat-label">今日订单</div></div></el-card></el-col>
      <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">¥{{data.today_amount||0}}</div><div class="stat-label">今日销售额</div></div></el-card></el-col>
      <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">{{data.pending_ship||0}}</div><div class="stat-label">待发货</div></div></el-card></el-col>
      <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">{{data.pending_refund||0}}</div><div class="stat-label">待处理售后</div></div></el-card></el-col>
    </el-row>
    <el-row :gutter="16" style="margin-top:16px">
      <el-col :span="12"><el-card><template #header>商品概览</template><div class="stat-row"><span>商品总数</span><strong>{{data.goods_count||0}}</strong></div></el-card></el-col>
      <el-col :span="12"><el-card><template #header>累计销售额</template><div class="stat-row"><span>累计金额</span><strong style="color:#f56c6c">¥{{data.total_amount||0}}</strong></div></el-card></el-col>
    </el-row>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
const data = ref({})
onMounted(async () => { const res = await request({ url:'/merchant/workbench' }); data.value = res.data || {} })
</script>
<style scoped>.stat-item{text-align:center;padding:10px 0}.stat-value{font-size:28px;font-weight:700;color:#333}.stat-label{color:#999;font-size:14px;margin-top:8px}.stat-row{display:flex;justify-content:space-between;padding:8px 0}</style>
