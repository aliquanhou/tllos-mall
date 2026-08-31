import request from '@/utils/request'
export const getMerchantList = params => request({ url: '/admin/merchants', method: 'get', params })
export const getMerchantDetail = id => request({ url: `/admin/merchants/${id}`, method: 'get' })
export const auditMerchant = (id, data) => request({ url: `/admin/merchants/${id}/audit`, method: 'post', data })
export const toggleMerchantStatus = id => request({ url: `/admin/merchants/${id}/toggle-status`, method: 'post' })
export const updateMerchant = (id, data) => request({ url: `/admin/merchants/${id}`, method: 'put', data })
export const deleteMerchant = id => request({ url: `/admin/merchants/${id}`, method: 'delete' })
