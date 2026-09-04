import { defineStore } from 'pinia'
export const useAppStore = defineStore('app', {
  state: () => ({ sidebarCollapsed: false, locale: localStorage.getItem('tllos_locale') || 'zh' }),
  actions: {
    toggleSidebar() { this.sidebarCollapsed = !this.sidebarCollapsed },
    setLocale(lang) { this.locale = lang; localStorage.setItem('tllos_locale', lang) }
  }
})
