import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useAssetsStore = defineStore('assets', () => {
  const gbics = ref([])
  const switches = ref([])
  const patchCords = ref([])
  const loading = ref(false)
  const error = ref(null)

  // GBICs
  async function fetchGbics(params = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/gbics', { params })
      gbics.value = response.data.data || response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch GBICs'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createGbic(data) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/gbics', data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create GBIC'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateGbic(id, data) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put(`/gbics/${id}`, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update GBIC'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteGbic(id) {
    loading.value = true
    error.value = null

    try {
      const response = await api.delete(`/gbics/${id}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete GBIC'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function findGbicByBarcode(barcode) {
    try {
      const response = await api.get(`/gbics/barcode/${barcode}`)
      return response.data.gbic
    } catch (err) {
      throw err
    }
  }

  // Switches
  async function fetchSwitches(params = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/switches', { params })
      switches.value = response.data.data || response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch switches'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createSwitch(data) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/switches', data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create switch'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateSwitch(id, data) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put(`/switches/${id}`, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update switch'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteSwitch(id) {
    loading.value = true
    error.value = null

    try {
      const response = await api.delete(`/switches/${id}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete switch'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function getSwitchPorts(id) {
    try {
      const response = await api.get(`/switches/${id}/ports`)
      return response.data
    } catch (err) {
      throw err
    }
  }

  // Patch Cords
  async function fetchPatchCords(params = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/patch-cords', { params })
      patchCords.value = response.data.data || response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch patch cords'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createPatchCord(data) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/patch-cords', data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create patch cord'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updatePatchCord(id, data) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put(`/patch-cords/${id}`, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update patch cord'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deletePatchCord(id) {
    loading.value = true
    error.value = null

    try {
      const response = await api.delete(`/patch-cords/${id}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete patch cord'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    gbics,
    switches,
    patchCords,
    loading,
    error,
    fetchGbics,
    createGbic,
    updateGbic,
    deleteGbic,
    findGbicByBarcode,
    fetchSwitches,
    createSwitch,
    updateSwitch,
    deleteSwitch,
    getSwitchPorts,
    fetchPatchCords,
    createPatchCord,
    updatePatchCord,
    deletePatchCord
  }
})
