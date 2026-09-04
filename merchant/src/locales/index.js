import { createI18n } from 'vue-i18n'
import zh from './zh'
import en from './en'
export default createI18n({ legacy: false, locale: localStorage.getItem('tllos_locale') || 'zh', fallbackLocale: 'en', messages: { zh, en } })
