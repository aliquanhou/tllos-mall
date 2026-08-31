import request from '@/utils/request'
export const getProducts = params => request({ url: '/products', method: 'get', params })
export const getProduct = id => request({ url: `/products/${id}`, method: 'get' })
export const getCategories = () => request({ url: '/categories', method: 'get' })
