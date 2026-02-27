// resources/js/stores/auth.js
import { defineStore } from 'pinia'
import axios from 'axios'
import { initEcho, destroyEcho } from '@/echo'  // ✅ importamos las funciones

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    isLoading: false,
    error: null,
  }),

  getters: {
    isAuthenticated:  (state) => !!state.token,
    userName:         (state) => state.user?.name || 'Invitado',
    userEmail:        (state) => state.user?.email || '',
    userRoles:        (state) => state.user?.roles || [],
    isAdmin:          (state) => state.user?.roles?.some(role => role.name === 'admin') ?? false,
    isCaseManager:    (state) => state.user?.roles?.some(role => role.name === 'casemanager') ?? false,
    isClient:         (state) => state.user?.roles?.some(role => role.name === 'client') ?? false,
  },

  actions: {
    initAuth() {
      const token = localStorage.getItem('token')
      const userStr = localStorage.getItem('user')

      if (token) {
        this.token = token
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        initEcho(token)  // ✅ restaurar Echo al recargar la página
      }

      if (userStr) {
        try {
          this.user = JSON.parse(userStr)
        } catch (e) {
          console.error('Error al parsear usuario de localStorage:', e)
          this.logout()
        }
      }
    },

    async register(userData) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.post('/register', userData)
        const { token, user } = response.data

        this.token = token
        this.user = user

        localStorage.setItem('token', token)
        localStorage.setItem('user', JSON.stringify(user))

        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        // ✅ No inicializamos Echo aquí porque el que se registra no es admin

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Error al registrar usuario'
        throw error.response?.data || error.message
      } finally {
        this.isLoading = false
      }
    },

    async login(credentials) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.post('/login', credentials)
        const { token, user } = response.data

        this.token = token
        this.user = user

        localStorage.setItem('token', token)
        localStorage.setItem('user', JSON.stringify(user))

        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        initEcho(token)  // ✅ inicializar Echo con el token listo

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Credenciales inválidas o error en el servidor'
        throw error.response?.data || error.message
      } finally {
        this.isLoading = false
      }
    },

    logout() {
      destroyEcho()  // ✅ desconectar WebSocket limpiamente

      this.token = null
      this.user = null
      this.error = null

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      delete axios.defaults.headers.common['Authorization']
    },

    async updateUser() {
      try {
        const response = await axios.get('/profile')
        this.user = response.data.user
        localStorage.setItem('user', JSON.stringify(this.user))
      } catch (err) {
        console.error('Error al actualizar usuario:', err)
      }
    }
  }
})