<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>消息管理</span><el-button type="primary" @click="handleAdd">新增消息</el-button></div></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="消息标题" clearable style="width:180px" /></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="title" label="消息标题" min-width="200" />
      <el-table-column label="类型" width="100" align="center"><template #default="{row}"><el-tag :type="row.type===1?'warning':'info'" size="small">{{row.type===1?'系统通知':'活动消息'}}</el-tag></template></el-table-column>
      <el-table-column prop="content" label="消息内容" min-width="250" show-overflow-tooltip />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'已发布':'草稿'}}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" align="center" />
      <el-table-column label="操作" width="180" align="center" fixed="right"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑消息':'新增消息'" width="600px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="消息标题"><el-input v-model="form.title" /></el-form-item>
        <el-form-item label="类型"><el-select v-model="form.type" style="width:100%"><el-option :value="1" label="系统通知" /><el-option :value="2" label="活动消息" /></el-select></el-form-item>
        <el-form-item label="消息内容"><el-input v-model="form.content" type="textarea" :rows="4" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getNoticeList, createNotice, updateNotice, deleteNotice } from '@/api/application'
const list = ref([]); const total = ref(0); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, title:'', type:1, content:'', sort:0, status:1 })
const query = reactive({ page:1, limit:20, keyword:'' })
const fetchList = async () => { loading.value=true; try { const res = await getNoticeList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:''}); fetchList() }
const handleAdd = () => { isEdit.value=false; form.value={id:null,title:'',type:1,content:'',sort:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if (!form.value.title) { ElMessage.warning('请输入消息标题'); return }; if(isEdit.value){await updateNotice(form.value.id,form.value);ElMessage.success('更新成功')}else{await createNotice(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除消息"${row.title}"？`,'提示',{type:'warning'}); await deleteNotice(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}.search-form{margin-bottom:16px}</style>
