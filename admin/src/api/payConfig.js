import request from '@/utils/request'

// 获取所有支付配置
export const getPayConfigList = () => request({ url: '/admin/pay-configs', method: 'get' })

// 创建支付配置
export const createPayConfig = data => request({ url: '/admin/pay-configs', method: 'post', data })

// 更新支付配置
export const updatePayConfig = (id, data) => request({ url: `/admin/pay-configs/${id}`, method: 'put', data })

// 删除支付配置
export const deletePayConfig = id => request({ url: `/admin/pay-configs/${id}`, method: 'delete' })
