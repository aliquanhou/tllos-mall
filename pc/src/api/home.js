import request from '@/utils/request'
export const getHomeData = () => request({ url: '/home', method: 'get' })
export const getConfig = () => request({ url: '/config', method: 'get' })
