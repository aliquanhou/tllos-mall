<template>
  <div class="user-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词"><el-input v-model="searchForm.keyword" placeholder="手机号/昵称" clearable style="width:180px" @keyup.enter="fetchList" /></el-form-item>
        <el-form-item label="状态"><el-select v-model="searchForm.status" placeholder="全部" clearable style="width:120px"><el-option label="正常" :value="1" /><el-option label="禁用" :value="0" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetSearch">重置</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-card shadow="never" style="margin-top:16px">
      <template #header><div class="card-header"><span>用户列表（共 {{ total }} 人）</span></div></template>
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="头像" width="80"><template #default="{row}"><el-avatar :src="row.avatar" size="small">{{row.nickname?.charAt(0)||'U'}}</el-avatar></template></el-table-column>
        <el-table-column prop="nickname" label="昵称" width="120" />
        <el-table-column prop="mobile" label="手机号" width="130" />
        <el-table-column label="等级" width="100"><template #default="{row}"><el-tag size="small">{{row.level_name||'普通会员'}}</el-tag></template></el-table-column>
        <el-table-column label="余额" width="100" align="right"><template #default="{row}">¥{{row.balance||0}}</template></el-table-column>
        <el-table-column label="积分" width="100" align="right"><template #default="{row}">{{row.points||0}}</template></el-table-column>
        <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleToggleStatus(row)" /></template></el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="170" />
        <el-table-column label="操作" width="150" fixed="right"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" @click="handleDetail(row)">详情</el-button></template></el-table-column>
      </el-table>
      <div class="pagination"><el-pagination v-model:current-page="page" v-model:page-size="limit" :page-sizes="[10,20,50]" :total="total" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" /></div>
    </el-card>
    <el-dialog v-model="dialogVisible" title="编辑用户" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="昵称"><el-input v-model="form.nickname" /></el-form-item>
        <el-form-item label="手机号"><el-input v-model="form.mobile" /></el-form-item>
        <el-form-item label="头像"><el-input v-model="form.avatar" /></el-form-item>
        <el-form-item label="余额"><el-input-number v-model="form.balance" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="积分"><el-input-number v-model="form.points" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">保存</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getUserList, updateUser, getUserDetail } from '@/api/user'
const list = ref([]); const total = ref(0); const loading = ref(false)
const page = ref(1); const limit = ref(20)
const searchForm = reactive({ keyword:'', status:null })
const dialogVisible = ref(false)
const form = ref({ id:null, nickname:'', mobile:'', avatar:'', balance:0, points:0, status:1 })
const fetchList = async () => { loading.value=true; try { const res = await getUserList({page:page.value,limit:limit.value,...searchForm}); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetSearch = () => { searchForm.keyword=''; searchForm.status=null; page.value=1; fetchList() }
const handleEdit = row => { form.value={...row}; dialogVisible.value=true }
const handleDetail = async row => { const res = await getUserDetail(row.id); form.value=res.data||row; dialogVisible.value=true }
const handleSubmit = async () => { await updateUser(form.value.id,form.value); ElMessage.success('保存成功'); dialogVisible.value=false; fetchList() }
const handleToggleStatus = async row => { await updateUser(row.id,{status:row.status}); ElMessage.success('状态更新成功') }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:0}.card-header{display:flex;justify-content:space-between;align-items:center}.pagination{margin-top:16px;display:flex;justify-content:flex-end}</style>
