<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>支付场景</span><el-button type="primary" @click="handleAdd">新增场景</el-button></div></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="场景名称" width="150" />
      <el-table-column prop="code" label="场景编码" width="150" />
      <el-table-column label="微信支付" width="100" align="center"><template #default="{row}"><el-tag :type="row.wechat_enabled===1?'success':'info'" size="small">{{row.wechat_enabled===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="支付宝" width="100" align="center"><template #default="{row}"><el-tag :type="row.alipay_enabled===1?'success':'info'" size="small">{{row.alipay_enabled===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="余额支付" width="100" align="center"><template #default="{row}"><el-tag :type="row.balance_enabled===1?'success':'info'" size="small">{{row.balance_enabled===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑场景':'新增场景'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="场景名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="场景编码"><el-input v-model="form.code" /></el-form-item>
        <el-form-item label="微信支付"><el-switch v-model="form.wechat_enabled" :active-value="1" :inactive-value="0" /></el-form-item>
        <el-form-item label="支付宝"><el-switch v-model="form.alipay_enabled" :active-value="1" :inactive-value="0" /></el-form-item>
        <el-form-item label="余额支付"><el-switch v-model="form.balance_enabled" :active-value="1" :inactive-value="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getPaySceneList, createPayScene, updatePayScene, deletePayScene } from '@/api/payScene'
const list = ref([]); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', code:'', wechat_enabled:1, alipay_enabled:0, balance_enabled:1, status:1 })
const fetchList = async () => { const res = await getPaySceneList(); list.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',code:'',wechat_enabled:1,alipay_enabled:0,balance_enabled:1,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.name||!form.value.code){ElMessage.warning('请填写完整');return}; if(isEdit.value){await updatePayScene(form.value.id,form.value);ElMessage.success('更新成功')}else{await createPayScene(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除场景"${row.name}"？`,'提示',{type:'warning'}); await deletePayScene(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
