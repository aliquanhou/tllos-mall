import request from '@/utils/request'
export function getUserList(params) { return request({ url: '/admin/users', method: 'get', params }) }
export function getUser(id) { return request({ url: `/admin/users/${id}`, method: 'get' }) }
export function updateUser(id, data) { return request({ url: `/admin/users/${id}`, method: 'put', data }) }
export function toggleUserStatus(id) { return request({ url: `/admin/users/${id}/toggle-status`, method: 'post' }) }
