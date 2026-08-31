<template>
  <el-card shadow="never">
    <template #header><span>用户提现</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="用户/账号" clearable style="width:180px" /></el-form-item>
      <el-form-item label="状态"><el-select v-model="query.status" placeholder="全部" clearable style="width:120px"><el-option :value="0" label="待审核" /><el-option :value="1" label="已通过" /><el-option :value="2" label="已拒绝" /><el-option :value="3" label="已打款" /></el-select></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="user_name" label="用户" width="100" />
      <el-table-column label="提现金额" width="110" align="center"><template #default="{row}"><span style="color:#f56c6c">¥{{row.amount}}</span></template></el-table-column>
      <el-table-column label="手续费" width="90" align="center"><template #default="{row}">¥{{row.fee}}</template></el-table-column>
      <el-table-column label="实际到账" width="110" align="center"><template #default="{row}"><span style="color:#67c23a;font-weight:bold">¥{{row.actual_amount}}</span></template></el-table-column>
      <el-table-column prop="pay_type" label="提现方式" width="100" align="center" />
      <el-table-column prop="account" label="收款账号" width="150" show-overflow-tooltip />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===0?'warning':row.status===1?'success':row.status===2?'danger':'info'" size="small">{{row.status===0?'待审核':row.status===1?'已通过':row.status===2?'已拒绝':'已打款'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="180" align="center" fixed="right"><template #default="{row}">
        <el-button v-if="row.status===0" size="small" type="success" @click="handleAudit(row,1)">通过</el-button>
        <el-button v-if="row.status===0" size="small" type="danger" @click="handleAudit(row,2)">拒绝</el-button>
        <el-button v-if="row.status===1" size="small" type="primary" @click="handlePay(row)">打款</el-button>
      </template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getWithdrawList, auditWithdraw, payWithdraw } from '@/api/userCenter'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const fetchList = async () => { loading.value=true; try { const res = await getWithdrawList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const handleAudit = async (row, status) => { await ElMessageBox.confirm(`确定${status===1?'通过':'拒绝'}提现申请？`,'提示',{type:status===1?'success':'warning'}); await auditWithdraw(row.id,{status}); ElMessage.success(status===1?'已通过':'已拒绝'); fetchList() }
const handlePay = async row => { await ElMessageBox.confirm('确认已打款？','提示',{type:'info'}); await payWithdraw(row.id); ElMessage.success('已打款'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
