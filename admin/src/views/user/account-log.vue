<template>
  <el-card shadow="never">
    <template #header><span>用户账户日志</span></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="user_name" label="用户" width="100" />
      <el-table-column label="类型" width="100" align="center"><template #default="{row}"><el-tag :type="row.type===1?'warning':'success'" size="small">{{row.type===1?'余额':'积分'}}</el-tag></template></el-table-column>
      <el-table-column prop="action" label="变动" width="80" align="center" />
      <el-table-column label="金额" width="110" align="center"><template #default="{row}"><span :style="{color:row.action==='inc'?'#f56c6c':'#67c23a'}">{{row.action==='inc'?'+':'-'}}{{row.amount}}</span></template></el-table-column>
      <el-table-column label="变动前" width="100" align="center"><template #default="{row}">{{row.before_amount}}</template></el-table-column>
      <el-table-column label="变动后" width="100" align="center"><template #default="{row}">{{row.after_amount}}</template></el-table-column>
      <el-table-column prop="order_no" label="关联订单" width="170" />
      <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
      <el-table-column prop="created_at" label="时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getAccountLogList } from '@/api/userCenter'
const list = ref([]); const total = ref(0); const loading = ref(false)
const query = reactive({ page:1, limit:20 })
const fetchList = async () => { loading.value=true; try { const res = await getAccountLogList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
onMounted(fetchList)
</script>
