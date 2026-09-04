<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-left">
        <div class="brand">
          <h1>TLLOS商城</h1>
          <p>品质生活 优选好物</p>
        </div>
        <div class="features">
          <div class="feature"><el-icon size="24"><CircleCheck /></el-icon><span>正品保障 假一赔十</span></div>
          <div class="feature"><el-icon size="24"><Truck /></el-icon><span>极速配送 全国包邮</span></div>
          <div class="feature"><el-icon size="24"><Refresh /></el-icon><span>7天无理由退换</span></div>
        </div>
      </div>
      <div class="login-right">
        <div class="login-card">
          <div class="login-tabs">
            <div class="tab" :class="{active: loginType === 'login'}" @click="loginType = 'login'">登录</div>
            <div class="tab" :class="{active: loginType === 'register'}" @click="loginType = 'register'">注册</div>
          </div>
          <!-- 登录表单 -->
          <el-form v-if="loginType === 'login'" :model="loginForm" :rules="loginRules" ref="loginFormRef" label-position="top">
            <el-form-item label="账号/手机号" prop="account">
              <el-input v-model="loginForm.account" size="large" placeholder="请输入账号或手机号" prefix-icon="User" />
            </el-form-item>
            <el-form-item label="密码" prop="password">
              <el-input v-model="loginForm.password" type="password" size="large" placeholder="请输入密码" prefix-icon="Lock" show-password @keyup.enter="handleLogin" />
            </el-form-item>
            <div class="login-options">
              <el-checkbox v-model="loginForm.remember">记住我</el-checkbox>
              <a href="javascript:;" class="forgot-link">忘记密码？</a>
            </div>
            <el-button type="primary" size="large" class="submit-btn" :loading="loading" @click="handleLogin">登 录</el-button>
            <div class="quick-login">
              <span class="divider">其他登录方式</span>
              <div class="login-icons">
                <el-icon size="28" class="wechat"><ChatDotRound /></el-icon>
                <el-icon size="28" class="alipay"><Wallet /></el-icon>
              </div>
            </div>
          </el-form>
          <!-- 注册表单 -->
          <el-form v-else :model="registerForm" :rules="registerRules" ref="registerFormRef" label-position="top">
            <el-form-item label="手机号" prop="mobile">
              <el-input v-model="registerForm.mobile" size="large" placeholder="请输入手机号" prefix-icon="Iphone" maxlength="11" />
            </el-form-item>
            <el-form-item label="验证码" prop="code">
              <div class="code-input">
                <el-input v-model="registerForm.code" size="large" placeholder="请输入验证码" prefix-icon="Key" maxlength="6" />
                <el-button size="large" :disabled="codeCountdown > 0" @click="sendCode">{{ codeCountdown > 0 ? codeCountdown + 's' : '获取验证码' }}</el-button>
              </div>
            </el-form-item>
            <el-form-item label="设置密码" prop="password">
              <el-input v-model="registerForm.password" type="password" size="large" placeholder="请设置密码（6-20位）" prefix-icon="Lock" show-password />
            </el-form-item>
            <el-form-item label="确认密码" prop="confirm_password">
              <el-input v-model="registerForm.confirm_password" type="password" size="large" placeholder="请再次输入密码" prefix-icon="Lock" show-password />
            </el-form-item>
            <el-form-item prop="agreement">
              <el-checkbox v-model="registerForm.agreement">我已阅读并同意 <a href="javascript:;" class="agreement-link">《用户协议》</a> 和 <a href="javascript:;" class="agreement-link">《隐私政策》</a></el-checkbox>
            </el-form-item>
            <el-button type="primary" size="large" class="submit-btn" :loading="loading" @click="handleRegister">注 册</el-button>
          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const loginType = ref(route.query.type === 'register' ? 'register' : 'login')
