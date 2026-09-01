<template>
  <div class="bargain-page">
    <el-card>
      <template #header>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>砍价活动</span>
          <el-button type="primary" size="small" @click="showDialog=true">新增活动</el-button>
        </div>
      </template>
      <el-table :data="list" border>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="活动名称" />
        <el-table-column prop="goods_name" label="商品" />
        <el-table-column label="原价" width="100"><template #default="{row}">¥{{row.original_price}}</template></el-table-column>
        <el-table-column label="最低价" width="100"><template #default="{row}">¥{{row.min_price}}</template></el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'进行中':'已结束'}}</el-tag></template>
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
    <el-dialog v-model="showDialog" :title="form.id?'编辑活动':'新增活动'" width="600px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="活动名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="商品ID"><el-input-number v-model="form.goods_id" :min="1" /></el-form-item>
        <el-form-item label="原价"><el-input-number v-model="form.original_price" :min="0.01" :precision="2" /></el-form-item>
        <el-form-item label="最低价"><el-input-number v-model="form.min_price" :min="0.01" :precision="2" /></el-form-item>
        <el-form-item label="砍价范围"><el-input-number v-model="form.bargain_min" :min="0.01" :precision="2" style="width:120px" /> - <el-input-number v-model="form.bargain_max" :min="0.01" :precision="2" style="width:120px" /></el-form-item>
        <el-form-item label="开始时间"><el-date-picker v-model="form.start_time" type="datetime" /></el-form-item>
        <el-form-item label="结束时间"><el-date-picker v-model="form.end_time" type="datetime" /></el-form-item>
        <el-form-item label="总数量"><el-input-number v-model="form.total_count" :min="0" /></el-form-item>
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
const form = reactive({ id:null, name:'', goods_id:1, original_price:0, min_price:0, bargain_min:1, bargain_max:10, start_time:null, end_time:null, total_count:0, status:1 })
const loadList = async () => {
  const res = await request({ url:'/admin/bargains', params:{ page:page.value, limit:limit.value } })
  list.value = res.data?.list || []; total.value = res.data?.total || 0
}
const edit = (row) => { Object.assign(form, row); showDialog.value = true }
const remove = async (row) => {
  await ElMessageBox.confirm('确定删除？','提示',{type:'warning'})
  await request({ url:`/admin/bargains/${row.id}`, method:'delete' })
  ElMessage.success('删除成功'); loadList()
}
const submit = async () => {
  if (form.id) await request({ url:`/admin/bargains/${form.id}`, method:'put', data:form })
  else await request({ url:'/admin/bargains', method:'post', data:form })
  ElMessage.success('保存成功'); showDialog.value = false; loadList()
}
onMounted(() => loadList())
</script>
