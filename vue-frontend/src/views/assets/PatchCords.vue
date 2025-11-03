<template>
  <div class="patch-cords-page">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Patch Cords Management</h1>
        <p class="text-sm text-gray-500 mt-1">Manage fiber optic patch cords inventory</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="loadPatchCords"
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
          Add Patch Cord
        </button>
      </div>
    </div>

    <!-- Alert Messages -->
    <Alert
      v-if="showAlert"
      :type="alertType"
      :title="alertTitle"
      :message="alertMessage"
      :timeout="5000"
      @close="showAlert = false"
    />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Patch Cords</p>
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Deployed</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.deployed }}</p>
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

    <!-- Patch Cords List -->
    <div class="bg-white rounded-lg shadow">
      <PatchCordList
        :patchCords="patchCords"
        :loading="loading"
        @edit="openEditModal"
        @delete="handleDelete"
        @refresh="loadPatchCords"
      />
    </div>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showFormModal"
      :title="editingPatchCord ? 'Edit Patch Cord' : 'Create Patch Cord'"
      size="xl"
      :hide-footer="true"
    >
      <template #default>
        <PatchCordForm
          :patchCord="editingPatchCord"
          :loading="submitting"
          @submit="handleSubmit"
          @cancel="closeFormModal"
        />
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PatchCordList from '@/components/assets/PatchCordList.vue'
import PatchCordForm from '@/components/assets/PatchCordForm.vue'
import Modal from '@/components/common/Modal.vue'
import Alert from '@/components/common/Alert.vue'
import api from '@/services/api'

const loading = ref(false)
const submitting = ref(false)
const patchCords = ref([])
const showFormModal = ref(false)
const editingPatchCord = ref(null)
const showAlert = ref(false)
const alertType = ref('success')
const alertTitle = ref('')
const alertMessage = ref('')

// Computed stats
const stats = computed(() => {
  return {
    total: patchCords.value.length,
    in_stock: patchCords.value.filter(p => p.status === 'in_stock').length,
    deployed: patchCords.value.filter(p => p.status === 'deployed').length,
    faulty: patchCords.value.filter(p => p.status === 'faulty').length
  }
})

// Load patch cords
const loadPatchCords = async () => {
  console.log('loadPatchCords called')
  loading.value = true
  try {
    console.log('Fetching patch cords...')
    const response = await api.get('/patch-cords')
    console.log('Response received:', response.data)
    patchCords.value = response.data.data || response.data
    console.log('PatchCords loaded:', patchCords.value)
  } catch (error) {
    console.error('Error loading patch cords:', error)
    showNotification('error', 'Error', 'Failed to load patch cords')
  } finally {
    loading.value = false
    console.log('loadPatchCords finished')
  }
}

// Open create modal
const openCreateModal = () => {
  editingPatchCord.value = null
  showFormModal.value = true
}

// Open edit modal
const openEditModal = (patchCord) => {
  editingPatchCord.value = patchCord
  showFormModal.value = true
}

// Close form modal
const closeFormModal = () => {
  showFormModal.value = false
  editingPatchCord.value = null
}

// Handle form submit
const handleSubmit = async (formData) => {
  submitting.value = true
  try {
    if (editingPatchCord.value) {
      // Update existing patch cord
      await api.put(`/patch-cords/${editingPatchCord.value.id}`, formData)
      showNotification('success', 'Success', 'Patch cord updated successfully')
    } else {
      // Create new patch cord
      await api.post('/patch-cords', formData)
      showNotification('success', 'Success', 'Patch cord created successfully')
    }
    await loadPatchCords()
    closeFormModal()
  } catch (error) {
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to save patch cord')
  } finally {
    submitting.value = false
  }
}

// Handle delete
const handleDelete = async (patchCord) => {
  try {
    await api.delete(`/patch-cords/${patchCord.id}`)
    showNotification('success', 'Success', 'Patch cord deleted successfully')
    loadPatchCords()
  } catch (error) {
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to delete patch cord')
  }
}

// Show notification
const showNotification = (type, title, message) => {
  alertType.value = type
  alertTitle.value = title
  alertMessage.value = message
  showAlert.value = true
}

onMounted(() => {
  console.log('PatchCords component mounted')
  loadPatchCords()
})
</script>
<style scoped>
.patch-cords-page {
  margin: 0 auto;
  padding: 1.5rem;
}
</style>