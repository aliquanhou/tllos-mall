<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>商城公告</span><el-button type="primary" @click="handleAdd">新增公告</el-button></div></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="title" label="公告标题" min-width="200" />
      <el-table-column label="类型" width="100" align="center"><template #default="{row}"><el-tag :type="row.type===1?'warning':'info'" size="small">{{row.type===1?'系统公告':'活动公告'}}</el-tag></template></el-table-column>
      <el-table-column prop="sort" label="排序" width="80" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'已发布':'草稿'}}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" align="center" />
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑公告':'新增公告'" width="600px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="公告标题"><el-input v-model="form.title" /></el-form-item>
        <el-form-item label="类型"><el-select v-model="form.type" style="width:100%"><el-option :value="1" label="系统公告" /><el-option :value="2" label="活动公告" /></el-select></el-form-item>
        <el-form-item label="公告内容"><el-input v-model="form.content" type="textarea" :rows="5" /></el-form-item>
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
import { getAnnouncementList, createAnnouncement, updateAnnouncement, deleteAnnouncement } from '@/api/announcement'
const list = ref([]); const total = ref(0); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, title:'', type:1, content:'', sort:0, status:1 })
const query = reactive({ page:1, limit:20 })
const fetchList = async () => { loading.value=true; try { const res = await getAnnouncementList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const handleAdd = () => { isEdit.value=false; form.value={id:null,title:'',type:1,content:'',sort:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.title){ElMessage.warning('请输入公告标题');return}; if(isEdit.value){await updateAnnouncement(form.value.id,form.value);ElMessage.success('更新成功')}else{await createAnnouncement(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除公告"${row.title}"？`,'提示',{type:'warning'}); await deleteAnnouncement(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
