<template>
  <el-card shadow="never">
    <template #header><span>管理员部门</span></template>
    <el-table :data="list" border>
      <el-table-column prop="id" label="ID" width="80" align="center" />
      <el-table-column prop="name" label="部门名称" width="150" />
      <el-table-column prop="leader" label="负责人" width="120" />
      <el-table-column prop="phone" label="联系电话" width="150" />
      <el-table-column prop="sort" label="排序" width="100" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { getAdminDeptList } from '@/api/tools'
const list = ref([])
const fetchList = async () => { const res = await getAdminDeptList(); list.value = res.data.list||[] }
onMounted(fetchList)
</script>
