import request from '@/utils/request'
export const payOrder = data => request({ url: '/payment/pay', method: 'post', data })
export const getPaymentStatus = orderId => request({ url: `/payment/status/${orderId}`, method: 'get' })
