import request from '@/utils/request'
export const getPaySceneList = () => request({ url: '/admin/pay-scene', method: 'get' })
export const createPayScene = data => request({ url: '/admin/pay-scene', method: 'post', data })
export const updatePayScene = (id, data) => request({ url: `/admin/pay-scene/${id}`, method: 'put', data })
export const deletePayScene = id => request({ url: `/admin/pay-scene/${id}`, method: 'delete' })
