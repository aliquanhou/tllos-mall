import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('tllos_admin_token') || '')
  const userInfo = ref(JSON.parse(localStorage.getItem('tllos_admin_user') || 'null'))
  const isLoggedIn = computed(() => !!token.value)
  const setToken = val => { token.value = val; localStorage.setItem('tllos_admin_token', val) }
  const setUserInfo = val => { userInfo.value = val; localStorage.setItem('tllos_admin_user', JSON.stringify(val)) }
  const logout = () => { token.value = ''; userInfo.value = null; localStorage.removeItem('tllos_admin_token'); localStorage.removeItem('tllos_admin_user') }
  return { token, userInfo, isLoggedIn, setToken, setUserInfo, logout }
})
