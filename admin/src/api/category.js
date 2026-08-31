import request from '@/utils/request'
export const getCategoryList = params => request({ url: '/admin/categories', method: 'get', params })
export const getCategoryTree = () => request({ url: '/admin/categories/tree', method: 'get' })
export const createCategory = data => request({ url: '/admin/categories', method: 'post', data })
export const updateCategory = (id, data) => request({ url: `/admin/categories/${id}`, method: 'put', data })
export const deleteCategory = id => request({ url: `/admin/categories/${id}`, method: 'delete' })
