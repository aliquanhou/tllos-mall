<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>会员折扣</span><el-button type="primary" @click="handleAdd">新增等级</el-button></div></template>
    <el-table :data="list" border>
      <el-table-column prop="level" label="等级" width="80" align="center" />
      <el-table-column prop="level_name" label="等级名称" width="150" />
      <el-table-column label="折扣率" width="120" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">{{row.discount_rate}}%</span></template></el-table-column>
      <el-table-column label="最低消费" width="120" align="center"><template #default="{row}">¥{{row.min_amount}}</template></el-table-column>
      <el-table-column prop="description" label="升级条件" min-width="200" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑等级':'新增等级'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="等级名称"><el-input v-model="form.level_name" /></el-form-item>
        <el-form-item label="等级"><el-input-number v-model="form.level" :min="1" :max="10" /></el-form-item>
        <el-form-item label="折扣率(%)"><el-input-number v-model="form.discount_rate" :min="0" :max="100" :precision="2" /><span style="margin-left:8px;color:#909399">如95=95折</span></el-form-item>
        <el-form-item label="最低消费"><el-input-number v-model="form.min_amount" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="升级条件"><el-input v-model="form.description" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getDiscountList, createDiscount, updateDiscount, deleteDiscount } from '@/api/marketing'
const list = ref([]); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, level_name:'', level:1, discount_rate:100, min_amount:0, description:'', status:1 })
const fetchList = async () => { const res = await getDiscountList(); list.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,level_name:'',level:1,discount_rate:100,min_amount:0,description:'',status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if (!form.value.level_name) { ElMessage.warning('请输入等级名称'); return }; if(isEdit.value){await updateDiscount(form.value.id,form.value);ElMessage.success('更新成功')}else{await createDiscount(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除等级"${row.level_name}"？`,'提示',{type:'warning'}); await deleteDiscount(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
