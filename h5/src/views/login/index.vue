<template>
  <div class="login-page">
    <div class="login-header">
      <h1>TLLOS Mall</h1>
      <p>{{ t('login.title') }}</p>
    </div>
    <div class="login-form card">
      <div class="tabs">
        <span :class="{ active: mode === 'login' }" @click="mode = 'login'">{{ t('common.login') }}</span>
        <span :class="{ active: mode === 'register' }" @click="mode = 'register'">{{ t('common.register') }}</span>
      </div>
      <div class="form-item">
        <label>{{ t('login.mobile') }}</label>
        <input v-model="form.mobile" type="tel" placeholder="请输入手机号" maxlength="11" />
      </div>
      <div v-if="mode === 'register'" class="form-item">
        <label>{{ t('login.smsCode') }}</label>
        <div class="code-input">
          <input v-model="form.code" type="text" placeholder="请输入验证码" maxlength="6" />
          <button class="code-btn" :disabled="countdown > 0" @click="sendCode">
            {{ countdown > 0 ? countdown + 's' : t('login.getCode') }}
          </button>
        </div>
      </div>
      <div class="form-item">
        <label>{{ t('login.password') }}</label>
        <input v-model="form.password" type="password" placeholder="请输入密码" />
      </div>
      <button class="btn-primary submit-btn" :disabled="loading" @click="handleSubmit">
        {{ loading ? t('common.loading') : (mode === 'login' ? t('login.loginBtn') : t('login.registerBtn')) }}
      </button>
      <div class="lang-switch" @click="switchLang">{{ locale === 'zh' ? 'English' : '中文' }}</div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/stores/user'
const { t, locale } = useI18n()
const router = useRouter()
const userStore = useUserStore()
const mode = ref('login')
const loading = ref(false)
const countdown = ref(0)
const form = reactive({ mobile: '', password: '', code: '' })

const sendCode = () => {
  if (!form.mobile || form.mobile.length !== 11) { alert('请输入正确的手机号'); return }
  countdown.value = 60
  const timer = setInterval(() => { countdown.value--; if (countdown.value <= 0) clearInterval(timer) }, 1000)
}

const handleSubmit = async () => {
  if (!form.mobile || form.mobile.length !== 11) { alert('请输入正确的手机号'); return }
  if (!form.password || form.password.length < 6) { alert('密码至少6位'); return }
  loading.value = true
  try {
    if (mode.value === 'login') {
      await userStore.login({ account: form.mobile, password: form.password })
    } else {
      await userStore.login({ account: form.mobile, password: form.password })
    }
    router.back() || router.push('/home')
  } catch (e) {} finally { loading.value = false }
}

const switchLang = () => {
  const newLang = locale.value === 'zh' ? 'en' : 'zh'
  locale.value = newLang
  localStorage.setItem('tllos_locale', newLang)
}
</script>
<style scoped>
.login-page { min-height: 100vh; background: linear-gradient(180deg, var(--primary) 0%, var(--bg) 40%); padding: 60px 20px 20px; }
.login-header { text-align: center; color: #fff; margin-bottom: 30px; }
.login-header h1 { font-size: 28px; margin-bottom: 8px; }
.login-header p { font-size: 14px; opacity: 0.9; }
.login-form { padding: 24px 20px; }
.tabs { display: flex; gap: 24px; margin-bottom: 24px; }
.tabs span { font-size: 16px; color: var(--text-secondary); padding-bottom: 6px; cursor: pointer; }
.tabs span.active { color: var(--primary); font-weight: 500; border-bottom: 2px solid var(--primary); }
.form-item { margin-bottom: 18px; }
.form-item label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; }
.form-item input { width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; }
.form-item input:focus { border-color: var(--primary); }
.code-input { display: flex; gap: 10px; }
.code-input input { flex: 1; }
.code-btn { padding: 0 16px; background: var(--primary-light); color: #fff; border: none; border-radius: 8px; font-size: 13px; white-space: nowrap; cursor: pointer; }
.code-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.submit-btn { width: 100%; padding: 14px; font-size: 15px; border-radius: 24px; margin-top: 10px; }
.submit-btn:disabled { opacity: 0.6; }
.lang-switch { text-align: center; margin-top: 20px; color: var(--text-secondary); font-size: 13px; cursor: pointer; }
</style>
