<template>
  <div class="brand-page">
    <el-card>
      <template #header>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>品牌管理</span>
          <el-button type="primary" size="small" @click="showDialog=true">新增品牌</el-button>
        </div>
      </template>
      <el-table :data="list" border>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="Logo" width="100">
          <template #default="{row}"><el-image v-if="row.logo" :src="row.logo" style="width:50px;height:50px" fit="cover" /></template>
        </el-table-column>
        <el-table-column prop="name" label="品牌名称" />
        <el-table-column prop="description" label="描述" show-overflow-tooltip />
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="状态" width="90">
          <template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="{row}">
            <el-button size="small" @click="edit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="remove(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" @current-change="loadList" style="margin-top:15px;justify-content:flex-end" />
    </el-card>
    <el-dialog v-model="showDialog" :title="form.id?'编辑品牌':'新增品牌'" width="500px">
      <el-form :model="form" label-width="80px">
        <el-form-item label="品牌名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="Logo"><el-input v-model="form.logo" placeholder="图片URL" /></el-form-item>
        <el-form-item label="描述"><el-input v-model="form.description" type="textarea" :rows="3" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="showDialog=false">取消</el-button><el-button type="primary" @click="submit">确定</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/utils/request'
const list = ref([]); const total = ref(0); const page = ref(1); const limit = ref(20)
const showDialog = ref(false)
const form = reactive({ id:null, name:'', logo:'', description:'', sort:0, status:1 })
const loadList = async () => {
  const res = await request({ url:'/admin/brands', params:{ page:page.value, limit:limit.value } })
  list.value = res.data?.list || []; total.value = res.data?.total || 0
}
const edit = (row) => { Object.assign(form, row); showDialog.value = true }
const remove = async (row) => {
  await ElMessageBox.confirm('确定删除？','提示',{type:'warning'})
  await request({ url:`/admin/brands/${row.id}`, method:'delete' })
  ElMessage.success('删除成功'); loadList()
}
const submit = async () => {
  if (form.id) await request({ url:`/admin/brands/${form.id}`, method:'put', data:form })
  else await request({ url:'/admin/brands', method:'post', data:form })
  ElMessage.success('保存成功'); showDialog.value = false; loadList()
}
onMounted(() => loadList())
</script>
