<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>快递模板</span><el-button type="primary" @click="handleAdd">新增模板</el-button></div></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="模板名称" min-width="150" />
      <el-table-column label="计费方式" width="120" align="center"><template #default="{row}">{{row.type===1?'包邮':row.type===2?'按件数':'按重量'}}</template></el-table-column>
      <el-table-column label="首件/首重" width="100" align="center"><template #default="{row}">{{row.first_num}}</template></el-table-column>
      <el-table-column label="首费" width="100" align="center"><template #default="{row}">¥{{row.first_fee}}</template></el-table-column>
      <el-table-column label="续件/续重" width="100" align="center"><template #default="{row}">{{row.continue_num}}</template></el-table-column>
      <el-table-column label="续费" width="100" align="center"><template #default="{row}">¥{{row.continue_fee}}</template></el-table-column>
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑模板':'新增模板'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="模板名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="计费方式"><el-select v-model="form.type" style="width:100%"><el-option :value="1" label="包邮" /><el-option :value="2" label="按件数" /><el-option :value="3" label="按重量" /></el-select></el-form-item>
        <el-form-item label="首件/首重"><el-input-number v-model="form.first_num" :min="1" /></el-form-item>
        <el-form-item label="首费(元)"><el-input-number v-model="form.first_fee" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="续件/续重"><el-input-number v-model="form.continue_num" :min="1" /></el-form-item>
        <el-form-item label="续费(元)"><el-input-number v-model="form.continue_fee" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getExpressTemplateList, createExpressTemplate, updateExpressTemplate, deleteExpressTemplate } from '@/api/systemConfig'
const list = ref([]); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', type:1, first_num:1, first_fee:0, continue_num:1, continue_fee:0, status:1 })
const fetchList = async () => { const res = await getExpressTemplateList(); list.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',type:1,first_num:1,first_fee:0,continue_num:1,continue_fee:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.name){ElMessage.warning('请输入模板名称');return}; if(isEdit.value){await updateExpressTemplate(form.value.id,form.value);ElMessage.success('更新成功')}else{await createExpressTemplate(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除模板"${row.name}"？`,'提示',{type:'warning'}); await deleteExpressTemplate(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
