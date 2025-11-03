<template>
  <div class="racks-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Racks</h2>
        <p class="text-sm text-gray-500 mt-1">Manage server racks across all sites</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
      >
        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Rack
      </button>
    </div>

    <!-- Racks List -->
    <DataTable
      :columns="columns"
      :data="racks"
      :loading="loading"
      :actions="{ view: true, edit: true, delete: true }"
      search-placeholder="Search racks..."
      @view="viewRack"
      @edit="openEditModal"
      @delete="confirmDelete"
    >
      <!-- Custom cell for site -->
      <template #cell-site.name="{ item }">
        <div v-if="item.site" class="text-sm">
          <div class="font-medium text-gray-900">{{ item.site.name }}</div>
          <div class="text-gray-500 text-xs">
            {{ [item.site.city, item.site.country].filter(Boolean).join(', ') }}
          </div>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>

      <!-- Custom cell for location -->
      <template #cell-location="{ item }">
        <span v-if="item.location" class="text-sm text-gray-900">{{ item.location }}</span>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>

      <!-- Custom cell for capacity -->
      <template #cell-units="{ value }">
        <span class="text-sm font-medium text-blue-600">{{ value }}U</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showFormModal"
      :title="isEditing ? 'Edit Rack' : 'Create Rack'"
      size="lg"
      hide-footer
    >
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Site Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Site <span class="text-red-500">*</span>
          </label>
          <select
            v-model="formData.site_id"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">-- Select a site --</option>
            <option
              v-for="site in sites"
              :key="site.id"
              :value="site.id"
            >
              {{ site.name }} ({{ [site.city, site.country].filter(Boolean).join(', ') }})
            </option>
          </select>
        </div>

        <!-- Rack Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Rack Name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="formData.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Rack-A1, Server Rack 01"
          />
        </div>

        <!-- Location -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Location/Room
          </label>
          <input
            v-model="formData.location"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Server Room A, Data Center Floor 2"
          />
          <p class="mt-1 text-xs text-gray-500">
            Specific location within the site (room, floor, etc.)
          </p>
        </div>

        <!-- Units (Capacity) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Rack Units (U) <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="formData.units"
            type="number"
            required
            min="1"
            max="100"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., 42"
          />
          <p class="mt-1 text-xs text-gray-500">
            Total rack units (typically 42U for standard racks)
          </p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t">
          <button
            type="button"
            @click="closeFormModal"
            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:bg-blue-300"
          >
            {{ submitting ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- View Details Modal -->
    <Modal
      v-model="showViewModal"
      :title="`Rack Details - ${selectedRack?.name || ''}`"
      size="lg"
      hide-confirm-button
      cancel-text="Close"
    >
      <div v-if="selectedRack" class="space-y-4">
        <!-- Rack Information -->
        <div>
          <h4 class="font-semibold text-gray-900 mb-2">Rack Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Rack Name</p>
              <p class="text-sm font-medium">{{ selectedRack.name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Capacity</p>
              <p class="text-sm font-medium text-blue-600">{{ selectedRack.units }}U</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Site</p>
              <p class="text-sm font-medium">{{ selectedRack.site?.name || '-' }}</p>
            </div>
            <div v-if="selectedRack.location">
              <p class="text-sm text-gray-500">Location</p>
              <p class="text-sm font-medium">{{ selectedRack.location }}</p>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm text-blue-800">
            <strong>{{ bayCount }}</strong> bay(s) configured in this rack
          </p>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="showDeleteModal"
      title="Delete Rack"
      confirm-text="Delete"
      confirm-variant="danger"
      @confirm="handleDelete"
    >
      <p class="text-gray-600">Are you sure you want to delete this rack?</p>
      <div v-if="selectedRack" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm"><strong>{{ selectedRack.name }}</strong></p>
        <p class="text-sm text-gray-600">{{ selectedRack.units }}U - {{ selectedRack.site?.name }}</p>
      </div>
      <p class="mt-4 text-sm text-red-600">
        All bays in this rack will also need to be reassigned.
      </p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Modal from '@/components/common/Modal.vue'
import api from '@/services/api'

const emit = defineEmits(['notification'])

const loading = ref(false)
const racks = ref([])
const sites = ref([])
const bays = ref([])
const showFormModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const selectedRack = ref(null)
const submitting = ref(false)

const formData = reactive({
  site_id: null,
  name: '',
  location: '',
  units: 42
})

const columns = [
  { key: 'name', label: 'Rack Name', sortable: true },
  { key: 'site.name', label: 'Site', sortable: false },
  { key: 'location', label: 'Location', sortable: false },
  { key: 'units', label: 'Capacity', sortable: true },
  { key: 'created_at', label: 'Created', sortable: true, type: 'date' }
]

const bayCount = computed(() => {
  if (!selectedRack.value) return 0
  return bays.value.filter(b => b.rack_id === selectedRack.value.id).length
})

const loadRacks = async () => {
  loading.value = true
  try {
    const response = await api.get('/racks')
    racks.value = response.data.data || response.data
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: 'Failed to load racks'
    })
  } finally {
    loading.value = false
  }
}

const loadSites = async () => {
  try {
    const response = await api.get('/sites')
    sites.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load sites:', error)
  }
}

const loadBays = async () => {
  try {
    const response = await api.get('/bays')
    bays.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load bays:', error)
  }
}

const openCreateModal = () => {
  Object.assign(formData, {
    site_id: null,
    name: '',
    location: '',
    units: 42
  })
  isEditing.value = false
  showFormModal.value = true
}

const openEditModal = (rack) => {
  Object.assign(formData, {
    site_id: rack.site_id,
    name: rack.name,
    location: rack.location || '',
    units: rack.units
  })
  selectedRack.value = rack
  isEditing.value = true
  showFormModal.value = true
}

const viewRack = async (rack) => {
  selectedRack.value = rack
  await loadBays()
  showViewModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedRack.value = null
}

const handleSubmit = async () => {
  submitting.value = true
  try {
    const dataToSubmit = {
      site_id: formData.site_id,
      name: formData.name,
      location: formData.location || undefined,
      units: formData.units
    }

    if (isEditing.value) {
      await api.put(`/racks/${selectedRack.value.id}`, dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Rack updated successfully'
      })
    } else {
      await api.post('/racks', dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Rack created successfully'
      })
    }

    closeFormModal()
    loadRacks()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to save rack'
    })
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (rack) => {
  selectedRack.value = rack
  showDeleteModal.value = true
}

const handleDelete = async () => {
  try {
    await api.delete(`/racks/${selectedRack.value.id}`)
    emit('notification', {
      type: 'success',
      title: 'Success',
      message: 'Rack deleted successfully'
    })
    showDeleteModal.value = false
    loadRacks()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to delete rack'
    })
  }
}

onMounted(() => {
  loadRacks()
  loadSites()
  loadBays()
})
</script>
