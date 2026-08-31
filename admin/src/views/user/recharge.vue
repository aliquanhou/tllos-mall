<template>
  <el-card shadow="never">
    <template #header><span>用户充值</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="订单号/用户" clearable style="width:180px" /></el-form-item>
      <el-form-item label="状态"><el-select v-model="query.status" placeholder="全部" clearable style="width:120px"><el-option :value="0" label="待支付" /><el-option :value="1" label="已支付" /><el-option :value="2" label="已取消" /></el-select></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="order_no" label="充值订单号" width="180" />
      <el-table-column prop="user_name" label="用户" width="100" />
      <el-table-column label="充值金额" width="120" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">¥{{row.amount}}</span></template></el-table-column>
      <el-table-column prop="pay_type" label="支付方式" width="100" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':row.status===2?'info':'warning'" size="small">{{row.status===1?'已支付':row.status===2?'已取消':'待支付'}}</el-tag></template></el-table-column>
      <el-table-column prop="pay_time" label="支付时间" width="170" align="center" />
      <el-table-column prop="created_at" label="创建时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getRechargeList } from '@/api/userCenter'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const fetchList = async () => { loading.value=true; try { const res = await getRechargeList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
