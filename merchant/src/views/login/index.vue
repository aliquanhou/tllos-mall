<template>
  <div class="login-page">
    <div class="login-box">
      <div class="login-left">
        <h1>{{ t('login.title') }}</h1>
        <p>{{ t('login.subtitle') }}</p>
        <div class="features">
          <div class="feature-item"><el-icon><Check /></el-icon><span>多商户入驻 · 独立店铺</span></div>
          <div class="feature-item"><el-icon><Check /></el-icon><span>商品/订单/财务 全链路管理</span></div>
          <div class="feature-item"><el-icon><Check /></el-icon><span>营销工具 · 数据看板</span></div>
        </div>
      </div>
      <div class="login-right">
        <h2>{{ t('login.submit') }}</h2>
        <el-form ref="formRef" :model="form" :rules="rules" @keyup.enter="handleLogin">
          <el-form-item prop="account"><el-input v-model="form.account" :placeholder="t('login.accountPlaceholder')" size="large" :prefix-icon="User" /></el-form-item>
          <el-form-item prop="password"><el-input v-model="form.password" type="password" :placeholder="t('login.passwordPlaceholder')" size="large" :prefix-icon="Lock" show-password /></el-form-item>
          <el-button type="success" size="large" class="login-btn" :loading="loading" @click="handleLogin">{{ t('login.submit') }}</el-button>
        </el-form>
        <div class="lang-switch" @click="switchLang">{{ locale === 'zh' ? 'English' : '中文' }}</div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { useUserStore } from '@/stores/user'
const router = useRouter()
const { t, locale } = useI18n()
const userStore = useUserStore()
const formRef = ref()
const loading = ref(false)
const form = reactive({ account: '', password: '' })
const rules = { account: [{ required: true, message: '请输入账号', trigger: 'blur' }], password: [{ required: true, message: '请输入密码', trigger: 'blur' }] }
const handleLogin = async () => {
  await formRef.value.validate()
  loading.value = true
  try { await userStore.login(form); ElMessage.success(t('login.loginSuccess')); router.push('/') }
  catch (e) {} finally { loading.value = false }
}
const switchLang = () => { const nl = locale.value === 'zh' ? 'en' : 'zh'; locale.value = nl; localStorage.setItem('tllos_locale', nl) }
</script>
<style scoped>
.login-page { height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a2332 0%, #2d4a3e 100%); }
.login-box { width: 820px; height: 480px; background: #fff; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.3); display: flex; overflow: hidden; }
.login-left { flex: 1; background: linear-gradient(135deg, #1a2332 0%, #234d3a 100%); color: #fff; padding: 40px 32px; display: flex; flex-direction: column; justify-content: center; }
.login-left h1 { font-size: 28px; margin-bottom: 10px; }
.login-left p { color: #a0aec0; margin-bottom: 32px; }
.features { display: flex; flex-direction: column; gap: 14px; }
.feature-item { display: flex; align-items: center; gap: 10px; color: #cbd5e0; font-size: 13px; }
.feature-item .el-icon { color: #52c41a; }
.login-right { flex: 1; padding: 40px 32px; display: flex; flex-direction: column; justify-content: center; }
.login-right h2 { font-size: 22px; margin-bottom: 28px; color: #1a2332; }
.login-btn { width: 100%; margin-top: 8px; }
.lang-switch { text-align: center; margin-top: 16px; color: #909399; font-size: 13px; cursor: pointer; }
</style>
