import request from '@/utils/request'
export const getCommentList = params => request({ url: '/admin/comments', method: 'get', params })
export const getCommentDetail = id => request({ url: `/admin/comments/${id}`, method: 'get' })
export const replyComment = (id, data) => request({ url: `/admin/comments/${id}/reply`, method: 'post', data })
export const toggleCommentShow = id => request({ url: `/admin/comments/${id}/toggle-show`, method: 'post' })
export const deleteComment = id => request({ url: `/admin/comments/${id}`, method: 'delete' })
