import request from '@/utils/request'
export function getOrderList(params) { return request({ url: '/admin/orders', method: 'get', params }) }
export function getOrder(id) { return request({ url: `/admin/orders/${id}`, method: 'get' }) }
export function getOrderDetail(id) { return request({ url: `/admin/orders/${id}`, method: 'get' }) }
export function shipOrder(id, data) { return request({ url: `/admin/orders/${id}/ship`, method: 'post', data }) }
export function cancelOrder(id) { return request({ url: `/admin/orders/${id}/cancel`, method: 'post' }) }
export function getRefundList(params) { return request({ url: '/admin/refunds', method: 'get', params }) }
export function auditRefund(id, data) { return request({ url: `/admin/refunds/${id}/audit`, method: 'post', data }) }
