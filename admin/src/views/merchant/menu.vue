<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>商家端菜单</span><el-button type="primary" @click="handleAdd">新增菜单</el-button></div></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="菜单名称" min-width="150" />
      <el-table-column prop="path" label="路由路径" min-width="180" />
      <el-table-column prop="icon" label="图标" width="100" align="center" />
      <el-table-column prop="sort" label="排序" width="80" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑菜单':'新增菜单'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="菜单名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="路由路径"><el-input v-model="form.path" /></el-form-item>
        <el-form-item label="图标"><el-input v-model="form.icon" /></el-form-item>
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
import { getShopMenuList, createShopMenu, updateShopMenu, deleteShopMenu } from '@/api/systemExtra'
const list = ref([]); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', path:'', icon:'', sort:0, status:1 })
const fetchList = async () => { const res = await getShopMenuList(); list.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',path:'',icon:'',sort:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.name){ElMessage.warning('请输入菜单名称');return}; if(isEdit.value){await updateShopMenu(form.value.id,form.value);ElMessage.success('更新成功')}else{await createShopMenu(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除菜单"${row.name}"？`,'提示',{type:'warning'}); await deleteShopMenu(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
