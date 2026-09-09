<template>
  <el-card shadow="never">
    <template #header><span>系统升级</span></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="version" label="版本号" width="120" />
      <el-table-column prop="release_date" label="发布日期" width="150" />
      <el-table-column prop="description" label="更新说明" min-width="300" />
      <el-table-column label="状态" width="120" align="center"><template #default="{row}"><el-tag :type="row.status==='current'?'success':'warning'" size="small">{{row.status==='current'?'当前版本':'可升级'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button v-if="row.status!=='current'" size="small" type="primary" @click="handleUpgrade(row)">立即升级</el-button></template></el-table-column>
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getUpgradeList } from '@/api/tools'
const list = ref([]); const loading = ref(false)
const fetchList = async () => { loading.value=true; try { const res = await getUpgradeList(); list.value = res.data.list||[] } finally { loading.value=false } }
const handleUpgrade = async row => { await ElMessageBox.confirm(`确定升级到版本 ${row.version}？`,'提示',{type:'warning'}); ElMessage.success(`升级到 ${row.version} 成功(模拟)`); fetchList() }
onMounted(fetchList)
</script>
