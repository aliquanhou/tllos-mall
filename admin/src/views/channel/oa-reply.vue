<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>公众号回复</span><el-button type="primary" @click="handleAdd">新增回复</el-button></div></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="key" label="关键词" width="150" />
      <el-table-column prop="value" label="回复内容" min-width="300" show-overflow-tooltip />
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑回复':'新增回复'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="关键词"><el-input v-model="form.key" :disabled="isEdit" /></el-form-item>
        <el-form-item label="回复内容"><el-input v-model="form.value" type="textarea" :rows="4" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getOaReplyList, addOaReply, updateOaReply, deleteOaReply } from '@/api/channel'
const list = ref([]); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, key:'', value:'' })
const fetchList = async () => { loading.value=true; try { const res = await getOaReplyList(); list.value = (res.data.list||[]).map(i=>({...i,key:i.key.replace('reply_','')})) } finally { loading.value=false } }
const handleAdd = () => { isEdit.value=false; form.value={id:null,key:'',value:''}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.key||!form.value.value){ElMessage.warning('请填写完整');return}; if(isEdit.value){await updateOaReply(form.value.id,{value:form.value.value});ElMessage.success('编辑成功')}else{await addOaReply(form.value);ElMessage.success('添加成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm('确定删除？','提示',{type:'warning'}); await deleteOaReply(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
