<template>
  <el-card shadow="never">
    <template #header><span>用户实名认证</span></template>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="ID" width="80" align="center" />
      <el-table-column prop="mobile" label="手机号" width="130" />
      <el-table-column prop="real_name" label="真实姓名" width="120" />
      <el-table-column prop="id_card" label="身份证号" width="200" />
      <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':row.status===2?'danger':'warning'" size="small">{{row.status===1?'已通过':row.status===2?'已拒绝':'待审核'}}</el-tag></template></el-table-column>
      <el-table-column prop="audit_time" label="审核时间" width="170" align="center" />
      <el-table-column label="操作" width="180" align="center"><template #default="{row}"><el-button v-if="row.status===0" size="small" type="success" @click="handleAudit(row,1)">通过</el-button><el-button v-if="row.status===0" size="small" type="danger" @click="handleAudit(row,2)">拒绝</el-button></template></el-table-column>
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getUserAuthList, auditUserAuth } from '@/api/tools'
const list = ref([]); const loading = ref(false)
const fetchList = async () => { loading.value=true; try { const res = await getUserAuthList(); list.value = res.data.list||[] } finally { loading.value=false } }
const handleAudit = async (row, status) => { const { value: remark } = await ElMessageBox.prompt('请输入审核备注','审核',{confirmButtonText:'确定',cancelButtonText:'取消'}); await auditUserAuth(row.id,{status,remark}); ElMessage.success(status===1?'审核通过':'审核拒绝'); fetchList() }
onMounted(fetchList)
</script>
