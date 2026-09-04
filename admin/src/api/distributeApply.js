import request from '@/utils/request'
export const getApplyList = params => request({ url: '/admin/distribute/apply', method: 'get', params })
export const auditApply = (id, data) => request({ url: `/admin/distribute/apply/${id}/audit`, method: 'post', data })
