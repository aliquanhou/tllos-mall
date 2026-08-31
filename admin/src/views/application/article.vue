<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>文章资讯</span><el-button type="primary" @click="handleAdd">新增文章</el-button></div></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="文章标题" clearable style="width:180px" /></el-form-item>
      <el-form-item label="分类"><el-select v-model="query.category_id" placeholder="全部" clearable style="width:140px"><el-option v-for="c in categories" :key="c.id" :value="c.id" :label="c.name" /></el-select></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="title" label="文章标题" min-width="200" />
      <el-table-column prop="category_name" label="分类" width="100" align="center" />
      <el-table-column prop="author" label="作者" width="100" align="center" />
      <el-table-column prop="view_count" label="浏览量" width="90" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'已发布':'草稿'}}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" align="center" />
      <el-table-column label="操作" width="180" align="center" fixed="right"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑文章':'新增文章'" width="700px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="文章标题"><el-input v-model="form.title" /></el-form-item>
        <el-form-item label="分类"><el-select v-model="form.category_id" style="width:100%"><el-option v-for="c in categories" :key="c.id" :value="c.id" :label="c.name" /></el-select></el-form-item>
        <el-form-item label="摘要"><el-input v-model="form.summary" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="文章内容"><el-input v-model="form.content" type="textarea" :rows="6" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getArticleList, createArticle, updateArticle, deleteArticle, getArticleCategories } from '@/api/application'
const list = ref([]); const total = ref(0); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false); const categories = ref([])
const form = ref({ id:null, title:'', category_id:1, summary:'', content:'', sort:0, status:1 })
const query = reactive({ page:1, limit:20, keyword:'', category_id:null })
const fetchList = async () => { loading.value=true; try { const res = await getArticleList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const fetchCategories = async () => { const res = await getArticleCategories(); categories.value = res.data.list||[] }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',category_id:null}); fetchList() }
const handleAdd = () => { isEdit.value=false; form.value={id:null,title:'',category_id:categories.value[0]?.id||1,summary:'',content:'',sort:0,status:1}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if (!form.value.title) { ElMessage.warning('请输入文章标题'); return }; if(isEdit.value){await updateArticle(form.value.id,form.value);ElMessage.success('更新成功')}else{await createArticle(form.value);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除文章"${row.title}"？`,'提示',{type:'warning'}); await deleteArticle(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(() => { fetchCategories(); fetchList() })
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}.search-form{margin-bottom:16px}</style>
