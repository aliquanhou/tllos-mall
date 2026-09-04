import request from '@/utils/request'
export const getAdminList = params => request({ url: '/admin/admin-manage', method: 'get', params })
export const createAdmin = data => request({ url: '/admin/admin-manage', method: 'post', data })
export const updateAdmin = (id, data) => request({ url: `/admin/admin-manage/${id}`, method: 'put', data })
export const deleteAdmin = id => request({ url: `/admin/admin-manage/${id}`, method: 'delete' })
export const getJobList = () => request({ url: '/admin/jobs', method: 'get' })
export const createJob = data => request({ url: '/admin/jobs', method: 'post', data })
export const updateJob = (id, data) => request({ url: `/admin/jobs/${id}`, method: 'put', data })
export const deleteJob = id => request({ url: `/admin/jobs/${id}`, method: 'delete' })
