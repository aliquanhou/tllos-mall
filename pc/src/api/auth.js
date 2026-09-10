import request from '@/utils/request'
export const login = data => request({ url: '/auth/login', method: 'post', data })
export const register = data => request({ url: '/auth/register', method: 'post', data })
export const sendSmsCode = data => request({ url: '/auth/sms-code', method: 'post', data })
export const getProfile = () => request({ url: '/auth/profile', method: 'get' })
export const updateProfile = data => request({ url: '/auth/profile', method: 'put', data })
export const logout = () => request({ url: '/auth/logout', method: 'post' })
