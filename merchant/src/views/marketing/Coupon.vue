<template>
  <div class="coupon-list">
    <el-card>
      <template #header>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>优惠券管理</span>
          <el-button type="primary" size="small">新增优惠券</el-button>
        </div>
      </template>
      <el-table :data="list">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="名称" />
        <el-table-column prop="value" label="面值" width="120" />
        <el-table-column prop="min_amount" label="满减门槛" width="120" />
        <el-table-column prop="total_count" label="总数" width="100" />
        <el-table-column prop="used_count" label="已领取" width="100" />
        <el-table-column prop="status" label="状态" width="100" />
      </el-table>
    </el-card>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
const list = ref([])
onMounted(async () => {
  try {
    const res = await request({ url: '/merchant/coupons' })
    list.value = res.data?.list || []
  } catch (e) { console.error(e) }
})
</script>
