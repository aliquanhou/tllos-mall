import axios from 'axios'

const request = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || '/api/v1',
  timeout: 30000
})

request.interceptors.request.use(config => {
  const token = localStorage.getItem('tllos_user_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

request.interceptors.response.use(
  response => {
    const res = response.data
    if (res.code !== 200 && res.code !== 0) {
      if (res.code === 401) {
        localStorage.removeItem('tllos_user_token')
        window.location.href = '/h5/login'
      }
      return Promise.reject(new Error(res.message || 'Error'))
    }
    return res
  },
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('tllos_user_token')
      window.location.href = '/h5/login'
    }
    return Promise.reject(error)
  }
)

export default request
