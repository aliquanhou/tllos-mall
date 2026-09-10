import request from '@/utils/request'

// 退款/售后
export const getRefundList = params => request({ url: '/refunds', method: 'get', params })
export const getRefundDetail = id => request({ url: `/refunds/${id}`, method: 'get' })
export const applyRefund = data => request({ url: '/refunds', method: 'post', data })
export const cancelRefund = id => request({ url: `/refunds/${id}/cancel`, method: 'post' })
export const getAfterSaleList = params => request({ url: '/user/after-sale', method: 'get', params })
