import request from '@/utils/request'
export function uploadFile(file) {
  const formData = new FormData()
  formData.append('file', file)
  return request({ url: '/upload', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
}
export function uploadImage(file) {
  const formData = new FormData()
  formData.append('file', file)
  return request({ url: '/upload/image', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
}
export function uploadVideo(file) {
  const formData = new FormData()
  formData.append('file', file)
  return request({ url: '/upload/video', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
}
