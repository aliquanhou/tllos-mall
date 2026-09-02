import request from '@/utils/request'
export const getOrderList = params => request({ url: '/orders', method: 'get', params })
export const getOrderDetail = id => request({ url: `/orders/${id}`, method: 'get' })
export const createOrder = data => request({ url: '/orders', method: 'post', data })
export const previewOrder = data => request({ url: '/orders/preview', method: 'post', data })
export const cancelOrder = id => request({ url: `/orders/${id}/cancel`, method: 'post' })
export const confirmOrder = id => request({ url: `/orders/${id}/confirm`, method: 'post' })
export const payOrder = data => request({ url: '/payment/pay', method: 'post', data })
