<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>公众号菜单</span><div><el-button @click="handleAdd">新增菜单</el-button><el-button type="primary" @click="handleSave">保存</el-button><el-button type="success" @click="handlePublish">保存并发布</el-button></div></div></template>
    <el-table :data="menus" border>
      <el-table-column prop="name" label="菜单名称" min-width="150">
        <template #default="{row}"><el-input v-model="row.name" size="small" /></template>
      </el-table-column>
      <el-table-column prop="type" label="类型" width="120">
        <template #default="{row}"><el-select v-model="row.type" size="small"><el-option value="click" label="点击事件" /><el-option value="view" label="跳转链接" /><el-option value="miniprogram" label="小程序" /></el-select></template>
      </el-table-column>
      <el-table-column prop="key" label="事件KEY" min-width="150"><template #default="{row}"><el-input v-model="row.key" size="small" /></template></el-table-column>
      <el-table-column prop="url" label="跳转URL" min-width="200"><template #default="{row}"><el-input v-model="row.url" size="small" /></template></el-table-column>
      <el-table-column label="操作" width="100" align="center"><template #default="{$index}"><el-button size="small" type="danger" @click="menus.splice($index,1)">删除</el-button></template></el-table-column>
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getOaMenu, saveOaMenu } from '@/api/channel'
const menus = ref([{name:'菜单1',type:'click',key:'key1',url:''}])
const fetchData = async () => { const res = await getOaMenu(); if(res.data&&res.data.menus) menus.value = res.data.menus.map(m=>JSON.parse(m.value)) }
const handleAdd = () => menus.value.push({name:'新菜单',type:'click',key:'',url:''})
const handleSave = async () => { await saveOaMenu({menus:menus.value}); ElMessage.success('保存成功') }
const handlePublish = async () => { await saveOaMenu({menus:menus.value}); ElMessage.success('保存并发布成功') }
onMounted(fetchData)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}</style>
