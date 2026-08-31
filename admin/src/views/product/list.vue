<template>
  <div class="product-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词"><el-input v-model="searchForm.keyword" placeholder="商品名称" clearable style="width:200px" @keyup.enter="fetchList" /></el-form-item>
        <el-form-item label="分类"><el-select v-model="searchForm.category_id" placeholder="全部分类" clearable style="width:160px"><el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" /></el-select></el-form-item>
        <el-form-item label="状态"><el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width:120px"><el-option label="已上架" :value="1" /><el-option label="已下架" :value="0" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetSearch">重置</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-card shadow="never" style="margin-top:16px">
      <template #header><div class="card-header"><span>商品列表（共 {{ total }} 件）</span><el-button type="primary" @click="handleAdd">新增商品</el-button></div></template>
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品信息" min-width="280">
          <template #default="{row}"><div class="product-info"><el-image :src="row.main_image" fit="cover" style="width:60px;height:60px;border-radius:4px" :preview-src-list="[row.main_image]"><template #error><div class="image-error">无图</div></template></el-image><div class="product-detail"><div class="product-name">{{row.name}}</div><div class="product-meta"><el-tag size="small" type="info" v-if="row.category_name">{{row.category_name}}</el-tag><span v-if="row.is_hot" style="color:#f56c6c;margin-left:8px">热</span><span v-if="row.is_new" style="color:#67c23a;margin-left:4px">新</span></div></div></div></template>
        </el-table-column>
        <el-table-column label="价格" width="120"><template #default="{row}"><div class="price">¥{{row.price}}</div><div class="market-price" v-if="row.market_price">¥{{row.market_price}}</div></template></el-table-column>
        <el-table-column prop="stock" label="库存" width="90" />
        <el-table-column prop="sales" label="销量" width="90" />
        <el-table-column label="状态" width="100"><template #default="{row}"><el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleToggleStatus(row)" /></template></el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column label="操作" width="150" fixed="right"><template #default="{row}"><el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button><el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button></template></el-table-column>
      </el-table>
      <div class="pagination"><el-pagination v-model:current-page="page" v-model:page-size="limit" :page-sizes="[10,20,50]" :total="total" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" /></div>
    </el-card>
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑商品':'新增商品'" width="700px" @close="resetForm">
      <el-form :model="form" label-width="100px">
        <el-form-item label="商品名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="商品分类"><el-select v-model="form.category_id" style="width:100%"><el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" /></el-select></el-form-item>
        <el-form-item label="商品图片"><el-input v-model="form.main_image" placeholder="图片URL" /></el-form-item>
        <el-form-item label="价格"><el-input-number v-model="form.price" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="市场价"><el-input-number v-model="form.market_price" :min="0" :precision="2" /></el-form-item>
        <el-form-item label="库存"><el-input-number v-model="form.stock" :min="0" /></el-form-item>
        <el-form-item label="商品描述"><el-input v-model="form.description" type="textarea" :rows="3" /></el-form-item>
        <el-form-item label="是否热销"><el-switch v-model="form.is_hot" :active-value="1" :inactive-value="0" /></el-form-item>
        <el-form-item label="是否新品"><el-switch v-model="form.is_new" :active-value="1" :inactive-value="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getProductList, createProduct, updateProduct, deleteProduct, toggleProductStatus } from '@/api/product'
import { getCategoryTree } from '@/api/category'
const list = ref([]); const total = ref(0); const loading = ref(false); const categories = ref([])
const page = ref(1); const limit = ref(20)
const searchForm = reactive({ keyword:'', category_id:null, status:null })
const dialogVisible = ref(false); const isEdit = ref(false); const submitting = ref(false)
const form = ref({ id:null, name:'', category_id:null, main_image:'', price:0, market_price:0, stock:0, description:'', is_hot:0, is_new:0, status:1 })
const fetchList = async () => { loading.value=true; try { const res = await getProductList({page:page.value,limit:limit.value,...searchForm}); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const fetchCategories = async () => { const res = await getCategoryTree(); categories.value=res.data||[] }
const resetSearch = () => { searchForm.keyword=''; searchForm.category_id=null; searchForm.status=null; page.value=1; fetchList() }
const resetForm = () => { form.value={id:null,name:'',category_id:null,main_image:'',price:0,market_price:0,stock:0,description:'',is_hot:0,is_new:0,status:1} }
const handleAdd = () => { isEdit.value=false; resetForm(); dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(!form.value.name){ElMessage.warning('请输入商品名称');return}; submitting.value=true; try { if(isEdit.value){await updateProduct(form.value.id,form.value);ElMessage.success('更新成功')}else{await createProduct(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() } finally { submitting.value=false } }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除商品"${row.name}"？`,'提示',{type:'warning'}); await deleteProduct(row.id); ElMessage.success('删除成功'); fetchList() }
const handleToggleStatus = async row => { await toggleProductStatus(row.id,{status:row.status}); ElMessage.success('状态更新成功') }
onMounted(() => { fetchCategories(); fetchList() })
</script>
<style scoped>.search-form{margin-bottom:0}.card-header{display:flex;justify-content:space-between;align-items:center}.product-info{display:flex;gap:12px;align-items:center}.product-detail{flex:1}.product-name{font-weight:500;margin-bottom:4px}.product-meta{display:flex;align-items:center}.price{color:#f56c6c;font-weight:600}.market-price{color:#909399;text-decoration:line-through;font-size:12px}.pagination{margin-top:16px;display:flex;justify-content:flex-end}.image-error{width:60px;height:60px;display:flex;align-items:center;justify-content:center;background:#f5f7fa;color:#909399;font-size:12px}</style>
