import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // Computed
  const isAuthenticated = computed(() => !!token.value)
  const userRole = computed(() => user.value?.roles?.[0]?.name || null)
  const isApproved = computed(() => user.value?.is_approved || false)

  // Initialize from localStorage
  function init() {
    const savedToken = localStorage.getItem('token')
    const savedUser = localStorage.getItem('user')

    if (savedToken && savedUser) {
      token.value = savedToken
      user.value = JSON.parse(savedUser)
    }
  }

  // Login
  async function login(credentials) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/auth/login', credentials)

      token.value = response.data.access_token
      user.value = response.data.user

      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Register
  async function register(userData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/auth/register', userData)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Logout
  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  // Get current user
  async function fetchUser() {
    try {
      const response = await api.get('/auth/me')
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(user.value))
      return response.data.user
    } catch (err) {
      console.error('Fetch user error:', err)
      throw err
    }
  }

  // Update profile
  async function updateProfile(data) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put('/auth/me', data)
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(user.value))
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Update failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Check permissions
  function hasRole(role) {
    if (!user.value || !user.value.roles) return false
    // Support both formats: array of strings or array of objects
    return user.value.roles.some(r => {
      return typeof r === 'string' ? r === role : r.name === role
    })
  }

  function hasAnyRole(roles) {
    if (!user.value || !user.value.roles) return false
    // Support both formats: array of strings or array of objects
    return user.value.roles.some(r => {
      const roleName = typeof r === 'string' ? r : r.name
      return roles.includes(roleName)
    })
  }

  function hasPermission(permission) {
    if (!user.value || !user.value.permissions) return false
    return user.value.permissions.some(p => p.name === permission)
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    userRole,
    isApproved,
    init,
    login,
    register,
    logout,
    fetchUser,
    updateProfile,
    hasRole,
    hasAnyRole,
    hasPermission
  }
})
