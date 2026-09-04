<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header"><span>物流公司</span><el-button type="primary" @click="handleAdd">新增物流公司</el-button></div>
    </template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="ID" width="80" align="center" />
      <el-table-column prop="name" label="物流公司名称" min-width="150" />
      <el-table-column prop="code" label="物流编码" width="150" />
      <el-table-column prop="sort" label="排序" width="80" align="center" />
      <el-table-column label="状态" width="100" align="center">
        <template #default="{ row }"><el-tag :type="row.status===1?'success':'info'" size="small">{{ row.status===1?'启用':'禁用' }}</el-tag></template>
      </el-table-column>
      <el-table-column label="操作" width="180" align="center">
        <template #default="{ row }">
          <el-button size="small" @click="handleEdit(row)">编辑</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑物流公司':'新增物流公司'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="公司名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="物流编码"><el-input v-model="form.code" placeholder="如:SF=顺丰" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getExpressList, createExpress, updateExpress, deleteExpress } from '@/api/system'
const list = ref([]); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', code:'', sort:0, status:1 })
const fetchList = async () => { loading.value = true; try { const res = await getExpressList(); list.value = res.data.list||[] } finally { loading.value = false } }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',code:'',sort:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if (!form.value.name) { ElMessage.warning('请输入公司名称'); return }; if (isEdit.value) { await updateExpress(form.value.id, form.value); ElMessage.success('更新成功') } else { await createExpress(form.value); ElMessage.success('创建成功') }; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除物流公司"${row.name}"？`,'提示',{type:'warning'}); await deleteExpress(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
