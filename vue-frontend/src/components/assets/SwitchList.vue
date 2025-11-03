<template>
  <div class="switch-list">
    <DataTable
      :columns="columns"
      :data="switches"
      :loading="loading"
      :actions="{ view: true, edit: true, delete: true }"
      search-placeholder="Search Switches (serial, hostname, model...)"
      @view="viewSwitch"
      @edit="editSwitch"
      @delete="confirmDelete"
    >
      <!-- Custom cell for status badge -->
      <template #cell-status="{ value }">
        <span :class="getStatusBadgeClass(value)">
          {{ ASSET_STATUS_LABELS[value] }}
        </span>
      </template>

      <!-- Custom cell for switch model -->
      <template #cell-switch_model.model="{ item }">
        <div v-if="item.switch_model" class="text-sm">
          <div class="font-medium text-gray-900">{{ item.switch_model.manufacturer }} {{ item.switch_model.model }}</div>
          <div class="text-gray-500 text-xs">{{ item.switch_model.port_count }} ports</div>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>

      <!-- Custom cell for location -->
      <template #cell-bay.rack.site.name="{ item }">
        <div v-if="item.bay?.rack?.site" class="text-sm">
          <div class="font-medium text-gray-900">{{ item.bay.rack.site.name }}</div>
          <div class="text-gray-500 text-xs">
            Rack: {{ item.bay.rack.name }} / Bay: {{ item.bay.position }}U
          </div>
        </div>
        <span v-else class="text-gray-400 text-sm">Not assigned</span>
      </template>

      <!-- Custom cell for warranty -->
      <template #cell-warranty_end="{ value }">
        <span v-if="value" :class="getWarrantyClass(value)">
          {{ formatDate(value) }}
        </span>
        <span v-else class="text-gray-400">-</span>
      </template>

      <!-- Custom actions with ports button -->
      <template #actions="{ item }">
        <button
          @click="viewPorts(item)"
          class="text-purple-600 hover:text-purple-900 mr-3"
          title="View Ports"
        >
          <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
          </svg>
        </button>
        <button
          @click="viewSwitch(item)"
          class="text-blue-600 hover:text-blue-900 mr-3"
          title="View"
        >
          <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
        <button
          @click="editSwitch(item)"
          class="text-green-600 hover:text-green-900 mr-3"
          title="Edit"
        >
          <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>
        <button
          @click="confirmDelete(item)"
          class="text-red-600 hover:text-red-900"
          title="Delete"
        >
          <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </template>
    </DataTable>

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="showDeleteModal"
      title="Delete Switch"
      confirm-text="Delete"
      confirm-variant="danger"
      @confirm="handleDelete"
    >
      <p class="text-gray-600">
        Are you sure you want to delete this switch?
      </p>
      <div v-if="selectedSwitch" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm"><strong>Serial Number:</strong> {{ selectedSwitch.serial_number }}</p>
        <p class="text-sm"><strong>Hostname:</strong> {{ selectedSwitch.hostname || 'N/A' }}</p>
        <p class="text-sm"><strong>Status:</strong> {{ ASSET_STATUS_LABELS[selectedSwitch.status] }}</p>
      </div>
      <p class="mt-4 text-sm text-red-600">
        This action cannot be undone. All port assignments will be lost.
      </p>
    </Modal>

    <!-- View Details Modal -->
    <Modal
      v-model="showViewModal"
      :title="`Switch Details - ${selectedSwitch?.hostname || selectedSwitch?.serial_number || ''}`"
      size="xl"
      hide-confirm-button
      cancel-text="Close"
    >
      <div v-if="selectedSwitch" class="space-y-4">
        <!-- General Information -->
        <div>
          <h4 class="font-semibold text-gray-900 mb-2">General Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Serial Number</p>
              <p class="text-sm font-medium">{{ selectedSwitch.serial_number }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Barcode</p>
              <p class="text-sm font-medium">{{ selectedSwitch.barcode }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Hostname</p>
              <p class="text-sm font-medium">{{ selectedSwitch.hostname || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Asset Tag</p>
              <p class="text-sm font-medium">{{ selectedSwitch.asset_tag || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Status</p>
              <span :class="getStatusBadgeClass(selectedSwitch.status)">
                {{ ASSET_STATUS_LABELS[selectedSwitch.status] }}
              </span>
            </div>
          </div>
        </div>

        <!-- Model Information -->
        <div v-if="selectedSwitch.switch_model">
          <h4 class="font-semibold text-gray-900 mb-2">Model Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Manufacturer</p>
              <p class="text-sm font-medium">{{ selectedSwitch.switch_model.manufacturer }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Model</p>
              <p class="text-sm font-medium">{{ selectedSwitch.switch_model.model }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Port Count</p>
              <p class="text-sm font-medium">{{ selectedSwitch.switch_model.port_count }} ports</p>
            </div>
            <div v-if="selectedSwitch.switch_model.description">
              <p class="text-sm text-gray-500">Description</p>
              <p class="text-sm font-medium">{{ selectedSwitch.switch_model.description }}</p>
            </div>
          </div>
        </div>

        <!-- Location Information -->
        <div v-if="selectedSwitch.bay">
          <h4 class="font-semibold text-gray-900 mb-2">Location</h4>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-gray-500">Site</p>
              <p class="text-sm font-medium">{{ selectedSwitch.bay.rack?.site?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Rack</p>
              <p class="text-sm font-medium">{{ selectedSwitch.bay.rack?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Bay Position</p>
              <p class="text-sm font-medium">{{ selectedSwitch.bay.position }}U</p>
            </div>
          </div>
        </div>

        <!-- Warranty Information -->
        <div>
          <h4 class="font-semibold text-gray-900 mb-2">Warranty & Purchase</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Purchase Date</p>
              <p class="text-sm font-medium">{{ formatDate(selectedSwitch.purchase_date) || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Warranty End</p>
              <p v-if="selectedSwitch.warranty_end" class="text-sm font-medium" :class="getWarrantyTextClass(selectedSwitch.warranty_end)">
                {{ formatDate(selectedSwitch.warranty_end) }}
              </p>
              <p v-else class="text-sm">-</p>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedSwitch.notes">
          <h4 class="font-semibold text-gray-900 mb-2">Notes</h4>
          <p class="text-sm text-gray-700 p-3 bg-gray-50 rounded-md">{{ selectedSwitch.notes }}</p>
        </div>

        <!-- Timestamps -->
        <div class="text-xs text-gray-500 pt-4 border-t">
          <p>Created: {{ formatDateTime(selectedSwitch.created_at) }}</p>
          <p>Updated: {{ formatDateTime(selectedSwitch.updated_at) }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Modal from '@/components/common/Modal.vue'
import { ASSET_STATUS_LABELS, ASSET_STATUS_COLORS } from '@/utils/constants'

const props = defineProps({
  switches: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['edit', 'delete', 'refresh', 'view-ports'])

const showDeleteModal = ref(false)
const showViewModal = ref(false)
const selectedSwitch = ref(null)

const columns = [
  {
    key: 'serial_number',
    label: 'Serial Number',
    sortable: true
  },
  {
    key: 'hostname',
    label: 'Hostname',
    sortable: true
  },
  {
    key: 'switch_model.model',
    label: 'Model',
    sortable: false
  },
  {
    key: 'bay.rack.site.name',
    label: 'Location',
    sortable: false
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true,
    type: 'badge',
    badgeColors: ASSET_STATUS_COLORS
  },
  {
    key: 'warranty_end',
    label: 'Warranty End',
    sortable: true
  }
]

const getStatusBadgeClass = (status) => {
  const baseClass = 'px-2 py-1 text-xs font-semibold rounded-full'
  const colorMap = {
    success: 'bg-green-100 text-green-800',
    info: 'bg-blue-100 text-blue-800',
    warning: 'bg-yellow-100 text-yellow-800',
    danger: 'bg-red-100 text-red-800',
    secondary: 'bg-gray-100 text-gray-800'
  }

  const color = ASSET_STATUS_COLORS[status] || 'secondary'
  return `${baseClass} ${colorMap[color]}`
}

const getWarrantyClass = (warrantyEnd) => {
  if (!warrantyEnd) return 'text-gray-400'

  const today = new Date()
  const endDate = new Date(warrantyEnd)
  const daysUntilEnd = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24))

  if (daysUntilEnd < 0) {
    return 'text-red-600 font-medium'
  } else if (daysUntilEnd <= 30) {
    return 'text-red-500 font-medium'
  } else if (daysUntilEnd <= 90) {
    return 'text-yellow-600'
  }
  return 'text-gray-900'
}

const getWarrantyTextClass = (warrantyEnd) => {
  if (!warrantyEnd) return ''

  const today = new Date()
  const endDate = new Date(warrantyEnd)
  const daysUntilEnd = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24))

  if (daysUntilEnd < 0) return 'text-red-600'
  if (daysUntilEnd <= 30) return 'text-red-500'
  if (daysUntilEnd <= 90) return 'text-yellow-600'
  return 'text-gray-900'
}

const formatDate = (dateString) => {
  if (!dateString) return null
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return null
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const viewSwitch = (switchItem) => {
  selectedSwitch.value = switchItem
  showViewModal.value = true
}

const editSwitch = (switchItem) => {
  emit('edit', switchItem)
}

const viewPorts = (switchItem) => {
  emit('view-ports', switchItem)
}

const confirmDelete = (switchItem) => {
  selectedSwitch.value = switchItem
  showDeleteModal.value = true
}

const handleDelete = () => {
  emit('delete', selectedSwitch.value.id)
  showDeleteModal.value = false
  selectedSwitch.value = null
}
</script>
