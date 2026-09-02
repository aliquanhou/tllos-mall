<template>
  <div class="coupon-page">
    <div class="coupon-list">
      <div v-for="item in list" :key="item.id" class="coupon-item" :class="{used:item.status===1,expired:item.status===2}">
        <div class="coupon-left"><div class="amount">¥{{item.amount}}</div><div class="condition">满{{item.min_amount}}可用</div></div>
        <div class="coupon-right"><div class="name">{{item.name}}</div><div class="time">{{item.start_time}} 至 {{item.end_time}}</div><div class="status">{{statusText[item.status]||'未使用'}}</div></div>
      </div>
      <div v-if="list.length===0" class="empty">暂无优惠券</div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
const list = ref([])
const statusText = {0:'可使用',1:'已使用',2:'已过期'}
onMounted(async () => { const res = await request({url:'/user/coupons'}); list.value = res.data || [] })
</script>
<style scoped>.coupon-page{min-height:100vh;background:#f5f5f5;padding:12px}.coupon-item{display:flex;background:#fff;border-radius:12px;margin-bottom:12px;overflow:hidden}.coupon-left{width:120px;background:linear-gradient(135deg,#f56c6c,#e64c4c);color:#fff;padding:20px 12px;text-align:center;display:flex;flex-direction:column;justify-content:center}.coupon-left .amount{font-size:28px;font-weight:700}.coupon-left .condition{font-size:12px;opacity:.9;margin-top:4px}.coupon-right{flex:1;padding:16px;display:flex;flex-direction:column;justify-content:center}.coupon-right .name{font-size:16px;font-weight:600;margin-bottom:8px}.coupon-right .time{font-size:12px;color:#999;margin-bottom:4px}.coupon-right .status{font-size:13px;color:#67c23a}.coupon-item.used .coupon-left{background:#ccc}.coupon-item.expired .coupon-left{background:#999}.empty{text-align:center;padding:60px 0;color:#999}</style>