const loading = ref(false)
const codeCountdown = ref(0)
const loginFormRef = ref(null)
const registerFormRef = ref(null)
const loginForm = reactive({ account: '', password: '', remember: true })
const registerForm = reactive({ mobile: '', code: '', password: '', confirm_password: '', agreement: false })
const loginRules = { account: [{ required: true, message: '请输入账号或手机号', trigger: 'blur' }], password: [{ required: true, message: '请输入密码', trigger: 'blur' }, { min: 6, message: '密码至少6位', trigger: 'blur' }] }
const registerRules = { mobile: [{ required: true, message: '请输入手机号', trigger: 'blur' }, { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' }], code: [{ required: true, message: '请输入验证码', trigger: 'blur' }], password: [{ required: true, message: '请设置密码', trigger: 'blur' }, { min: 6, max: 20, message: '密码6-20位', trigger: 'blur' }], confirm_password: [{ required: true, message: '请确认密码', trigger: 'blur' }], agreement: [{ validator: (r, v, cb) => v ? cb() : cb(new Error('请阅读并同意协议')), trigger: 'change' }] }
const handleLogin = async () => {
  if (!loginFormRef.value) return
  await loginFormRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      await userStore.login({ account: loginForm.account, password: loginForm.password })
      ElMessage.success('登录成功')
      router.push('/home')
    } catch (e) { ElMessage.error(e.message || '登录失败') } finally { loading.value = false }
  })
}
const handleRegister = async () => {
  if (!registerFormRef.value) return
  await registerFormRef.value.validate(async (valid) => {
    if (!valid) return
    if (registerForm.password !== registerForm.confirm_password) { ElMessage.error('两次密码不一致'); return }
    loading.value = true
    try {
      ElMessage.success('注册成功，请登录')
      loginType.value = 'login'
      loginForm.account = registerForm.mobile
    } catch (e) { ElMessage.error(e.message || '注册失败') } finally { loading.value = false }
  })
}
const sendCode = () => {
  if (!registerForm.mobile || !/^1[3-9]\d{9}$/.test(registerForm.mobile)) { ElMessage.warning('请输入正确的手机号'); return }
  codeCountdown.value = 60
  ElMessage.success('验证码已发送')
  const timer = setInterval(() => { codeCountdown.value--; if (codeCountdown.value <= 0) clearInterval(timer) }, 1000)
}
</script>
<style scoped>
.login-page { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; padding: 20px; }
.login-container { max-width: 900px; width: 100%; display: grid; grid-template-columns: 1fr 1fr; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.login-left { background: linear-gradient(135deg, #e6a23c, #f56c6c); padding: 40px; color: #fff; display: flex; flex-direction: column; justify-content: space-between; }
.brand h1 { font-size: 36px; margin: 0 0 8px 0; }
.brand p { font-size: 16px; opacity: 0.9; margin: 0; }
.features { display: flex; flex-direction: column; gap: 20px; }
.feature { display: flex; align-items: center; gap: 12px; font-size: 15px; }
.login-right { padding: 40px; }
.login-card { }
.login-tabs { display: flex; gap: 0; margin-bottom: 24px; border-bottom: 2px solid #f0f0f0; }
.tab { flex: 1; text-align: center; padding: 12px; font-size: 16px; color: #999; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
.tab.active { color: #e6a23c; border-bottom-color: #e6a23c; font-weight: bold; }
.login-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 13px; }
.forgot-link { color: #e6a23c; text-decoration: none; }
.submit-btn { width: 100%; margin-top: 8px; background: #e6a23c; border-color: #e6a23c; }
.submit-btn:hover { background: #d4922a; border-color: #d4922a; }
.quick-login { margin-top: 24px; text-align: center; }
.divider { display: block; font-size: 12px; color: #999; margin-bottom: 16px; position: relative; }
.divider::before, .divider::after { content: ''; position: absolute; top: 50%; width: 30%; height: 1px; background: #eee; }
.divider::before { left: 0; }
.divider::after { right: 0; }
.login-icons { display: flex; justify-content: center; gap: 24px; }
.wechat { color: #07c160; cursor: pointer; }
.alipay { color: #1677ff; cursor: pointer; }
.code-input { display: flex; gap: 10px; width: 100%; }
.code-input .el-input { flex: 1; }
.agreement-link { color: #e6a23c; text-decoration: none; }
@media (max-width: 768px) {
  .login-container { grid-template-columns: 1fr; }
  .login-left { display: none; }
}
</style>
