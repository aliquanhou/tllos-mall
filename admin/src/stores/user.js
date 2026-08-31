import { defineStore } from 'pinia'
import { login, getProfile, logout } from '@/api/auth'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: localStorage.getItem('tllos_admin_token') || '',
    userInfo: JSON.parse(localStorage.getItem('tllos_admin_user') || 'null')
  }),
  actions: {
    async login(credentials) {
      const res = await login(credentials)
      this.token = res.data.token
      this.userInfo = res.data.admin
      localStorage.setItem('tllos_admin_token', res.data.token)
      localStorage.setItem('tllos_admin_user', JSON.stringify(res.data.admin))
      return res
    },
    async fetchProfile() {
      const res = await getProfile()
      this.userInfo = res.data
      localStorage.setItem('tllos_admin_user', JSON.stringify(res.data))
      return res
    },
    async logout() {
      try { await logout() } catch (e) {}
      this.token = ''
      this.userInfo = null
      localStorage.removeItem('tllos_admin_token')
      localStorage.removeItem('tllos_admin_user')
    }
  }
})
