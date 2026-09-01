<template>
  <div class="template-page">
    <el-card>
      <template #header>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:16px;font-weight:bold">页面模板</span>
          <el-button type="primary" @click="showDialog=true">新建模板</el-button>
        </div>
      </template>
      <el-table :data="templates" border>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="模板名称" width="150" />
        <el-table-column prop="page_type" label="适用页面" width="100">
          <template #default="{row}">{{ pageTypeText[row.page_type] }}</template>
        </el-table-column>
        <el-table-column prop="description" label="描述" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{row}">
            <el-tag :type="row.status===1?'success':'info'">{{ row.status===1?'使用中':'未使用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200">
          <template #default="{row}">
            <el-button size="small" @click="edit(row)">编辑</el-button>
            <el-button size="small" type="primary" @click="apply(row)">应用</el-button>
            <el-button size="small" type="danger" @click="remove(row)" :disabled="row.is_system">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="showDialog" :title="form.id?'编辑模板':'新建模板'" width="600px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="模板名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="适用页面">
          <el-select v-model="form.page_type">
            <el-option label="首页" value="home" />
            <el-option label="分类页" value="category" />
            <el-option label="会员中心" value="member" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述"><el-input v-model="form.description" type="textarea" :rows="2" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog=false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/utils/request'

const templates = ref([])
const showDialog = ref(false)
const form = reactive({ id: null, name: '', page_type: 'home', description: '' })
const pageTypeText = { home: '首页', category: '分类页', member: '会员中心' }

const loadList = async () => {
  const res = await request({ url: '/decorate/templates' })
  templates.value = res.data?.list || res.data || []
}

const edit = (row) => {
  Object.assign(form, row)
  showDialog.value = true
}

const save = async () => {
  if (form.id) {
    await request({ url: `/decorate/templates/${form.id}`, method: 'put', data: form })
  } else {
    await request({ url: '/decorate/templates', method: 'post', data: form })
  }
  ElMessage.success('保存成功')
  showDialog.value = false
  loadList()
}

const apply = async (row) => {
  await ElMessageBox.confirm(`确定应用"${row.name}"模板到首页？`, '提示', { type: 'warning' })
  await request({ url: '/decorate/pages/1/apply-template', method: 'post', data: { template_id: row.id } })
  ElMessage.success('应用成功')
}

const remove = async (row) => {
  await ElMessageBox.confirm('确定删除该模板？', '提示', { type: 'warning' })
  await request({ url: `/decorate/templates/${row.id}`, method: 'delete' })
  ElMessage.success('删除成功')
  loadList()
}

onMounted(() => loadList())
</script>

<style scoped>
.template-page { padding: 20px; }
</style>
