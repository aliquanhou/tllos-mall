<template>
  <div class="goods-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item><el-input v-model="searchForm.keyword" placeholder="商品名称" clearable style="width:200px" /></el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="handleAdd">新增商品</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-card shadow="never" style="margin-top:16px">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品" min-width="200"><template #default="{row}"><div style="display:flex;gap:10px;align-items:center"><el-image :src="row.main_image" style="width:50px;height:50px" /><span>{{row.name}}</span></div></template></el-table-column>
        <el-table-column label="价格" width="100"><template #default="{row}">¥{{row.price}}</template></el-table-column>
        <el-table-column prop="stock" label="库存" width="80" />
        <el-table-column prop="sales" label="销量" width="80" />
        <el-table-column label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'上架':'下架'}}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="150"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
      </el-table>
      <div style="margin-top:16px;display:flex;justify-content:flex-end"><el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" :page-sizes="[10,20,50]" layout="total, prev, pager, next" @current-change="fetchList" /></div>
    </el-card>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑商品':'新增商品'" width="600px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="商品名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="商品图片"><el-input v-model="form.main_image" placeholder="图片URL" /></el-form-item>
        <el-form-item label="价格"><el-input-number v-model="form.price" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="库存"><el-input-number v-model="form.stock" :min="0" /></el-form-item>
        <el-form-item label="商品描述"><el-input v-model="form.description" type="textarea" :rows="3" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/utils/request'
const list = ref([]); const total = ref(0); const loading = ref(false)
const page = ref(1); const limit = ref(20)
const searchForm = reactive({ keyword:'' })
const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', main_image:'', price:0, stock:0, description:'', status:1 })
const fetchList = async () => { loading.value=true; try { const res = await request({ url:'/merchant/goods', params:{page:page.value,limit:limit.value,...searchForm} }); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',main_image:'',price:0,stock:0,description:'',status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(isEdit.value){await request({url:`/merchant/goods/${form.value.id}`,method:'put',data:form.value});ElMessage.success('更新成功')}else{await request({url:'/merchant/goods',method:'post',data:form.value});ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm('确定删除？','提示',{type:'warning'}); await request({url:`/merchant/goods/${row.id}`,method:'delete'}); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
