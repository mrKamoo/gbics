<template>
  <div class="gbic-list">
    <DataTable
      :columns="columns"
      :data="gbics"
      :loading="loading"
      :actions="{ view: true, edit: true, delete: true }"
      search-placeholder="Search GBICs (serial, barcode, status...)"
      @view="viewGbic"
      @edit="editGbic"
      @delete="confirmDelete"
    >
      <!-- Custom cell for status badge -->
      <template #cell-status="{ value }">
        <span :class="getStatusBadgeClass(value)">
          {{ ASSET_STATUS_LABELS[value] }}
        </span>
      </template>

      <!-- Custom cell for FS.com product -->
      <template #cell-fs_com_product.name="{ item }">
        <div v-if="item.fs_com_product" class="text-sm">
          <div class="font-medium text-gray-900">{{ item.fs_com_product.name }}</div>
          <div class="text-gray-500 text-xs">{{ item.fs_com_product.sku }}</div>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>

      <!-- Custom cell for warranty -->
      <template #cell-warranty_end="{ value }">
        <span v-if="value" :class="getWarrantyClass(value)">
          {{ formatDate(value) }}
        </span>
        <span v-else class="text-gray-400">-</span>
      </template>
    </DataTable>

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="showDeleteModal"
      title="Delete GBIC"
      confirm-text="Delete"
      confirm-variant="danger"
      @confirm="handleDelete"
    >
      <p class="text-gray-600">
        Are you sure you want to delete this GBIC?
      </p>
      <div v-if="selectedGbic" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm"><strong>Serial Number:</strong> {{ selectedGbic.serial_number }}</p>
        <p class="text-sm"><strong>Barcode:</strong> {{ selectedGbic.barcode }}</p>
        <p class="text-sm"><strong>Status:</strong> {{ ASSET_STATUS_LABELS[selectedGbic.status] }}</p>
      </div>
      <p class="mt-4 text-sm text-red-600">
        This action cannot be undone.
      </p>
    </Modal>

    <!-- View Details Modal -->
    <Modal
      v-model="showViewModal"
      :title="`GBIC Details - ${selectedGbic?.serial_number || ''}`"
      size="lg"
      hide-confirm-button
      cancel-text="Close"
    >
      <div v-if="selectedGbic" class="space-y-4">
        <!-- General Information -->
        <div>
          <h4 class="font-semibold text-gray-900 mb-2">General Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Serial Number</p>
              <p class="text-sm font-medium">{{ selectedGbic.serial_number }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Barcode</p>
              <p class="text-sm font-medium">{{ selectedGbic.barcode }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Status</p>
              <span :class="getStatusBadgeClass(selectedGbic.status)">
                {{ ASSET_STATUS_LABELS[selectedGbic.status] }}
              </span>
            </div>
          </div>
        </div>

        <!-- FS.com Product Info -->
        <div v-if="selectedGbic.fs_com_product">
          <h4 class="font-semibold text-gray-900 mb-2">Product Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Product Name</p>
              <p class="text-sm font-medium">{{ selectedGbic.fs_com_product.name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">SKU</p>
              <p class="text-sm font-medium">{{ selectedGbic.fs_com_product.sku }}</p>
            </div>
            <div v-if="selectedGbic.fs_com_product.price" class="col-span-2">
              <p class="text-sm text-gray-500">Price</p>
              <p class="text-sm font-medium">
                {{ selectedGbic.fs_com_product.price }} {{ selectedGbic.fs_com_product.currency }}
              </p>
            </div>
          </div>
        </div>

        <!-- Warranty Information -->
        <div>
          <h4 class="font-semibold text-gray-900 mb-2">Warranty & Purchase</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Purchase Date</p>
              <p class="text-sm font-medium">{{ formatDate(selectedGbic.purchase_date) || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Warranty End</p>
              <p v-if="selectedGbic.warranty_end" class="text-sm font-medium" :class="getWarrantyTextClass(selectedGbic.warranty_end)">
                {{ formatDate(selectedGbic.warranty_end) }}
              </p>
              <p v-else class="text-sm">-</p>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedGbic.notes">
          <h4 class="font-semibold text-gray-900 mb-2">Notes</h4>
          <p class="text-sm text-gray-700 p-3 bg-gray-50 rounded-md">{{ selectedGbic.notes }}</p>
        </div>

        <!-- Timestamps -->
        <div class="text-xs text-gray-500 pt-4 border-t">
          <p>Created: {{ formatDateTime(selectedGbic.created_at) }}</p>
          <p>Updated: {{ formatDateTime(selectedGbic.updated_at) }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Modal from '@/components/common/Modal.vue'
import { ASSET_STATUS_LABELS, ASSET_STATUS_COLORS } from '@/utils/constants'

const props = defineProps({
  gbics: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['edit', 'delete', 'refresh'])

const showDeleteModal = ref(false)
const showViewModal = ref(false)
const selectedGbic = ref(null)

const columns = [
  {
    key: 'serial_number',
    label: 'Serial Number',
    sortable: true
  },
  {
    key: 'barcode',
    label: 'Barcode',
    sortable: true
  },
  {
    key: 'fs_com_product.name',
    label: 'Product',
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
    key: 'purchase_date',
    label: 'Purchase Date',
    sortable: true,
    type: 'date'
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
    return 'text-red-600 font-medium' // Expired
  } else if (daysUntilEnd <= 30) {
    return 'text-red-500 font-medium' // Expiring soon
  } else if (daysUntilEnd <= 90) {
    return 'text-yellow-600' // Warning
  }
  return 'text-gray-900' // Valid
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

const viewGbic = (gbic) => {
  selectedGbic.value = gbic
  showViewModal.value = true
}

const editGbic = (gbic) => {
  emit('edit', gbic)
}

const confirmDelete = (gbic) => {
  selectedGbic.value = gbic
  showDeleteModal.value = true
}

const handleDelete = () => {
  emit('delete', selectedGbic.value.id)
  showDeleteModal.value = false
  selectedGbic.value = null
}
</script>
