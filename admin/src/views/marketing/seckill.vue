<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>限时秒杀</span><el-button type="primary" @click="handleAdd">新增活动</el-button></div></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="活动名称" clearable style="width:180px" /></el-form-item>
      <el-form-item label="状态"><el-select v-model="query.status" placeholder="全部" clearable style="width:120px"><el-option :value="1" label="进行中" /><el-option :value="0" label="未开始" /></el-select></el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="name" label="活动名称" min-width="180" />
      <el-table-column label="活动时间" width="280" align="center"><template #default="{row}">{{row.start_time?.slice(0,16)}} 至 {{row.end_time?.slice(0,16)}}</template></el-table-column>
      <el-table-column label="秒杀商品数" width="110" align="center"><template #default="{row}">{{row.goods_count}}</template></el-table-column>
      <el-table-column prop="sort" label="排序" width="80" align="center" />
      <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'进行中':'未开始'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="180" align="center" fixed="right"><template #default="{row}"><el-button size="small" @click="handleEdit(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button></template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑活动':'新增活动'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="活动名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="活动时间"><el-date-picker v-model="dateRange" type="datetimerange" range-separator="至" start-placeholder="开始时间" end-placeholder="结束时间" style="width:100%" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getSeckillList, createSeckill, updateSeckill, deleteSeckill } from '@/api/marketing'
const list = ref([]); const total = ref(0); const loading = ref(false); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', sort:0, status:1 }); const dateRange = ref([])
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const fetchList = async () => { loading.value=true; try { const res = await getSeckillList(query); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',status:null}); fetchList() }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',sort:0,status:1}; dateRange.value=[]; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dateRange.value=[row.start_time,row.end_time]; dialogVisible.value=true }
const handleSubmit = async () => { if (!form.value.name) { ElMessage.warning('请输入活动名称'); return }; const data={...form.value}; if(dateRange.value&&dateRange.value.length===2){data.start_time=dateRange.value[0];data.end_time=dateRange.value[1]}; if(isEdit.value){await updateSeckill(form.value.id,data);ElMessage.success('更新成功')}else{await createSeckill(data);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除活动"${row.name}"？`,'提示',{type:'warning'}); await deleteSeckill(row.id); ElMessage.success('删除成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.card-header{display:flex;justify-content:space-between;align-items:center}.search-form{margin-bottom:16px}</style>
