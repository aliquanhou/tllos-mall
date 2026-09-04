import request from '@/utils/request'
export const getCart = () => request({ url: '/cart', method: 'get' })
export const addToCart = data => request({ url: '/cart', method: 'post', data })
export const updateCart = (id, data) => request({ url: `/cart/${id}`, method: 'put', data })
export const deleteCart = id => request({ url: `/cart/${id}`, method: 'delete' })
export const clearCart = () => request({ url: '/cart/clear', method: 'post' })
export const selectAllCart = data => request({ url: '/cart/select-all', method: 'post', data })
export const getCartCount = () => request({ url: '/cart/count', method: 'get' })
