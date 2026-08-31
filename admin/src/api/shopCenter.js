import request from '@/utils/request'
export const getShopCategoryList = () => request({ url: '/admin/shop-center/categories', method: 'get' })
export const createShopCategory = data => request({ url: '/admin/shop-center/categories', method: 'post', data })
export const updateShopCategory = (id, data) => request({ url: `/admin/shop-center/categories/${id}`, method: 'put', data })
export const deleteShopCategory = id => request({ url: `/admin/shop-center/categories/${id}`, method: 'delete' })
export const getShopBankList = shopId => request({ url: `/admin/shop-center/banks/${shopId}`, method: 'get' })
export const getShopAccountLogList = params => request({ url: '/admin/shop-center/account-logs', method: 'get', params })
