import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as apiLogin, getProfile, logout as apiLogout } from '@/api/auth'

export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('tllos_admin_token') || '')
  const userInfo = ref(JSON.parse(localStorage.getItem('tllos_admin_user') || 'null'))
  const isLoggedIn = computed(() => !!token.value)

  const setToken = val => {
    token.value = val
    localStorage.setItem('tllos_admin_token', val)
  }

  const setUserInfo = val => {
    userInfo.value = val
    localStorage.setItem('tllos_admin_user', JSON.stringify(val))
  }

  const login = async (form) => {
    const res = await apiLogin(form)
    if (res.code === 200 || res.code === 0) {
      const data = res.data || {}
      if (data.token) setToken(data.token)
      if (data.admin) setUserInfo(data.admin)
      // 尝试获取完整profile
      try {
        const profileRes = await getProfile()
        if (profileRes.data) setUserInfo(profileRes.data)
      } catch (e) { /* ignore */ }
      return res
    }
    throw new Error(res.message || '登录失败')
  }

  const logout = async () => {
    try { await apiLogout() } catch (e) { /* ignore */ }
    token.value = ''
    userInfo.value = null
    localStorage.removeItem('tllos_admin_token')
    localStorage.removeItem('tllos_admin_user')
  }

  const fetchProfile = async () => {
    try {
      const res = await getProfile()
      if (res.data) setUserInfo(res.data)
      return res
    } catch (e) {
      throw e
    }
  }

  return { token, userInfo, isLoggedIn, setToken, setUserInfo, login, logout, fetchProfile }
})
