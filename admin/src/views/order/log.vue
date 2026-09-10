<template>
  <el-card shadow="never">
    <template #header><span>订单操作日志</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="订单号"><el-input v-model="query.order_no" placeholder="订单号" clearable style="width:180px" /></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="ID" width="80" align="center" />
      <el-table-column prop="order_no" label="订单号" width="200" />
      <el-table-column prop="action" label="操作" width="120" align="center" />
      <el-table-column prop="remark" label="备注" min-width="200" show-overflow-tooltip />
      <el-table-column prop="operator" label="操作人" width="120" />
      <el-table-column label="操作类型" width="100" align="center"><template #default="{row}">{{row.operator_type===1?'管理员':row.operator_type===2?'商家':'用户'}}</template></el-table-column>
      <el-table-column prop="created_at" label="操作时间" width="170" align="center" />
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getOrderLogList } from '@/api/tools'
const list = ref([]); const loading = ref(false)
const query = reactive({ order_no:'' })
const fetchList = async () => { loading.value=true; try { const res = await getOrderLogList(query); list.value = res.data.list||[] } finally { loading.value=false } }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
