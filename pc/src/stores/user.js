import { defineStore } from 'pinia'
import { login, getProfile, logout } from '@/api/auth'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: localStorage.getItem('tllos_pc_token') || '',
    userInfo: JSON.parse(localStorage.getItem('tllos_pc_user') || 'null')
  }),
  getters: {
    isLogin: state => !!state.token
  },
  actions: {
    async login(credentials) {
      const res = await login(credentials)
      this.token = res.data.token
      this.userInfo = res.data.user
      localStorage.setItem('tllos_pc_token', res.data.token)
      localStorage.setItem('tllos_pc_user', JSON.stringify(res.data.user))
      return res
    },
    async fetchProfile() {
      const res = await getProfile()
      this.userInfo = res.data
      localStorage.setItem('tllos_pc_user', JSON.stringify(res.data))
    },
    async logout() {
      try { await logout() } catch (e) {}
      this.token = ''
      this.userInfo = null
      localStorage.removeItem('tllos_pc_token')
      localStorage.removeItem('tllos_pc_user')
    }
  }
})
