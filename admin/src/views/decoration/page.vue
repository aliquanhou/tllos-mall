<template>
  <el-card shadow="never">
    <template #header><span>页面装修</span></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="页面名称" width="150" />
      <el-table-column prop="page_type" label="页面类型" width="120" align="center" />
      <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'已发布':'草稿'}}</el-tag></template></el-table-column>
      <el-table-column prop="updated_at" label="更新时间" width="170" align="center" />
      <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button size="small" type="primary" @click="handleEdit(row)">编辑</el-button><el-button size="small" link>预览</el-button></template></el-table-column>
    </el-table>
    <el-dialog v-model="dialogVisible" title="编辑页面" width="800px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="页面名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="页面内容"><el-input v-model="form.content" type="textarea" :rows="10" placeholder="JSON格式的页面配置" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">保存</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getPageList, savePage } from '@/api/decorate'
const list = ref([]); const dialogVisible = ref(false)
const form = ref({ id:null, name:'', content:'', status:1 })
const fetchList = async () => { const res = await getPageList(); list.value = res.data.list||[] }
const handleEdit = row => { form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { await savePage(form.value.id, form.value); ElMessage.success('保存成功'); dialogVisible.value=false; fetchList() }
onMounted(fetchList)
</script>
