<template>
  <el-card shadow="never">
    <template #header><span>分销商管理</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="昵称/手机号" clearable style="width:180px" /></el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" placeholder="全部" clearable style="width:120px">
          <el-option :value="0" label="待审核" /><el-option :value="1" label="正常" /><el-option :value="2" label="已拒绝" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="nickname" label="昵称" width="120" />
      <el-table-column prop="mobile" label="手机号" width="130" />
      <el-table-column prop="level_name" label="等级" width="100" align="center" />
      <el-table-column label="累计收益" width="110" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">¥{{row.total_income}}</span></template></el-table-column>
      <el-table-column label="可用佣金" width="110" align="center"><template #default="{row}">¥{{row.available_income}}</template></el-table-column>
      <el-table-column prop="total_orders" label="订单数" width="80" align="center" />
      <el-table-column prop="total_members" label="团队人数" width="90" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':row.status===2?'danger':'warning'" size="small">{{row.status===1?'正常':row.status===2?'已拒绝':'待审核'}}</el-tag></template></el-table-column>
      <el-table-column prop="join_time" label="加入时间" width="170" align="center" />
      <el-table-column label="操作" width="150" align="center" fixed="right">
        <template #default="{row}">
          <el-button v-if="row.status===0" size="small" type="success" @click="handleAudit(row,1)">通过</el-button>
          <el-button v-if="row.status===0" size="small" type="danger" @click="handleAudit(row,2)">拒绝</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAgentList, auditAgent } from '@/api/distribute'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const fetchList = async () => { loading.value=true; try { const res = await getAgentList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',status:null}); fetchList() }
const handleAudit = async (row, status) => { await ElMessageBox.confirm(`确定${status===1?'通过':'拒绝'}分销商"${row.nickname}"？`,'审核',{type:status===1?'success':'warning'}); await auditAgent(row.id,{status}); ElMessage.success(status===1?'审核通过':'已拒绝'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
