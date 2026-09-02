<template>
  <div class="profile">
    <div class="user-header">
      <div class="avatar">{{user.nickname?.charAt(0)||'U'}}</div>
      <div class="user-info">
        <div class="nickname">{{user.nickname||'未登录'}}</div>
        <div class="mobile">{{user.mobile||''}}</div>
      </div>
    </div>
    <div class="stats-row">
      <div class="stat-item" @click="$router.push('/orders')"><div class="num">{{data.order_count||0}}</div><div class="label">全部订单</div></div>
      <div class="stat-item" @click="$router.push('/coupons')"><div class="num">{{data.coupon_count||0}}</div><div class="label">优惠券</div></div>
      <div class="stat-item" @click="$router.push('/collects')"><div class="num">{{data.collect_count||0}}</div><div class="label">收藏</div></div>
    </div>
    <div class="balance-row">
      <div class="balance-item"><div class="num">¥{{data.balance||0}}</div><div class="label">余额</div></div>
      <div class="balance-item"><div class="num">{{data.points||0}}</div><div class="label">积分</div></div>
    </div>
    <div class="menu-list">
      <div class="menu-item" @click="$router.push('/address')"><span>收货地址</span><span>›</span></div>
      <div class="menu-item" @click="$router.push('/orders')"><span>我的订单</span><span>›</span></div>
      <div class="menu-item" @click="$router.push('/coupons')"><span>优惠券</span><span>›</span></div>
      <div class="menu-item" @click="$router.push('/collects')"><span>我的收藏</span><span>›</span></div>
      <div class="menu-item" @click="handleLogout"><span>退出登录</span><span>›</span></div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import request from '@/utils/request'
const router = useRouter()
const user = ref({}); const data = ref({})
onMounted(async () => {
  const token = localStorage.getItem('tllos_h5_token')
  if(!token){router.push('/login');return}
  try { const res = await request({url:'/user/center'}); user.value=res.data.user_info||{}; data.value=res.data } catch(e){ router.push('/login') }
})
const handleLogout = () => { localStorage.removeItem('tllos_h5_token'); router.push('/login') }
</script>
<style scoped>.profile{min-height:100vh;background:#f5f5f5;padding-bottom:60px}.user-header{display:flex;align-items:center;gap:16px;padding:30px 20px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}.avatar{width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:600}.user-info .nickname{font-size:18px;font-weight:600}.user-info .mobile{font-size:13px;opacity:.8;margin-top:4px}.stats-row{display:flex;background:#fff;margin:-20px 16px 0;border-radius:12px;padding:20px 0;position:relative;z-index:1}.stat-item{flex:1;text-align:center;cursor:pointer}.stat-item .num{font-size:20px;font-weight:700;color:#333}.stat-item .label{font-size:12px;color:#999;margin-top:4px}.balance-row{display:flex;background:#fff;margin:12px 16px;border-radius:12px;padding:20px 0}.balance-item{flex:1;text-align:center;border-right:1px solid #eee}.balance-item:last-child{border-right:none}.balance-item .num{font-size:18px;font-weight:700;color:#f56c6c}.balance-item .label{font-size:12px;color:#999;margin-top:4px}.menu-list{background:#fff;margin:0 16px;border-radius:12px;overflow:hidden}.menu-item{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #f5f5f5;font-size:15px;color:#333;cursor:pointer}.menu-item:last-child{border-bottom:none}</style>
