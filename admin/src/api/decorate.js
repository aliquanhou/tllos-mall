import request from '@/utils/request'
export const getPageList = () => request({ url: '/admin/decorate/pages', method: 'get' })
export const savePage = (id, data) => request({ url: `/admin/decorate/pages/${id}`, method: 'put', data })
export const getTabbarList = () => request({ url: '/admin/decorate/tabbars', method: 'get' })
export const createTabbar = data => request({ url: '/admin/decorate/tabbars', method: 'post', data })
export const updateTabbar = (id, data) => request({ url: `/admin/decorate/tabbars/${id}`, method: 'put', data })
export const deleteTabbar = id => request({ url: `/admin/decorate/tabbars/${id}`, method: 'delete' })
export const getCategoryAdList = () => request({ url: '/admin/decorate/category-ads', method: 'get' })
