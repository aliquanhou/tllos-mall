<template>
  <div class="login-page">
    <div class="login-box">
      <div class="login-left">
        <h1>{{ t('login.title') }}</h1>
        <p>{{ t('login.subtitle') }}</p>
        <div class="features">
          <div class="feature-item"><el-icon><Check /></el-icon><span>Laravel 11 + Vue3 全新架构</span></div>
          <div class="feature-item"><el-icon><Check /></el-icon><span>17大模块 · 多商户商城</span></div>
          <div class="feature-item"><el-icon><Check /></el-icon><span>H5 + 小程序 + Flutter APK</span></div>
        </div>
      </div>
      <div class="login-right">
        <h2>{{ t('login.submit') }}</h2>
        <el-form ref="formRef" :model="form" :rules="rules" @keyup.enter="handleLogin">
          <el-form-item prop="username">
            <el-input v-model="form.username" :placeholder="t('login.usernamePlaceholder')" size="large" :prefix-icon="User" />
          </el-form-item>
          <el-form-item prop="password">
            <el-input v-model="form.password" type="password" :placeholder="t('login.passwordPlaceholder')" size="large" :prefix-icon="Lock" show-password />
          </el-form-item>
          <el-form-item>
            <el-checkbox v-model="form.remember">{{ t('login.remember') }}</el-checkbox>
            <el-dropdown @command="switchLang" style="float:right">
              <span style="cursor:pointer;color:#409eff">{{ appStore.locale === 'zh' ? '中文 / EN' : 'EN / 中文' }}</span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="zh">简体中文</el-dropdown-item>
                  <el-dropdown-item command="en">English</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </el-form-item>
          <el-button type="primary" size="large" class="login-btn" :loading="loading" @click="handleLogin">{{ t('login.submit') }}</el-button>
        </el-form>
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
import { useAppStore } from '@/stores/app'

const router = useRouter()
const { t, locale } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()
const formRef = ref()
const loading = ref(false)
const form = reactive({ username: '', password: '', remember: false })
const rules = {
  username: [{ required: true, message: '请输入管理员账号', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
}

async function handleLogin() {
  await formRef.value.validate()
  loading.value = true
  try {
    await userStore.login(form)
    ElMessage.success(t('login.loginSuccess'))
    router.push('/')
  } catch (e) {} finally { loading.value = false }
}

function switchLang(cmd) {
  appStore.setLocale(cmd)
  locale.value = cmd
}
</script>

<style scoped>
.login-page { height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.login-box { width: 860px; height: 520px; background: #fff; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.3); display: flex; overflow: hidden; }
.login-left { flex: 1; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; }
.login-left h1 { font-size: 32px; margin-bottom: 12px; }
.login-left p { color: #a0aec0; margin-bottom: 40px; }
.features { display: flex; flex-direction: column; gap: 16px; }
.feature-item { display: flex; align-items: center; gap: 10px; color: #cbd5e0; font-size: 14px; }
.feature-item .el-icon { color: #48bb78; }
.login-right { flex: 1; padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; }
.login-right h2 { font-size: 24px; margin-bottom: 30px; color: #1a202c; }
.login-btn { width: 100%; margin-top: 10px; }
</style>
