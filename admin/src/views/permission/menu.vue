<template>
  <el-card shadow="never">
    <template #header><span>菜单管理</span></template>
    <el-table :data="menuList" border row-key="id" :tree-props="{children:'children'}" default-expand-all>
      <el-table-column prop="name" label="菜单名称" min-width="180" />
      <el-table-column prop="path" label="路由路径" min-width="200" />
      <el-table-column prop="icon" label="图标" width="100" align="center" />
      <el-table-column label="类型" width="100" align="center"><template #default="{row}"><el-tag :type="row.children&&row.children.length>0?'warning':'success'" size="small">{{row.children&&row.children.length>0?'目录':'菜单'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button size="small" type="primary" link>编辑</el-button><el-button size="small" type="danger" link>删除</el-button></template></el-table-column>
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { getMenuList } from '@/api/permission'
const menuList = ref([])
const fetchList = async () => { const res = await getMenuList(); menuList.value = res.data.list||[] }
onMounted(fetchList)
</script>
