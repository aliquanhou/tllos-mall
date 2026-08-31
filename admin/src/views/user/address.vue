<template>
  <el-card shadow="never">
    <template #header><span>用户地址</span></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="user_id" label="用户ID" width="80" align="center" />
      <el-table-column prop="consignee" label="收货人" width="100" />
      <el-table-column prop="mobile" label="手机号" width="130" />
      <el-table-column label="省市区" width="200"><template #default="{row}">{{row.province}} {{row.city}} {{row.district}}</template></el-table-column>
      <el-table-column prop="address" label="详细地址" min-width="200" show-overflow-tooltip />
      <el-table-column label="默认" width="80" align="center"><template #default="{row}"><el-tag v-if="row.is_default" type="success" size="small">默认</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getAddressList } from '@/api/userCenter'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20 })
const fetchList = async () => { loading.value=true; try { const res = await getAddressList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
onMounted(fetchList)
</script>
