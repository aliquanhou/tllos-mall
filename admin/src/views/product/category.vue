<template>
  <div class="category-page">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>商品分类管理</span>
          <el-button type="primary" @click="handleAdd(0)">新增一级分类</el-button>
        </div>
      </template>
      <el-table :data="treeData" row-key="id" :tree-props="{ children: 'children' }" default-expand-all border>
        <el-table-column prop="name" label="分类名称" min-width="200">
          <template #default="{ row }">
            <span style="font-weight: 500">{{ row.name }}</span>
            <el-tag v-if="row.level === 1" size="small" type="primary" style="margin-left: 8px">一级</el-tag>
            <el-tag v-else size="small" style="margin-left: 8px">二级</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="100" align="center" />
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-switch :model-value="row.status === 1" @change="handleStatus(row)" />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" align="center" />
        <el-table-column label="操作" width="240" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleAdd(row.id)" v-if="row.level < 2">添加子分类</el-button>
            <el-button size="small" type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑分类' : '新增分类'" width="500px">
      <el-form :model="form" label-width="80px">
        <el-form-item label="父级分类">
          <el-select v-model="form.parent_id" placeholder="请选择父级分类" style="width: 100%">
            <el-option :value="0" label="一级分类" />
            <el-option v-for="c in parentCategories" :key="c.id" :value="c.id" :label="c.name" />
          </el-select>
        </el-form-item>
        <el-form-item label="分类名称">
          <el-input v-model="form.name" placeholder="请输入分类名称" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="999" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getCategoryList, createCategory, updateCategory, deleteCategory } from '@/api/category'

const treeData = ref([])
const dialogVisible = ref(false)
const isEdit = ref(false)
const form = ref({ id: null, parent_id: 0, name: '', sort: 0, status: 1 })

const parentCategories = computed(() => treeData.value.filter(c => c.level === 1))

const fetchList = async () => {
  const res = await getCategoryList()
  const list = res.data.list || []
  treeData.value = buildTree(list)
}
const buildTree = (list, parentId = 0) => {
  return list.filter(c => c.parent_id == parentId).map(c => ({ ...c, children: buildTree(list, c.id) }))
}
const handleAdd = parentId => {
  isEdit.value = false
  form.value = { id: null, parent_id: parentId, name: '', sort: 0, status: 1 }
  dialogVisible.value = true
}
const handleEdit = row => {
  isEdit.value = true
  form.value = { ...row }
  dialogVisible.value = true
}
const handleSubmit = async () => {
  if (!form.value.name) { ElMessage.warning('请输入分类名称'); return }
  if (isEdit.value) {
    await updateCategory(form.value.id, form.value)
    ElMessage.success('更新成功')
  } else {
    await createCategory(form.value)
    ElMessage.success('创建成功')
  }
  dialogVisible.value = false
  fetchList()
}
const handleDelete = async row => {
  await ElMessageBox.confirm(`确定删除分类"${row.name}"？`, '提示', { type: 'warning' })
  await deleteCategory(row.id)
  ElMessage.success('删除成功')
  fetchList()
}
const handleStatus = async row => {
  await updateCategory(row.id, { status: row.status === 1 ? 0 : 1 })
  ElMessage.success('状态更新成功')
  fetchList()
}
onMounted(fetchList)
</script>
<style scoped>
.card-header { display: flex; justify-content: space-between; align-items: center; }
</style>
