<template>
  <el-card shadow="never">
    <template #header><span>充值管理</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="昵称/手机号" clearable style="width:180px" /></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="nickname" label="用户昵称" width="150" />
      <el-table-column prop="mobile" label="手机号" width="130" />
      <el-table-column label="账户余额" width="120" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">¥{{row.balance||0}}</span></template></el-table-column>
      <el-table-column label="累计充值" width="120" align="center"><template #default="{row}">¥{{row.total_recharge||0}}</template></el-table-column>
      <el-table-column label="累计消费" width="120" align="center"><template #default="{row}">¥{{row.total_consume||0}}</template></el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getDepositList } from '@/api/application'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'' })
const fetchList = async () => { loading.value=true; try { const res = await getDepositList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:''}); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
