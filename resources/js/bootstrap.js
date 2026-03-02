// resources/js/bootstrap.js
import axios from 'axios'

// ─── AXIOS ───────────────────────────────────────────────
window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common['Accept'] = 'application/json'
window.axios.defaults.baseURL = '/api'

window.axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const auth = window.useAuthStore?.()
      if (auth) {
        auth.logout()
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

// bootstrap.js - agregar esto
window.axios.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
  }
  return config
})