<template>
  <el-card shadow="never">
    <template #header><span>订单售后</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="订单号/用户" clearable style="width:180px" /></el-form-item>
      <el-form-item label="类型"><el-select v-model="query.type" placeholder="全部" clearable style="width:120px"><el-option :value="1" label="退款" /><el-option :value="2" label="退货退款" /><el-option :value="3" label="换货" /></el-select></el-form-item>
      <el-form-item label="状态"><el-select v-model="query.status" placeholder="全部" clearable style="width:120px"><el-option :value="0" label="待审核" /><el-option :value="1" label="已通过" /><el-option :value="2" label="已拒绝" /><el-option :value="3" label="已完成" /></el-select></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="order_no" label="订单号" width="170" />
      <el-table-column prop="user_name" label="用户" width="100" />
      <el-table-column label="类型" width="100" align="center"><template #default="{row}">{{row.type===1?'退款':row.type===2?'退货退款':'换货'}}</template></el-table-column>
      <el-table-column prop="reason" label="售后原因" min-width="150" show-overflow-tooltip />
      <el-table-column label="退款金额" width="110" align="center"><template #default="{row}"><span style="color:#f56c6c">¥{{row.refund_amount}}</span></template></el-table-column>
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===0?'warning':row.status===1?'success':row.status===2?'danger':'info'" size="small">{{row.status===0?'待审核':row.status===1?'已通过':row.status===2?'已拒绝':'已完成'}}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="申请时间" width="170" align="center" />
      <el-table-column label="操作" width="180" align="center" fixed="right"><template #default="{row}">
        <el-button v-if="row.status===0" size="small" type="success" @click="handleAudit(row,1)">通过</el-button>
        <el-button v-if="row.status===0" size="small" type="danger" @click="handleAudit(row,2)">拒绝</el-button>
        <el-button v-if="row.status===1" size="small" type="primary" @click="handleComplete(row)">完成</el-button>
      </template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAfterSaleList, auditAfterSale, completeAfterSale } from '@/api/afterSale'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', type:null, status:null })
const fetchList = async () => { loading.value=true; try { const res = await getAfterSaleList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const handleAudit = async (row, status) => { await ElMessageBox.confirm(`确定${status===1?'通过':'拒绝'}售后申请？`,'提示',{type:status===1?'success':'warning'}); await auditAfterSale(row.id,{status}); ElMessage.success(status===1?'已通过':'已拒绝'); fetchList() }
const handleComplete = async row => { await ElMessageBox.confirm('确定完成售后？','提示',{type:'info'}); await completeAfterSale(row.id); ElMessage.success('已完成'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
