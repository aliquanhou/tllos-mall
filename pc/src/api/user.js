import request from '@/utils/request'

// 收货地址
export const getAddressList = () => request({ url: '/user/addresses', method: 'get' })
export const getAddressDetail = id => request({ url: `/user/addresses/${id}`, method: 'get' })
export const addAddress = data => request({ url: '/user/addresses', method: 'post', data })
export const updateAddress = (id, data) => request({ url: `/user/addresses/${id}`, method: 'put', data })
export const deleteAddress = id => request({ url: `/user/addresses/${id}`, method: 'delete' })
export const setDefaultAddress = id => request({ url: `/user/addresses/${id}/default`, method: 'put' })
export const getDefaultAddress = () => request({ url: '/user/addresses/default', method: 'get' })

// 我的收藏
export const getCollectList = params => request({ url: '/user/collects', method: 'get', params })
export const addCollect = data => request({ url: '/user/collects', method: 'post', data })
export const cancelCollect = data => request({ url: '/user/collects/cancel', method: 'post', data })
export const deleteCollect = id => request({ url: `/user/collects/${id}`, method: 'delete' })

// 我的优惠券
export const getCouponList = params => request({ url: '/user/coupons', method: 'get', params })
export const receiveCoupon = data => request({ url: '/user/coupons/receive', method: 'post', data })

// 消息通知
export const getMessageList = params => request({ url: '/notices', method: 'get', params })
export const getMessageDetail = id => request({ url: `/notices/${id}`, method: 'get' })
