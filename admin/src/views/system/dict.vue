<template>
  <el-card shadow="never">
    <template #header><div class="card-header"><span>数据字典</span><el-button type="primary" @click="handleAddType">新增字典类型</el-button></div></template>
    <el-row :gutter="16">
      <el-col :span="8">
        <el-table :data="typeList" border highlight-current-row @current-change="handleTypeChange">
          <el-table-column prop="name" label="字典名称" />
          <el-table-column prop="code" label="字典编码" width="120" />
          <el-table-column label="操作" width="120" align="center"><template #default="{row}"><el-button size="small" type="danger" link @click.stop="handleDeleteType(row)">删除</el-button></template></el-table-column>
        </el-table>
      </el-col>
      <el-col :span="16">
        <div class="dict-data-header"><span>字典数据 - {{currentType?.name||'请选择字典类型'}}</span><el-button type="primary" size="small" :disabled="!currentType" @click="handleAddData">新增数据</el-button></div>
        <el-table :data="dataList" border>
          <el-table-column prop="label" label="标签" min-width="150" />
          <el-table-column prop="value" label="键值" width="150" />
          <el-table-column prop="sort" label="排序" width="80" align="center" />
          <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
          <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button size="small" @click="handleEditData(row)">编辑</el-button><el-button size="small" type="danger" @click="handleDeleteData(row)">删除</el-button></template></el-table-column>
        </el-table>
      </el-col>
    </el-row>
    <el-dialog v-model="typeDialogVisible" title="字典类型" width="400px">
      <el-form :model="typeForm" label-width="80px">
        <el-form-item label="名称"><el-input v-model="typeForm.name" /></el-form-item>
        <el-form-item label="编码"><el-input v-model="typeForm.code" /></el-form-item>
        <el-form-item label="备注"><el-input v-model="typeForm.remark" type="textarea" :rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="typeDialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmitType">确定</el-button></template>
    </el-dialog>
    <el-dialog v-model="dataDialogVisible" title="字典数据" width="400px">
      <el-form :model="dataForm" label-width="80px">
        <el-form-item label="标签"><el-input v-model="dataForm.label" /></el-form-item>
        <el-form-item label="键值"><el-input v-model="dataForm.value" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="dataForm.sort" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="dataForm.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dataDialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmitData">确定</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getDictTypeList, createDictType, deleteDictType, getDictDataList, createDictData, updateDictData, deleteDictData } from '@/api/systemConfig'
const typeList = ref([]); const dataList = ref([]); const currentType = ref(null)
const typeDialogVisible = ref(false); const dataDialogVisible = ref(false)
const typeForm = ref({ name:'', code:'', remark:'' })
const dataForm = ref({ id:null, label:'', value:'', sort:0, status:1 })
const fetchTypes = async () => { const res = await getDictTypeList(); typeList.value = res.data.list||[] }
const fetchData = async () => { if(!currentType.value) return; const res = await getDictDataList({type_id:currentType.value.id}); dataList.value = res.data.list||[] }
const handleTypeChange = row => { currentType.value = row; fetchData() }
const handleAddType = () => { typeForm.value={name:'',code:'',remark:''}; typeDialogVisible.value=true }
const handleSubmitType = async () => { if(!typeForm.value.name||!typeForm.value.code){ElMessage.warning('请填写完整');return}; await createDictType(typeForm.value); ElMessage.success('创建成功'); typeDialogVisible.value=false; fetchTypes() }
const handleDeleteType = async row => { await ElMessageBox.confirm(`确定删除字典类型"${row.name}"？`,'提示',{type:'warning'}); await deleteDictType(row.id); ElMessage.success('删除成功'); if(currentType.value?.id===row.id){currentType.value=null;dataList.value=[]}; fetchTypes() }
const handleAddData = () => { dataForm.value={id:null,label:'',value:'',sort:0,status:1}; dataDialogVisible.value=true }
const handleEditData = row => { dataForm.value={...row}; dataDialogVisible.value=true }
const handleSubmitData = async () => { if(!dataForm.value.label||!dataForm.value.value){ElMessage.warning('请填写完整');return}; dataForm.value.type_id=currentType.value.id; if(dataForm.value.id){await updateDictData(dataForm.value.id,dataForm.value);ElMessage.success('更新成功')}else{await createDictData(dataForm.value);ElMessage.success('创建成功')}; dataDialogVisible.value=false; fetchData() }
const handleDeleteData = async row => { await ElMessageBox.confirm('确定删除该字典数据？','提示',{type:'warning'}); await deleteDictData(row.id); ElMessage.success('删除成功'); fetchData() }
onMounted(fetchTypes)
</script>
<style scoped>.dict-data-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}</style>
