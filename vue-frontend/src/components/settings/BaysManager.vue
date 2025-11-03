<template>
  <div class="bays-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Bays</h2>
        <p class="text-sm text-gray-500 mt-1">Manage rack bay positions (U positions)</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
      >
        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Bay
      </button>
    </div>

    <!-- Bays List -->
    <DataTable
      :columns="columns"
      :data="bays"
      :loading="loading"
      :actions="{ edit: true, delete: true }"
      search-placeholder="Search bays..."
      @edit="openEditModal"
      @delete="confirmDelete"
    >
      <!-- Custom cell for rack -->
      <template #cell-rack="{ item }">
        <div v-if="item.rack" class="text-sm">
          <div class="font-medium text-gray-900">{{ item.rack.name }}</div>
          <div class="text-gray-500 text-xs">{{ item.rack.site?.name || 'Unknown site' }}</div>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>

      <!-- Custom cell for position -->
      <template #cell-position="{ value }">
        <span class="text-sm font-medium text-blue-600">{{ value }}U</span>
      </template>

      <!-- Custom cell for name -->
      <template #cell-name="{ value }">
        <span v-if="value" class="text-sm text-gray-900">{{ value }}</span>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>

      <!-- Custom cell for status -->
      <template #cell-status="{ item }">
        <span v-if="item.switch" class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
          Occupied
        </span>
        <span v-else class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
          Available
        </span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showFormModal"
      :title="isEditing ? 'Edit Bay' : 'Create Bay'"
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
            v-model="selectedSiteId"
            @change="onSiteChange"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">-- Select a site --</option>
            <option
              v-for="site in sites"
              :key="site.id"
              :value="site.id"
            >
              {{ site.name }}
            </option>
          </select>
        </div>

        <!-- Rack Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Rack <span class="text-red-500">*</span>
          </label>
          <select
            v-model="formData.rack_id"
            required
            :disabled="!selectedSiteId"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
          >
            <option :value="null">-- Select a rack --</option>
            <option
              v-for="rack in filteredRacks"
              :key="rack.id"
              :value="rack.id"
            >
              {{ rack.name }} ({{ rack.units }}U)
            </option>
          </select>
        </div>

        <!-- Position -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Position (U) <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="formData.position"
            type="number"
            required
            min="1"
            :max="selectedRack?.units || 100"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., 1, 2, 3..."
          />
          <p class="mt-1 text-xs text-gray-500">
            Position in rack units (1U = bottom)
            <span v-if="selectedRack"> - Max: {{ selectedRack.units }}U</span>
          </p>
        </div>

        <!-- Name (Optional) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Bay Name (Optional)
          </label>
          <input
            v-model="formData.name"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Core Switch Bay, Access Layer"
          />
          <p class="mt-1 text-xs text-gray-500">
            Optional label for this bay position
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

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="showDeleteModal"
      title="Delete Bay"
      confirm-text="Delete"
      confirm-variant="danger"
      @confirm="handleDelete"
    >
      <p class="text-gray-600">Are you sure you want to delete this bay?</p>
      <div v-if="selectedBay" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm">
          <strong>Position {{ selectedBay.position }}U</strong>
          {{ selectedBay.name ? `- ${selectedBay.name}` : '' }}
        </p>
        <p class="text-sm text-gray-600">
          {{ selectedBay.rack?.name }} @ {{ selectedBay.rack?.site?.name }}
        </p>
      </div>
      <p class="mt-4 text-sm text-red-600">
        Any switches in this bay will need to be reassigned.
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
const bays = ref([])
const racks = ref([])
const sites = ref([])
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const selectedBay = ref(null)
const selectedSiteId = ref(null)
const submitting = ref(false)

const formData = reactive({
  rack_id: null,
  position: null,
  name: ''
})

const columns = [
  { key: 'rack', label: 'Rack', sortable: false },
  { key: 'position', label: 'Position', sortable: true },
  { key: 'name', label: 'Bay Name', sortable: true },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'created_at', label: 'Created', sortable: true, type: 'date' }
]

const filteredRacks = computed(() => {
  if (!selectedSiteId.value) return []
  return racks.value.filter(rack => rack.site_id === selectedSiteId.value)
})

const selectedRack = computed(() => {
  if (!formData.rack_id) return null
  return racks.value.find(r => r.id === formData.rack_id)
})

const onSiteChange = () => {
  formData.rack_id = null
}

const loadBays = async () => {
  loading.value = true
  try {
    const response = await api.get('/bays')
    bays.value = response.data.data || response.data
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: 'Failed to load bays'
    })
  } finally {
    loading.value = false
  }
}

const loadRacks = async () => {
  try {
    const response = await api.get('/racks')
    racks.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load racks:', error)
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

const openCreateModal = () => {
  Object.assign(formData, {
    rack_id: null,
    position: null,
    name: ''
  })
  selectedSiteId.value = null
  isEditing.value = false
  showFormModal.value = true
}

const openEditModal = (bay) => {
  Object.assign(formData, {
    rack_id: bay.rack_id,
    position: bay.position,
    name: bay.name || ''
  })

  // Set the site for the cascade
  if (bay.rack && bay.rack.site_id) {
    selectedSiteId.value = bay.rack.site_id
  }

  selectedBay.value = bay
  isEditing.value = true
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedBay.value = null
  selectedSiteId.value = null
}

const handleSubmit = async () => {
  submitting.value = true
  try {
    const dataToSubmit = {
      rack_id: formData.rack_id,
      position: formData.position,
      name: formData.name || undefined
    }

    if (isEditing.value) {
      await api.put(`/bays/${selectedBay.value.id}`, dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Bay updated successfully'
      })
    } else {
      await api.post('/bays', dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Bay created successfully'
      })
    }

    closeFormModal()
    loadBays()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to save bay'
    })
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (bay) => {
  selectedBay.value = bay
  showDeleteModal.value = true
}

const handleDelete = async () => {
  try {
    await api.delete(`/bays/${selectedBay.value.id}`)
    emit('notification', {
      type: 'success',
      title: 'Success',
      message: 'Bay deleted successfully'
    })
    showDeleteModal.value = false
    loadBays()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to delete bay'
    })
  }
}

onMounted(() => {
  loadBays()
  loadRacks()
  loadSites()
})
</script>
