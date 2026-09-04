<template>
  <el-card shadow="never">
    <template #header><span>退款记录</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="退款单号/订单号/用户" clearable style="width:200px" /></el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" placeholder="全部" clearable style="width:120px">
          <el-option :value="0" label="待审核" /><el-option :value="1" label="已通过" /><el-option :value="2" label="已拒绝" /><el-option :value="3" label="已完成" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val">¥{{ stats.total_amount }}</div><div class="lbl">退款总额</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total_count }}</div><div class="lbl">退款笔数</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">{{ stats.pending_count }}</div><div class="lbl">待审核</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#67c23a">{{ stats.approved_count }}</div><div class="lbl">已通过</div></div></el-card></el-col>
    </el-row>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="refund_no" label="退款单号" width="180" />
      <el-table-column prop="order_no" label="订单号" width="180" />
      <el-table-column label="用户" width="130"><template #default="{ row }">{{ row.nickname }}<br /><span style="color:#909399;font-size:12px">{{ row.mobile }}</span></template></el-table-column>
      <el-table-column label="退款类型" width="90" align="center"><template #default="{ row }">{{ row.refund_type === 1 ? '仅退款' : '退货退款' }}</template></el-table-column>
      <el-table-column prop="reason" label="退款原因" min-width="120" show-overflow-tooltip />
      <el-table-column label="退款金额" width="110" align="center"><template #default="{ row }"><span style="color:#f56c6c;font-weight:bold">¥{{ row.refund_amount }}</span></template></el-table-column>
      <el-table-column label="状态" width="90" align="center"><template #default="{ row }"><el-tag :type="statusType[row.status]" size="small">{{ statusText[row.status] }}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="申请时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getRefundList } from '@/api/finance'
const list = ref([]); const total = ref(0); const loading = ref(false)
const stats = ref({ total_count:0, total_amount:0, pending_count:0, approved_count:0 })
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const statusText = { 0:'待审核', 1:'已通过', 2:'已拒绝', 3:'已完成' }
const statusType = { 0:'warning', 1:'success', 2:'danger', 3:'info' }
const fetchList = async () => { loading.value = true; try { const res = await getRefundList(query); list.value = res.data.list||[]; total.value = res.data.total||0; stats.value = res.data.stats||stats.value } finally { loading.value = false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',status:null}); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}.stats-row{margin-bottom:16px}.stat{text-align:center;padding:8px 0}.stat .val{font-size:22px;font-weight:bold;color:#303133}.stat .lbl{font-size:13px;color:#909399;margin-top:4px}</style>
