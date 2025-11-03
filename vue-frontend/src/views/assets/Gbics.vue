<template>
  <div class="gbics-page">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">GBICs Management</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your GBIC inventory and assignments</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="refreshData"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
        <button
          @click="openCreateModal"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add GBIC
        </button>
      </div>
    </div>

    <!-- Alert Messages -->
    <Alert
      v-model="showAlert"
      :type="alertType"
      :title="alertTitle"
      :message="alertMessage"
      :timeout="5000"
    />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total GBICs</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">In Stock</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.in_stock }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Assigned</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.assigned }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Faulty</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.faulty }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- GBICs List -->
    <div class="bg-white rounded-lg shadow">
      <GbicList
        :gbics="assetsStore.gbics"
        :loading="assetsStore.loading"
        @edit="openEditModal"
        @delete="handleDelete"
        @refresh="refreshData"
      />
    </div>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showFormModal"
      :title="isEditing ? 'Edit GBIC' : 'Create New GBIC'"
      size="lg"
      hide-footer
    >
      <GbicForm
        :gbic="selectedGbic"
        :is-edit="isEditing"
        @submit="handleSubmit"
        @cancel="closeFormModal"
      />
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAssetsStore } from '@/stores/assets'
import GbicList from '@/components/assets/GbicList.vue'
import GbicForm from '@/components/assets/GbicForm.vue'
import Modal from '@/components/common/Modal.vue'
import Alert from '@/components/common/Alert.vue'

const assetsStore = useAssetsStore()

// State
const showFormModal = ref(false)
const showAlert = ref(false)
const alertType = ref('success')
const alertTitle = ref('')
const alertMessage = ref('')
const selectedGbic = ref(null)
const isEditing = ref(false)

// Computed statistics
const stats = computed(() => {
  const gbics = assetsStore.gbics
  return {
    total: gbics.length,
    in_stock: gbics.filter(g => g.status === 'in_stock').length,
    assigned: gbics.filter(g => g.status === 'assigned').length,
    faulty: gbics.filter(g => g.status === 'faulty').length
  }
})

// Load GBICs data
const loadGbics = async () => {
  try {
    await assetsStore.fetchGbics()
  } catch (error) {
    showNotification('error', 'Error', 'Failed to load GBICs')
  }
}

// Refresh data
const refreshData = () => {
  loadGbics()
}

// Open create modal
const openCreateModal = () => {
  selectedGbic.value = null
  isEditing.value = false
  showFormModal.value = true
}

// Open edit modal
const openEditModal = (gbic) => {
  selectedGbic.value = { ...gbic }
  isEditing.value = true
  showFormModal.value = true
}

// Close form modal
const closeFormModal = () => {
  showFormModal.value = false
  selectedGbic.value = null
  isEditing.value = false
}

// Handle form submission
const handleSubmit = async (formData) => {
  try {
    if (isEditing.value) {
      await assetsStore.updateGbic(selectedGbic.value.id, formData)
      showNotification('success', 'Success', 'GBIC updated successfully')
    } else {
      await assetsStore.createGbic(formData)
      showNotification('success', 'Success', 'GBIC created successfully')
    }
    closeFormModal()
    refreshData()
  } catch (error) {
    const message = error.response?.data?.message || 'An error occurred while saving'
    showNotification('error', 'Error', message)
  }
}

// Handle delete
const handleDelete = async (id) => {
  try {
    await assetsStore.deleteGbic(id)
    showNotification('success', 'Success', 'GBIC deleted successfully')
    refreshData()
  } catch (error) {
    const message = error.response?.data?.message || 'Failed to delete GBIC'
    showNotification('error', 'Error', message)
  }
}

// Show notification
const showNotification = (type, title, message) => {
  alertType.value = type
  alertTitle.value = title
  alertMessage.value = message
  showAlert.value = true
}

// Initialize
onMounted(() => {
  loadGbics()
})
</script>

<style scoped>
.gbics-page {
  margin: 0 auto;
  padding: 1.5rem;
}
</style>
