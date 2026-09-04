import request from '@/utils/request'
export const getProductList = params => request({ url: '/products', method: 'get', params })
export const getProductDetail = id => request({ url: `/products/${id}`, method: 'get' })
export const getCategories = () => request({ url: '/products/categories', method: 'get' })
export const getHotProducts = params => request({ url: '/products/hot', method: 'get', params })
export const getNewProducts = params => request({ url: '/products/new', method: 'get', params })
