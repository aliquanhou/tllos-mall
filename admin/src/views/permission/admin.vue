<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>管理员管理</span><el-button type="primary" @click="handleAdd">新增管理员</el-button></div></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="ID" width="80" align="center" />
      <el-table-column prop="username" label="用户名" width="120" />
      <el-table-column prop="nickname" label="昵称" width="120" />
      <el-table-column prop="role_name" label="角色" width="120" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" align="center" />
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button v-if="row.id!==1" size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑管理员':'新增管理员'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="用户名"><el-input v-model="form.username" :disabled="isEdit" /></el-form-item>
        <el-form-item label="密码"><el-input v-model="form.password" type="password" :placeholder="isEdit?'不修改请留空':'请输入密码'" /></el-form-item>
        <el-form-item label="昵称"><el-input v-model="form.nickname" /></el-form-item>
        <el-form-item label="角色"><el-select v-model="form.role_id" style="width:100%"><el-option v-for="r in roles" :key="r.id" :value="r.id" :label="r.name" /></el-select></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAdminList, createAdmin, updateAdmin, deleteAdmin } from '@/api/adminManage'
import { getRoleList } from '@/api/permission'
const list = ref([]); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false); const roles = ref([])
const form = ref({ id:null, username:'', password:'', nickname:'', role_id:null, status:1 })
const fetchList = async () => { loading.value=true; try { const res = await getAdminList(); list.value=res.data.list||[] } finally { loading.value=false } }
const fetchRoles = async () => { const res = await getRoleList(); roles.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,username:'',password:'',nickname:'',role_id:null,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row,password:''}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.username){ElMessage.warning('请输入用户名');return}; if(!isEdit.value&&!form.value.password){ElMessage.warning('请输入密码');return}; if(isEdit.value){await updateAdmin(form.value.id,form.value);ElMessage.success('更新成功')}else{await createAdmin(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除管理员"${row.username}"？`,'提示',{type:'warning'}); await deleteAdmin(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(() => { fetchRoles(); fetchList() })
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
