<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>分销等级</span><el-button type="primary" @click="handleAdd">新增等级</el-button></div></template>
    <el-table :data="list" border>
      <el-table-column prop="level" label="等级" width="80" align="center" />
      <el-table-column prop="name" label="等级名称" width="150" />
      <el-table-column label="佣金比例" width="120" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">{{row.commission_rate}}%</span></template></el-table-column>
      <el-table-column label="升级条件" min-width="200"><template #default="{row}">累计{{row.min_orders}}单 或 ¥{{row.min_amount}}</template></el-table-column>
      <el-table-column prop="description" label="描述" min-width="150" show-overflow-tooltip />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="150" align="center">
        <template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button></template>
      </el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑等级':'新增等级'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="等级名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="等级"><el-input-number v-model="form.level" :min="1" :max="10" /></el-form-item>
        <el-form-item label="佣金比例(%)"><el-input-number v-model="form.commission_rate" :min="0" :max="100" :precision="2" /></el-form-item>
        <el-form-item label="最低订单数"><el-input-number v-model="form.min_orders" :min="0" /></el-form-item>
        <el-form-item label="最低金额"><el-input-number v-model="form.min_amount" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="描述"><el-input v-model="form.description" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getLevelList, createLevel, updateLevel } from '@/api/distribute'
const list = ref([]); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', level:1, commission_rate:10, min_orders:0, min_amount:0, description:'', status:1 })
const fetchList = async () => { const res = await getLevelList(); list.value = res.data.list||[] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',level:1,commission_rate:10,min_orders:0,min_amount:0,description:'',status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if (!form.value.name) { ElMessage.warning('请输入等级名称'); return }; if (isEdit.value) { await updateLevel(form.value.id,form.value); ElMessage.success('更新成功') } else { await createLevel(form.value); ElMessage.success('创建成功') }; dialogVisible.value=false; fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
