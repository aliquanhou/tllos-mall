<template>
  <el-card shadow="never">
    <template #header><span>通知设置</span></template>
    <el-table :data="list" border>
      <el-table-column prop="name" label="通知场景" min-width="150" />
      <el-table-column label="短信通知" width="100" align="center"><template #default="{row}"><el-switch v-model="row.sms_enabled" :active-value="1" :inactive-value="0" @change="handleUpdate(row)" /></template></el-table-column>
      <el-table-column label="小程序通知" width="120" align="center"><template #default="{row}"><el-switch v-model="row.mp_enabled" :active-value="1" :inactive-value="0" @change="handleUpdate(row)" /></template></el-table-column>
      <el-table-column label="APP通知" width="100" align="center"><template #default="{row}"><el-switch v-model="row.app_enabled" :active-value="1" :inactive-value="0" @change="handleUpdate(row)" /></template></el-table-column>
      <el-table-column prop="content" label="通知模板" min-width="250" show-overflow-tooltip />
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getNoticeSettingList, updateNoticeSetting } from '@/api/systemExtra'
const list = ref([])
const fetchList = async () => { const res = await getNoticeSettingList(); list.value = res.data.list||[] }
const handleUpdate = async row => { await updateNoticeSetting(row.id,{sms_enabled:row.sms_enabled,mp_enabled:row.mp_enabled,app_enabled:row.app_enabled}); ElMessage.success('更新成功') }
onMounted(fetchList)
</script>
