<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>配送方式</span><el-button type="primary" @click="handleAdd">新增配送方式</el-button></div></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="配送方式" min-width="150" />
      <el-table-column label="类型" width="100" align="center"><template #default="{row}">{{row.type===1?'快递':row.type===2?'自提':'同城'}}</template></el-table-column>
      <el-table-column label="运费" width="100" align="center"><template #default="{row}">¥{{row.fee}}</template></el-table-column>
      <el-table-column label="满额包邮" width="120" align="center"><template #default="{row}">¥{{row.free_amount}}</template></el-table-column>
      <el-table-column prop="sort" label="排序" width="80" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑配送方式':'新增配送方式'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="类型"><el-select v-model="form.type" style="width:100%"><el-option :value="1" label="快递配送" /><el-option :value="2" label="到店自提" /><el-option :value="3" label="同城配送" /></el-select></el-form-item>
        <el-form-item label="运费(元)"><el-input-number v-model="form.fee" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="满额包邮(元)"><el-input-number v-model="form.free_amount" :min="0" :precision="2" /></el-form-item>
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
import { getDeliveryTypeList, createDeliveryType, updateDeliveryType, deleteDeliveryType } from '@/api/systemExtra'
const list = ref([]); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', type:1, fee:0, free_amount:0, sort:0, status:1 })
const fetchList = async () => { const res = await getDeliveryTypeList(); list.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',type:1,fee:0,free_amount:0,sort:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.name){ElMessage.warning('请输入名称');return}; if(isEdit.value){await updateDeliveryType(form.value.id,form.value);ElMessage.success('更新成功')}else{await createDeliveryType(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除"${row.name}"？`,'提示',{type:'warning'}); await deleteDeliveryType(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
