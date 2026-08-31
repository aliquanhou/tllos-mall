import request from '@/utils/request'
export const getAnnouncementList = params => request({ url: '/admin/announcement', method: 'get', params })
export const createAnnouncement = data => request({ url: '/admin/announcement', method: 'post', data })
export const updateAnnouncement = (id, data) => request({ url: `/admin/announcement/${id}`, method: 'put', data })
export const deleteAnnouncement = id => request({ url: `/admin/announcement/${id}`, method: 'delete' })
