<template>
  <el-card shadow="never">
    <template #header><span>分销概览</span></template>
    <el-row :gutter="16" class="stats-row">
      <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total_agents }}</div><div class="lbl">分销商总数</div></div></el-card></el-col>
      <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#67c23a">{{ stats.active_agents }}</div><div class="lbl">活跃分销商</div></div></el-card></el-col>
      <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">{{ stats.pending_agents }}</div><div class="lbl">待审核</div></div></el-card></el-col>
      <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total_orders }}</div><div class="lbl">分销订单</div></div></el-card></el-col>
      <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#f56c6c">¥{{ stats.total_commission }}</div><div class="lbl">累计佣金</div></div></el-card></el-col>
      <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total_goods }}</div><div class="lbl">分销商品</div></div></el-card></el-col>
    </el-row>
    <el-row :gutter="16">
      <el-col :span="12">
        <el-card shadow="never"><template #header><span>最近分销订单</span></template>
          <el-table :data="recentOrders" size="small" border>
            <el-table-column prop="order_no" label="订单号" width="170" />
            <el-table-column prop="agent_name" label="分销商" width="100" />
            <el-table-column label="订单金额" width="100" align="center"><template #default="{row}">¥{{row.order_amount}}</template></el-table-column>
            <el-table-column label="佣金" width="100" align="center"><template #default="{row}"><span style="color:#f56c6c">¥{{row.commission}}</span></template></el-table-column>
            <el-table-column label="状态" width="80" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':row.status===2?'info':'warning'" size="small">{{row.status===1?'已结算':row.status===2?'已取消':'待结算'}}</el-tag></template></el-table-column>
          </el-table>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card shadow="never"><template #header><span>分销商排行榜</span></template>
          <el-table :data="topAgents" size="small" border>
            <el-table-column type="index" label="排名" width="60" align="center" />
            <el-table-column prop="nickname" label="分销商" />
            <el-table-column prop="level_name" label="等级" width="100" align="center" />
            <el-table-column label="累计收益" width="120" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">¥{{row.total_income}}</span></template></el-table-column>
            <el-table-column prop="total_orders" label="订单数" width="80" align="center" />
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { getOverview } from '@/api/distribute'
const stats = ref({}); const recentOrders = ref([]); const topAgents = ref([])
const fetchData = async () => { const res = await getOverview(); stats.value = res.data.stats||{}; recentOrders.value = res.data.recent_orders||[]; topAgents.value = res.data.top_agents||[] }
onMounted(fetchData)
</script>
<style scoped>.stats-row{margin-bottom:16px}.stat{text-align:center;padding:8px 0}.stat .val{font-size:22px;font-weight:bold;color:#303133}.stat .lbl{font-size:13px;color:#909399;margin-top:4px}</style>
