<template>
  <div class="switches-page">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Gestion des Switchs</h1>
        <p class="text-sm text-gray-500 mt-1">Gestion des Switchs et des ports</p>
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
          Add Switch
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Nombre de Switchs</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Deployés</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.deployed }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">En Stock</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.in_stock }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">En Maintenance</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.maintenance }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Switches List -->
    <div class="bg-white rounded-lg shadow">
      <SwitchList
        :switches="assetsStore.switches"
        :loading="assetsStore.loading"
        @edit="openEditModal"
        @delete="handleDelete"
        @refresh="refreshData"
        @view-ports="openPortsModal"
      />
    </div>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showFormModal"
      :title="isEditing ? 'Edit Switch' : 'Create New Switch'"
      size="xl"
      hide-footer
    >
      <SwitchForm
        :switch-item="selectedSwitch"
        :is-edit="isEditing"
        @submit="handleSubmit"
        @cancel="closeFormModal"
      />
    </Modal>

    <!-- Port View Modal -->
    <Modal
      v-model="showPortsModal"
      :title="`Switch Ports - ${selectedSwitch?.hostname || selectedSwitch?.serial_number || ''}`"
      size="2xl"
      hide-footer
      cancel-text="Close"
    >
      <SwitchPortView
        v-if="selectedSwitch"
        :switch-item="selectedSwitch"
        @assign="handlePortAssign"
        @unassign="handlePortUnassign"
        @view-assignment="handleViewAssignment"
      />
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAssetsStore } from '@/stores/assets'
import SwitchList from '@/components/assets/SwitchList.vue'
import SwitchForm from '@/components/assets/SwitchForm.vue'
import SwitchPortView from '@/components/assets/SwitchPortView.vue'
import Modal from '@/components/common/Modal.vue'
import Alert from '@/components/common/Alert.vue'

const assetsStore = useAssetsStore()

// State
const showFormModal = ref(false)
const showPortsModal = ref(false)
const showAlert = ref(false)
const alertType = ref('success')
const alertTitle = ref('')
const alertMessage = ref('')
const selectedSwitch = ref(null)
const isEditing = ref(false)

// Computed statistics
const stats = computed(() => {
  const switches = assetsStore.switches
  return {
    total: switches.length,
    deployed: switches.filter(s => s.status === 'deployed').length,
    in_stock: switches.filter(s => s.status === 'in_stock').length,
    maintenance: switches.filter(s => s.status === 'maintenance').length
  }
})

// Load Switches data
const loadSwitches = async () => {
  try {
    await assetsStore.fetchSwitches()
  } catch (error) {
    showNotification('error', 'Error', 'Failed to load switches')
  }
}

// Refresh data
const refreshData = () => {
  loadSwitches()
}

// Open create modal
const openCreateModal = () => {
  selectedSwitch.value = null
  isEditing.value = false
  showFormModal.value = true
}

// Open edit modal
const openEditModal = (switchItem) => {
  selectedSwitch.value = { ...switchItem }
  isEditing.value = true
  showFormModal.value = true
}

// Open ports modal
const openPortsModal = (switchItem) => {
  selectedSwitch.value = switchItem
  showPortsModal.value = true
}

// Close form modal
const closeFormModal = () => {
  showFormModal.value = false
  selectedSwitch.value = null
  isEditing.value = false
}

// Handle form submission
const handleSubmit = async (formData) => {
  try {
    if (isEditing.value) {
      await assetsStore.updateSwitch(selectedSwitch.value.id, formData)
      showNotification('success', 'Success', 'Switch updated successfully')
    } else {
      await assetsStore.createSwitch(formData)
      showNotification('success', 'Success', 'Switch created successfully')
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
    await assetsStore.deleteSwitch(id)
    showNotification('success', 'Success', 'Switch deleted successfully')
    refreshData()
  } catch (error) {
    const message = error.response?.data?.message || 'Failed to delete switch'
    showNotification('error', 'Error', message)
  }
}

// Handle port assignment
const handlePortAssign = (data) => {
  // TODO: Open assignment modal or navigate to assignment page
  showNotification('info', 'Assignment', 'Port assignment feature coming soon')
  console.log('Assign to port:', data)
}

// Handle port unassignment
const handlePortUnassign = async (data) => {
  // TODO: Implement unassignment logic
  showNotification('info', 'Unassignment', 'Port unassignment feature coming soon')
  console.log('Unassign port:', data)
}

// Handle view assignment
const handleViewAssignment = (assignment) => {
  // TODO: Show assignment details
  showNotification('info', 'Assignment', 'View assignment details coming soon')
  console.log('View assignment:', assignment)
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
  loadSwitches()
})
</script>

<style scoped>
.switches-page {
  margin: 0 auto;
  padding: 1.5rem;
}
</style>
