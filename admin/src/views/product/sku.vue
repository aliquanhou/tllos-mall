<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>商品SKU管理</span><el-button type="primary" @click="handleAdd">新增SKU</el-button></div></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="ID" width="80" align="center" />
      <el-table-column prop="goods_id" label="商品ID" width="100" align="center" />
      <el-table-column prop="sku_name" label="规格名称" min-width="150" />
      <el-table-column prop="sku_code" label="SKU编码" width="150" />
      <el-table-column label="价格" width="100" align="center"><template #default="{row}">¥{{row.price}}</template></el-table-column>
      <el-table-column prop="stock" label="库存" width="100" align="center" />
      <el-table-column prop="sales" label="销量" width="100" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑SKU':'新增SKU'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="商品ID"><el-input-number v-model="form.goods_id" :min="1" /></el-form-item>
        <el-form-item label="规格名称"><el-input v-model="form.sku_name" /></el-form-item>
        <el-form-item label="SKU编码"><el-input v-model="form.sku_code" /></el-form-item>
        <el-form-item label="价格"><el-input-number v-model="form.price" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="库存"><el-input-number v-model="form.stock" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getGoodsSkuList, createGoodsSku, updateGoodsSku, deleteGoodsSku } from '@/api/tools'
const list = ref([]); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, goods_id:1, sku_name:'', sku_code:'', price:0, stock:0, status:1 })
const fetchList = async () => { loading.value=true; try { const res = await getGoodsSkuList(); list.value = res.data.list||[] } finally { loading.value=false } }
const handleAdd = () => { isEdit.value=false; form.value={id:null,goods_id:1,sku_name:'',sku_code:'',price:0,stock:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(isEdit.value){await updateGoodsSku(form.value.id,form.value);ElMessage.success('更新成功')}else{await createGoodsSku(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm('确定删除？','提示',{type:'warning'}); await deleteGoodsSku(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
