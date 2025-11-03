<template>
  <div class="switch-models-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Switch Models</h2>
        <p class="text-sm text-gray-500 mt-1">Manage switch models and their specifications</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
      >
        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Model
      </button>
    </div>

    <!-- Models List -->
    <DataTable
      :columns="columns"
      :data="models"
      :loading="loading"
      :actions="{ edit: true, delete: true }"
      search-placeholder="Search models..."
      @edit="openEditModal"
      @delete="confirmDelete"
    >
      <!-- Custom cell for port types -->
      <template #cell-port_types="{ value }">
        <div v-if="value && value.length" class="flex flex-wrap gap-1">
          <span
            v-for="(portType, index) in value"
            :key="index"
            class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded"
          >
            {{ portType.type }}: {{ portType.count }}
          </span>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showFormModal"
      :title="isEditing ? 'Edit Switch Model' : 'Create Switch Model'"
      size="lg"
      hide-footer
    >
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Manufacturer -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Manufacturer <span class="text-red-500">*</span>
          </label>
          <input
            v-model="formData.manufacturer"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Cisco, Juniper, Arista"
          />
        </div>

        <!-- Model -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Model <span class="text-red-500">*</span>
          </label>
          <input
            v-model="formData.model"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Catalyst 2960-X"
          />
        </div>

        <!-- Port Count -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Port Count <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="formData.port_count"
            type="number"
            required
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., 24"
          />
        </div>

        <!-- Port Types -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Port Types
          </label>
          <div class="space-y-2">
            <div
              v-for="(portType, index) in formData.port_types"
              :key="index"
              class="flex gap-2"
            >
              <input
                v-model="portType.type"
                type="text"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="Type (e.g., SFP+, QSFP)"
              />
              <input
                v-model.number="portType.count"
                type="number"
                min="1"
                class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="Count"
              />
              <button
                type="button"
                @click="removePortType(index)"
                class="px-3 py-2 text-red-600 hover:text-red-800"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
            <button
              type="button"
              @click="addPortType"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              + Add Port Type
            </button>
          </div>
          <p class="mt-1 text-xs text-gray-500">
            Optional: Define different port types (e.g., 24x SFP+, 4x QSFP)
          </p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description
          </label>
          <textarea
            v-model="formData.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="Additional details about this model..."
          ></textarea>
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
      title="Delete Switch Model"
      confirm-text="Delete"
      confirm-variant="danger"
      @confirm="handleDelete"
    >
      <p class="text-gray-600">Are you sure you want to delete this switch model?</p>
      <div v-if="selectedModel" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm"><strong>{{ selectedModel.manufacturer }} {{ selectedModel.model }}</strong></p>
        <p class="text-sm text-gray-600">{{ selectedModel.port_count }} ports</p>
      </div>
      <p class="mt-4 text-sm text-red-600">This action cannot be undone.</p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Modal from '@/components/common/Modal.vue'
import api from '@/services/api'

const emit = defineEmits(['notification'])

const loading = ref(false)
const models = ref([])
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const selectedModel = ref(null)
const submitting = ref(false)

const formData = reactive({
  manufacturer: '',
  model: '',
  port_count: null,
  port_types: [],
  description: ''
})

const columns = [
  { key: 'manufacturer', label: 'Manufacturer', sortable: true },
  { key: 'model', label: 'Model', sortable: true },
  { key: 'port_count', label: 'Port Count', sortable: true },
  { key: 'port_types', label: 'Port Types', sortable: false },
  { key: 'description', label: 'Description', sortable: false }
]

const loadModels = async () => {
  loading.value = true
  try {
    const response = await api.get('/switch-models')
    models.value = response.data.data || response.data
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: 'Failed to load switch models'
    })
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  Object.assign(formData, {
    manufacturer: '',
    model: '',
    port_count: null,
    port_types: [],
    description: ''
  })
  isEditing.value = false
  showFormModal.value = true
}

const openEditModal = (model) => {
  Object.assign(formData, {
    manufacturer: model.manufacturer,
    model: model.model,
    port_count: model.port_count,
    port_types: model.port_types ? JSON.parse(JSON.stringify(model.port_types)) : [],
    description: model.description || ''
  })
  selectedModel.value = model
  isEditing.value = true
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedModel.value = null
}

const addPortType = () => {
  formData.port_types.push({ type: '', count: null })
}

const removePortType = (index) => {
  formData.port_types.splice(index, 1)
}

const handleSubmit = async () => {
  submitting.value = true
  try {
    const dataToSubmit = {
      manufacturer: formData.manufacturer,
      model: formData.model,
      port_count: formData.port_count,
      port_types: formData.port_types.filter(pt => pt.type && pt.count),
      description: formData.description || undefined
    }

    if (isEditing.value) {
      await api.put(`/switch-models/${selectedModel.value.id}`, dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Switch model updated successfully'
      })
    } else {
      await api.post('/switch-models', dataToSubmit)
      emit('notification', {
        type: 'success',
        title: 'Success',
        message: 'Switch model created successfully'
      })
    }

    closeFormModal()
    loadModels()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to save switch model'
    })
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (model) => {
  selectedModel.value = model
  showDeleteModal.value = true
}

const handleDelete = async () => {
  try {
    await api.delete(`/switch-models/${selectedModel.value.id}`)
    emit('notification', {
      type: 'success',
      title: 'Success',
      message: 'Switch model deleted successfully'
    })
    showDeleteModal.value = false
    loadModels()
  } catch (error) {
    emit('notification', {
      type: 'error',
      title: 'Error',
      message: error.response?.data?.message || 'Failed to delete switch model'
    })
  }
}

onMounted(() => {
  loadModels()
})
</script>
