import request from '@/utils/request'
export const getCouponList = params => request({ url: '/admin/coupons', method: 'get', params })
export const getCouponDetail = id => request({ url: `/admin/coupons/${id}`, method: 'get' })
export const createCoupon = data => request({ url: '/admin/coupons', method: 'post', data })
export const updateCoupon = (id, data) => request({ url: `/admin/coupons/${id}`, method: 'put', data })
export const toggleCouponStatus = id => request({ url: `/admin/coupons/${id}/toggle-status`, method: 'post' })
export const deleteCoupon = id => request({ url: `/admin/coupons/${id}`, method: 'delete' })
export const getCouponRecords = params => request({ url: '/admin/coupons/records', method: 'get', params })
