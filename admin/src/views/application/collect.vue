<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>商品采集</span><el-button type="primary">开始采集</el-button></div></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="采集来源"><el-select v-model="source" placeholder="选择平台" style="width:160px"><el-option value="1688" label="1688" /><el-option value="taobao" label="淘宝" /><el-option value="jd" label="京东" /><el-option value="pinduoduo" label="拼多多" /></el-select></el-form-item>
      <el-form-item label="商品链接"><el-input v-model="collectUrl" placeholder="输入商品链接" style="width:300px" /></el-form-item>
      <el-form-item><el-button type="primary" @click="handleCollect">采集</el-button></el-form-item>
    </el-form>
    <el-divider>已采集商品</el-divider>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="name" label="商品名称" min-width="200" />
      <el-table-column label="价格" width="120" align="center"><template #default="{row}"><span style="color:#f56c6c;font-weight:bold">¥{{row.price}}</span></template></el-table-column>
      <el-table-column prop="source" label="来源" width="100" align="center" />
      <el-table-column prop="stock" label="库存" width="90" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'已上架':'已下架'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button size="small" type="primary" link>导入商城</el-button><el-button size="small" type="danger" link>删除</el-button></template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getCollectList } from '@/api/application'
const list = ref([]); const total = ref(0); const loading = ref(false); const source = ref('1688'); const collectUrl = ref('')
const query = reactive({ page:1, limit:20 })
const fetchList = async () => { loading.value=true; try { const res = await getCollectList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const handleCollect = () => { if (!collectUrl.value) { ElMessage.warning('请输入商品链接'); return }; ElMessage.success('采集任务已提交') }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}.search-form{margin-bottom:16px}</style>
