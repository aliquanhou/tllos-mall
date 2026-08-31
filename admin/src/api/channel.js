import request from '@/utils/request'
export const getChannelConfig = channel => request({ url: `/admin/channel/${channel}/config`, method: 'get' })
export const saveChannelConfig = (channel, data) => request({ url: `/admin/channel/${channel}/config`, method: 'post', data })
export const getOaMenu = () => request({ url: '/admin/channel/oa/menu', method: 'get' })
export const saveOaMenu = data => request({ url: '/admin/channel/oa/menu', method: 'post', data })
export const getOaReplyList = params => request({ url: '/admin/channel/oa/reply', method: 'get', params })
export const addOaReply = data => request({ url: '/admin/channel/oa/reply', method: 'post', data })
export const updateOaReply = (id, data) => request({ url: `/admin/channel/oa/reply/${id}`, method: 'put', data })
export const deleteOaReply = id => request({ url: `/admin/channel/oa/reply/${id}`, method: 'delete' })
