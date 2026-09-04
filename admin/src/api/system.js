import request from '@/utils/request'
export const getSystemConfig = () => request({ url: '/admin/system/config', method: 'get' })
export const saveSystemConfig = data => request({ url: '/admin/system/config', method: 'post', data })
export const getExpressList = params => request({ url: '/admin/system/express', method: 'get', params })
export const createExpress = data => request({ url: '/admin/system/express', method: 'post', data })
export const updateExpress = (id, data) => request({ url: `/admin/system/express/${id}`, method: 'put', data })
export const deleteExpress = id => request({ url: `/admin/system/express/${id}`, method: 'delete' })
export const getLogList = params => request({ url: '/admin/system/logs', method: 'get', params })
