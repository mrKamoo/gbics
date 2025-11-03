<template>
  <div class="sites-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Sites</h2>
        <p class="text-sm text-gray-500 mt-1">Manage physical locations and sites</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
      >
        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Site
      </button>
    </div>

    <!-- Sites List -->
    <DataTable
      :columns="columns"
      :data="sites"
      :loading="loading"
      :actions="{ view: true, edit: true, delete: true }"
      search-placeholder="Search sites..."
      @view="viewSite"
      @edit="openEditModal"
      @delete="confirmDelete"
    >
      <!-- Custom cell for location -->
      <template #cell-location="{ item }">
        <div class="text-sm">
          <div v-if="item.city || item.country" class="font-medium text-gray-900">
            {{ [item.city, item.country].filter(Boolean).join(', ') }}
          </div>
          <div v-if="item.address" class="text-gray-500 text-xs">{{ item.address }}</div>
          <span v-if="!item.city && !item.country && !item.address" class="text-gray-400">-</span>
        </div>
      </template>

      <!-- Custom cell for contact -->
      <template #cell-contact="{ item }">
        <div v-if="item.contact_name || item.contact_phone" class="text-sm">
          <div v-if="item.contact_name" class="text-gray-900">{{ item.contact_name }}</div>
          <div v-if="item.contact_phone" class="text-gray-500 text-xs">{{ item.contact_phone }}</div>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showFormModal"
      :title="isEditing ? 'Edit Site' : 'Create Site'"
      size="lg"
      hide-footer
    >
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Site Name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="formData.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Headquarters, Data Center Paris"
          />
        </div>

        <!-- Address -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Address
          </label>
          <textarea
            v-model="formData.address"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="Street address"
          ></textarea>
        </div>

        <!-- City and Country -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              City
            </label>
            <input
              v-model="formData.city"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., Paris"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Country
            </label>
            <input
              v-model="formData.country"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., France"
            />
          </div>
        </div>

        <!-- Contact Information -->
        <div class="pt-4 border-t">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Contact Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Contact Name
              </label>
              <input
                v-model="formData.contact_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., John Doe"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Contact Phone
              </label>
              <input
                v-model="formData.contact_phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., +33 1 23 45 67 89"
              />
            </div>
          </div>
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
      :title="`Site Details - ${selectedSite?.name || ''}`"
      size="lg"
      hide-confirm-button
      cancel-text="Close"
    >
      <div v-if="selectedSite" class="space-y-4">
        <!-- Site Information -->
        <div>
          <h4 class="font-semibold text-gray-900 mb-2">Site Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Name</p>
              <p class="text-sm font-medium">{{ selectedSite.name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Location</p>
              <p class="text-sm font-medium">
                {{ [selectedSite.city, selectedSite.country].filter(Boolean).join(', ') || '-' }}
              </p>
            </div>
            <div class="col-span-2" v-if="selectedSite.address">
              <p class="text-sm text-gray-500">Address</p>
              <p class="text-sm font-medium">{{ selectedSite.address }}</p>
            </div>
          </div>
        </div>

        <!-- Contact Information -->
        <div v-if="selectedSite.contact_name || selectedSite.contact_phone">
          <h4 class="font-semibold text-gray-900 mb-2">Contact Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div v-if="selectedSite.contact_name">
              <p class="text-sm text-gray-500">Contact Name</p>
              <p class="text-sm font-medium">{{ selectedSite.contact_name }}</p>
            </div>
            <div v-if="selectedSite.contact_phone">
              <p class="text-sm text-gray-500">Contact Phone</p>
              <p class="text-sm font-medium">{{ selectedSite.contact_phone }}</p>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm text-blue-800">
            <strong>{{ rackCount }}</strong> rack(s) configured at this site
          </p>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="showDeleteModal"
      title="Delete Site"
      confirm-text="Delete"
      confirm-variant="danger"
      @confirm="handleDelete"
    >
      <p class="text-gray-600">Are you sure you want to delete this site?</p>
      <div v-if="selectedSite" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm"><strong>{{ selectedSite.name }}</strong></p>
        <p class="text-sm text-gray-600">
          {{ [selectedSite.city, selectedSite.country].filter(Boolean).join(', ') }}
        </p>
      </div>
      <p class="mt-4 text-sm text-red-600">
        All racks and bays associated with this site will also need to be reassigned.
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
const sites = ref([])
const racks = ref([])
const showFormModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const selectedSite = ref(null)
const submitting = ref(false)

const formData = reactive({
  name: '',
  address: '',
  city: '',
  country: '',
  contact_name: '',
  contact_phone: ''
})

const columns = [
  { key: 'name', label: 'Site Name', sortable: true },
  { key: 'location', label: 'Location', sortable: false },
  { key: 'contact', label: 'Contact', sortable: false },
  { key: 'created_at', label: 'Created', sortable: true, type: 'date' }
]

const rackCount = computed(() => {
  if (!selectedSite.value) return 0
  return racks.value.filter(r => r.site_id === selectedSite.value.id).length
})

const loadSites = async () => {
  loading.value = true
  try {
    const response = await api.get('/sites')
    sites.value = response.data.data || response.data
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: 'Failed to load sites'
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

const openCreateModal = () => {
  Object.assign(formData, {
    name: '',
    address: '',
    city: '',
    country: '',
    contact_name: '',
    contact_phone: ''
  })
  isEditing.value = false
  showFormModal.value = true
}

const openEditModal = (site) => {
  Object.assign(formData, {
    name: site.name,
    address: site.address || '',
    city: site.city || '',
    country: site.country || '',
    contact_name: site.contact_name || '',
    contact_phone: site.contact_phone || ''
  })
  selectedSite.value = site
  isEditing.value = true
  showFormModal.value = true
}

const viewSite = async (site) => {
  selectedSite.value = site
  await loadRacks()
  showViewModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedSite.value = null
}

const handleSubmit = async () => {
  submitting.value = true
  try {
    const dataToSubmit = {
      name: formData.name,
      address: formData.address || undefined,
      city: formData.city || undefined,
      country: formData.country || undefined,
      contact_name: formData.contact_name || undefined,
      contact_phone: formData.contact_phone || undefined
    }

    if (isEditing.value) {
      await api.put(`/sites/${selectedSite.value.id}`, dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Site updated successfully'
      })
    } else {
      await api.post('/sites', dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Site created successfully'
      })
    }

    closeFormModal()
    loadSites()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to save site'
    })
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (site) => {
  selectedSite.value = site
  showDeleteModal.value = true
}

const handleDelete = async () => {
  try {
    await api.delete(`/sites/${selectedSite.value.id}`)
    emit('notification', {
      type: 'success',
      title: 'Success',
      message: 'Site deleted successfully'
    })
    showDeleteModal.value = false
    loadSites()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to delete site'
    })
  }
}

onMounted(() => {
  loadSites()
  loadRacks()
})
</script>
