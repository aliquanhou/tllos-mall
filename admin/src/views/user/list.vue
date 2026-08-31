<template>
  <div class="user-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词"><el-input v-model="searchForm.keyword" placeholder="昵称/手机号/邮箱" clearable style="width:200px" @keyup.enter="fetchList" /></el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width:120px">
            <el-option label="正常" :value="1" /><el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetSearch">重置</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-row :gutter="16" style="margin-top:16px">
      <el-col :span="6" v-for="(item, key) in stats" :key="key">
        <el-card shadow="never" class="stat-card"><div class="stat-value">{{ item }}</div><div class="stat-label">{{ statLabels[key] }}</div></el-card>
      </el-col>
    </el-row>
    <el-card shadow="never" style="margin-top:16px">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="用户信息" min-width="200">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :src="row.avatar" :size="40">{{ row.nickname?.charAt(0) || 'U' }}</el-avatar>
              <div class="user-detail">
                <div class="user-name">{{ row.nickname || '未设置' }}</div>
                <div class="user-mobile">{{ row.mobile }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="email" label="邮箱" width="180" />
        <el-table-column label="性别" width="80"><template #default="{ row }">{{ genderMap[row.gender] || '未知' }}</template></el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }"><el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleToggleStatus(row)" /></template>
        </el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="170" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }"><el-button type="primary" link size="small" @click="handleDetail(row)">详情</el-button></template>
        </el-table-column>
      </el-table>
      <div class="pagination"><el-pagination v-model:current-page="page" v-model:page-size="limit" :page-sizes="[10,20,50]" :total="total" layout="total,sizes,prev,pager,next,jumper" @size-change="fetchList" @current-change="fetchList" /></div>
    </el-card>
    <el-dialog v-model="detailVisible" title="用户详情" width="600px">
      <div v-if="currentUser">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="用户ID">{{ currentUser.id }}</el-descriptions-item>
          <el-descriptions-item label="昵称">{{ currentUser.nickname || '未设置' }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ currentUser.mobile }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ currentUser.email || '未绑定' }}</el-descriptions-item>
          <el-descriptions-item label="性别">{{ genderMap[currentUser.gender] || '未知' }}</el-descriptions-item>
          <el-descriptions-item label="状态"><el-tag :type="currentUser.status==1?'success':'danger'">{{ currentUser.status==1?'正常':'禁用' }}</el-tag></el-descriptions-item>
          <el-descriptions-item label="订单数">{{ currentUser.order_count }}</el-descriptions-item>
          <el-descriptions-item label="消费金额">¥{{ currentUser.order_amount }}</el-descriptions-item>
          <el-descriptions-item label="账户余额">¥{{ currentUser.balance }}</el-descriptions-item>
          <el-descriptions-item label="积分">{{ currentUser.points }}</el-descriptions-item>
          <el-descriptions-item label="注册时间" :span="2">{{ currentUser.created_at }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getUserList, getUser, toggleUserStatus } from '@/api/user'
const genderMap = {0:'未知',1:'男',2:'女'}
const statLabels = {total:'总用户',today:'今日新增',active:'正常用户',disabled:'禁用用户'}
const loading = ref(false)
const list = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const stats = ref({})
const detailVisible = ref(false)
const currentUser = ref(null)
const searchForm = reactive({ keyword:'', status:null })
const fetchList = async () => {
  loading.value = true
  try {
    const res = await getUserList({ page:page.value, limit:limit.value, keyword:searchForm.keyword||undefined, status:searchForm.status!==null?searchForm.status:undefined })
    list.value = res.data.list
    total.value = res.data.total
    stats.value = res.data.stats || {}
  } catch(e) { console.error(e) } finally { loading.value = false }
}
const resetSearch = () => { searchForm.keyword=''; searchForm.status=null; page.value=1; fetchList() }
const handleToggleStatus = async (row) => {
  try { await toggleUserStatus(row.id); ElMessage.success(row.status==1?'已启用':'已禁用') } catch(e) { row.status = row.status==1?0:1 }
}
const handleDetail = async (row) => { try { const res = await getUser(row.id); currentUser.value=res.data; detailVisible.value=true } catch(e){console.error(e)} }
onMounted(fetchList)
</script>
<style scoped>
.stat-card{text-align:center}
.stat-value{font-size:24px;font-weight:bold;color:#303133}
.stat-label{font-size:13px;color:#909399;margin-top:4px}
.user-info{display:flex;align-items:center;gap:12px}
.user-detail{flex:1}
.user-name{font-size:14px;color:#303133;font-weight:500}
.user-mobile{font-size:12px;color:#909399;margin-top:2px}
.pagination{margin-top:20px;display:flex;justify-content:flex-end}
</style>
