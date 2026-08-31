<template>
  <el-card shadow="never">
    <template #header><span>操作日志</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="管理员/模块/内容" clearable style="width:200px" /></el-form-item>
      <el-form-item label="模块">
        <el-select v-model="query.module" placeholder="全部" clearable style="width:140px">
          <el-option v-for="m in modules" :key="m" :value="m" :label="m" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="ID" width="80" align="center" />
      <el-table-column prop="admin_name" label="管理员" width="120" />
      <el-table-column prop="module" label="操作模块" width="120" />
      <el-table-column prop="action" label="操作动作" width="120" />
      <el-table-column prop="content" label="操作内容" min-width="250" show-overflow-tooltip />
      <el-table-column prop="ip" label="IP地址" width="140" />
      <el-table-column prop="created_at" label="操作时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getLogList } from '@/api/system'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', module:null })
const modules = ['商品管理','订单管理','用户管理','商家管理','营销管理','财务管理','系统设置','分销管理']
const fetchList = async () => { loading.value = true; try { const res = await getLogList(query); list.value = res.data.list||[]; total.value = res.data.total||0 } finally { loading.value = false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',module:null}); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
