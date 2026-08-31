<template>
  <el-card shadow="never">
    <template #header><span>拼团开团记录</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="用户/订单号" clearable style="width:180px" /></el-form-item>
      <el-form-item label="状态"><el-select v-model="query.status" placeholder="全部" clearable style="width:120px"><el-option :value="0" label="进行中" /><el-option :value="1" label="拼团成功" /><el-option :value="2" label="拼团失败" /></el-select></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="团ID" width="80" align="center" />
      <el-table-column prop="user_name" label="团长" width="100" />
      <el-table-column prop="order_no" label="订单号" width="180" />
      <el-table-column label="需/已参团" width="120" align="center"><template #default="{row}">{{row.join_num}}/{{row.need_num}}</template></el-table-column>
      <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="row.status===0?'warning':row.status===1?'success':'info'" size="small">{{row.status===0?'进行中':row.status===1?'成功':'失败'}}</el-tag></template></el-table-column>
      <el-table-column prop="expire_time" label="过期时间" width="170" align="center" />
      <el-table-column prop="created_at" label="开团时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getPtOpenList } from '@/api/systemExtra'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const fetchList = async () => { loading.value=true; try { const res = await getPtOpenList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
