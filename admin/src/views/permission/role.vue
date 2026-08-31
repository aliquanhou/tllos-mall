<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>角色管理</span><el-button type="primary" @click="handleAdd">新增角色</el-button></div></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="角色名称" width="150" />
      <el-table-column prop="description" label="角色描述" min-width="200" />
      <el-table-column label="权限数量" width="100" align="center"><template #default="{row}">{{row.permissions?JSON.parse(row.permissions).length:0}}</template></el-table-column>
      <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="200" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="primary" @click="handlePermission(row)">分配权限</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑角色':'新增角色'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="角色名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="角色描述"><el-input v-model="form.description" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
    <el-dialog v-model="permVisible" title="分配权限" width="600px">
      <el-tree :data="menuTree" show-checkbox node-key="id" :default-checked-keys="checkedKeys" ref="permTree" />
      <template #footer><el-button @click="permVisible=false">取消</el-button><el-button type="primary" @click="handleSavePerm">保存</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getRoleList, createRole, updateRole, deleteRole, getMenuList } from '@/api/permission'
const list = ref([]); const dialogVisible = ref(false); const permVisible = ref(false); const isEdit = ref(false); const menuTree = ref([]); const checkedKeys = ref([]); const permTree = ref(null); const currentRole = ref(null)
const form = ref({ id:null, name:'', description:'', permissions:'[]', status:1 })
const fetchList = async () => { const res = await getRoleList(); list.value = res.data.list||[] }
const fetchMenu = async () => { const res = await getMenuList(); menuTree.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',description:'',permissions:'[]',status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handlePermission = row => { currentRole.value=row; try { checkedKeys.value = JSON.parse(row.permissions||'[]') } catch(e) { checkedKeys.value=[] }; permVisible.value=true }
const handleSubmit = async () => { if (!form.value.name) { ElMessage.warning('请输入角色名称'); return }; if(isEdit.value){await updateRole(form.value.id,form.value);ElMessage.success('更新成功')}else{await createRole(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleSavePerm = async () => { const keys = permTree.value.getCheckedKeys(); await updateRole(currentRole.value.id,{permissions:JSON.stringify(keys)}); ElMessage.success('权限分配成功'); permVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除角色"${row.name}"？`,'提示',{type:'warning'}); await deleteRole(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(() => { fetchList(); fetchMenu() })
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
