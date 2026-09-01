<template>
  <div class="finance-list">
    <el-card>
      <template #header>财务管理</template>
      <el-row :gutter="16">
        <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">¥{{data.total_income||0}}</div><div class="stat-label">累计收入</div></div></el-card></el-col>
        <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">¥{{data.available_income||0}}</div><div class="stat-label">可提现</div></div></el-card></el-col>
        <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">{{data.withdraw_count||0}}</div><div class="stat-label">提现次数</div></div></el-card></el-col>
        <el-col :span="6"><el-card><div class="stat-item"><div class="stat-value">¥{{data.withdraw_amount||0}}</div><div class="stat-label">已提现</div></div></el-card></el-col>
      </el-row>
      <el-table :data="list" style="margin-top:16px">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="type" label="类型" width="120" />
        <el-table-column prop="amount" label="金额" width="120" />
        <el-table-column prop="status" label="状态" width="100" />
        <el-table-column prop="created_at" label="时间" />
      </el-table>
    </el-card>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
const data = ref({})
const list = ref([])
onMounted(async () => {
  try {
    const res = await request({ url: '/merchant/finance' })
    data.value = res.data?.stats || {}
    list.value = res.data?.list || []
  } catch (e) { console.error(e) }
})
</script>
<style scoped>.stat-item{text-align:center;padding:10px 0}.stat-value{font-size:24px;font-weight:700;color:#333}.stat-label{color:#999;font-size:14px;margin-top:8px}</style>
