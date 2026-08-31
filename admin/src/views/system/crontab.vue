<template>
  <el-card shadow="never">
    <template #header><span>定时任务</span></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="任务名称" min-width="180" />
      <el-table-column prop="command" label="执行命令" min-width="250" show-overflow-tooltip />
      <el-table-column prop="cron_expression" label="Cron表达式" width="150" align="center" />
      <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'运行中':'已停止'}}</el-tag></template></el-table-column>
      <el-table-column prop="last_run_time" label="上次执行" width="170" align="center" />
      <el-table-column label="操作" width="120" align="center"><template #default="{row}"><el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleToggle(row)" /></template></el-table-column>
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getCrontabList, toggleCrontab } from '@/api/systemConfig'
const list = ref([])
const fetchList = async () => { const res = await getCrontabList(); list.value = res.data.list||[] }
const handleToggle = async row => { await toggleCrontab(row.id); ElMessage.success(row.status===1?'已启用':'已停止') }
onMounted(fetchList)
</script>
