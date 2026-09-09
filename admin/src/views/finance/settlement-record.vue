<template>
  <el-card shadow="never">
    <template #header><span>结算记录</span></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="settlement_id" label="结算单ID" width="100" align="center" />
      <el-table-column prop="shop_name" label="商家" width="150" />
      <el-table-column prop="order_no" label="订单号" width="180" />
      <el-table-column label="订单金额" width="110" align="center"><template #default="{row}">¥{{row.order_amount}}</template></el-table-column>
      <el-table-column label="平台佣金" width="110" align="center"><template #default="{row}"><span style="color:#f56c6c">¥{{row.commission}}</span></template></el-table-column>
      <el-table-column label="结算金额" width="110" align="center"><template #default="{row}"><span style="color:#67c23a;font-weight:bold">¥{{row.settle_amount}}</span></template></el-table-column>
      <el-table-column prop="created_at" label="结算时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import request from '@/utils/request'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20 })
const fetchList = async () => { loading.value=true; try { const res = await request({url:'/admin/settlement-record',method:'get',params:query}); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
onMounted(fetchList)
</script>
