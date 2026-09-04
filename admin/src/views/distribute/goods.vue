<template>
  <el-card shadow="never">
    <template #header><span>分销商品</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="商品名称" clearable style="width:180px" /></el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" placeholder="全部" clearable style="width:120px">
          <el-option :value="1" label="已开启" /><el-option :value="0" label="已关闭" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="product_name" label="商品名称" min-width="200" />
      <el-table-column label="佣金类型" width="100" align="center"><template #default="{row}">{{row.commission_type===1?'比例':'固定'}}</template></el-table-column>
      <el-table-column label="佣金比例/金额" width="130" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">{{row.commission_type===1?row.commission_rate+'%':'¥'+row.commission_amount}}</span></template></el-table-column>
      <el-table-column prop="sort" label="排序" width="80" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'已开启':'已关闭'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="120" align="center">
        <template #default="{row}"><el-button size="small" :type="row.status===1?'warning':'success'" @click="handleToggle(row)">{{row.status===1?'关闭':'开启'}}</el-button></template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getGoodsList, toggleGoods } from '@/api/distribute'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const fetchList = async () => { loading.value=true; try { const res = await getGoodsList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',status:null}); fetchList() }
const handleToggle = async row => { await toggleGoods(row.id); ElMessage.success(row.status===1?'已关闭':'已开启'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}</style>
