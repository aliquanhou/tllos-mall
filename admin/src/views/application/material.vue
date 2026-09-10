<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>素材管理</span><el-button type="primary">上传素材</el-button></div></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="素材名称" clearable style="width:180px" /></el-form-item>
      <el-form-item label="类型"><el-select v-model="query.type" placeholder="全部" clearable style="width:120px"><el-option value="image" label="图片" /><el-option value="video" label="视频" /><el-option value="file" label="文件" /></el-select></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="name" label="素材名称" min-width="180" />
      <el-table-column prop="type" label="类型" width="100" align="center" />
      <el-table-column label="大小" width="120" align="center"><template #default="{row}">{{(row.size/1024).toFixed(1)}}KB</template></el-table-column>
      <el-table-column prop="category" label="分类" width="120" align="center" />
      <el-table-column prop="created_at" label="上传时间" width="170" align="center" />
      <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button size="small" type="primary" link>复制链接</el-button><el-button size="small" type="danger" link>删除</el-button></template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getMaterialList } from '@/api/application'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', type:null })
const fetchList = async () => { loading.value=true; try { const res = await getMaterialList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',type:null}); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}.search-form{margin-bottom:16px}</style>
