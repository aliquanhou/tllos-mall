import request from '@/utils/request'
export const getAfterSaleList = params => request({ url: '/admin/after-sale', method: 'get', params })
export const getAfterSaleDetail = id => request({ url: `/admin/after-sale/${id}`, method: 'get' })
export const auditAfterSale = (id, data) => request({ url: `/admin/after-sale/${id}/audit`, method: 'post', data })
export const completeAfterSale = id => request({ url: `/admin/after-sale/${id}/complete`, method: 'post' })
