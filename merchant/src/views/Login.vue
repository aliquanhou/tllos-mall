<template>
  <div class="login-container">
    <div class="login-box">
      <h1 class="title">TLLOS 商家后台</h1>
      <p class="subtitle">多商户商城系统</p>
      <el-form :model="form" @submit.prevent="handleLogin">
        <el-form-item><el-input v-model="form.username" placeholder="商家账号" size="large" prefix-icon="User" /></el-form-item>
        <el-form-item><el-input v-model="form.password" type="password" placeholder="密码" size="large" prefix-icon="Lock" show-password @keyup.enter="handleLogin" /></el-form-item>
        <el-button type="primary" size="large" style="width:100%" :loading="loading" @click="handleLogin">登 录</el-button>
      </el-form>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
const router = useRouter()
const form = reactive({ username:'', password:'' })
const loading = ref(false)
const handleLogin = async () => {
  if(!form.username||!form.password){ElMessage.warning('请输入账号密码');return}
  loading.value=true
  try {
    const res = await request({ url:'/merchant/login', method:'post', data:form })
    localStorage.setItem('tllos_merchant_token', res.data.token)
    localStorage.setItem('tllos_merchant_info', JSON.stringify(res.data.shop))
    ElMessage.success('登录成功')
    router.push('/')
  } finally { loading.value=false }
}
</script>
<style scoped>.login-container{height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}.login-box{width:400px;padding:40px;background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.3)}.title{text-align:center;font-size:24px;margin:0 0 8px;color:#333}.subtitle{text-align:center;color:#999;margin:0 0 30px;font-size:14px}</style>
