import axios from 'axios'
import router from '@/router'

const request = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || '/api/v1',
  timeout: 30000
})

request.interceptors.request.use(config => {
  const token = localStorage.getItem('tllos_h5_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

request.interceptors.response.use(
  response => {
    const res = response.data
    if (res.code !== 200 && res.code !== 0) {
      alert(res.message || '请求失败')
      if (res.code === 401) {
        localStorage.removeItem('tllos_h5_token')
        router.push('/login')
      }
      return Promise.reject(new Error(res.message || 'Error'))
    }
    return res
  },
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('tllos_h5_token')
      router.push('/login')
    }
    alert(error.response?.data?.message || error.message || '网络错误')
    return Promise.reject(error)
  }
)

export default request
