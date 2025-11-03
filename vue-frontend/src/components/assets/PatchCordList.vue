<template>
  <div>
    <DataTable
    :columns="columns"
    :data="patchCords"
    :loading="loading"
    :actions="{ view: true, edit: true, delete: true }"
    searchPlaceholder="Search patch cords..."
    @view="viewPatchCord"
    @edit="editPatchCord"
    @delete="deletePatchCord"
    >
      <!-- Custom cell for Barcode -->
      <template #cell-barcode="{ item }">
        <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
          {{ item.barcode }}
        </span>
      </template>

      <!-- Custom cell for Length -->
      <template #cell-length="{ item }">
        <span class="text-sm">
          {{ item.length }}m
        </span>
      </template>

      <!-- Custom cell for Connectors -->
      <template #cell-connectors="{ item }">
        <span class="text-sm">
          {{ item.connector_type_a }} → {{ item.connector_type_b }}
        </span>
      </template>

      <!-- Custom cell for Fiber Type -->
      <template #cell-fiber_type="{ item }">
        <span
          :class="[
            'px-2 py-1 text-xs rounded-full',
            item.fiber_type === 'single_mode'
              ? 'bg-yellow-100 text-yellow-800'
              : 'bg-orange-100 text-orange-800'
          ]"
        >
          {{ item.fiber_type === 'single_mode' ? 'Single Mode' : 'Multi Mode' }}
        </span>
      </template>

      <!-- Custom cell for Status -->
      <template #cell-status="{ item }">
        <span
          :class="[
            'px-2 py-1 text-xs rounded-full',
            item.status === 'in_stock' ? 'bg-blue-100 text-blue-800' :
            item.status === 'deployed' ? 'bg-green-100 text-green-800' :
            item.status === 'faulty' ? 'bg-red-100 text-red-800' :
            'bg-gray-100 text-gray-800'
          ]"
        >
          {{ statusLabels[item.status] || item.status }}
        </span>
      </template>

      <!-- Custom cell for Product -->
      <template #cell-product="{ item }">
        <div v-if="item.fs_com_product" class="text-sm">
          <div class="font-medium">{{ item.fs_com_product.name }}</div>
          <div class="text-xs text-gray-500">SKU: {{ item.fs_com_product.sku }}</div>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>
    </DataTable>

    <!-- View Details Modal -->
  <Modal
    v-model="showViewModal"
    title="Patch Cord Details"
    size="lg"
    @close="closeViewModal"
  >
      <div v-if="selectedPatchCord" class="space-y-4">
        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Barcode</label>
            <p class="mt-1 text-sm font-mono bg-gray-100 px-2 py-1 rounded">
              {{ selectedPatchCord.barcode }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Serial Number</label>
            <p class="mt-1 text-sm">
              {{ selectedPatchCord.serial_number || '-' }}
            </p>
          </div>
        </div>

        <!-- Technical Specs -->
        <div class="border-t pt-4">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Technical Specifications</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Length</label>
              <p class="mt-1 text-sm">{{ selectedPatchCord.length }}m</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Fiber Type</label>
              <p class="mt-1 text-sm">
                {{ selectedPatchCord.fiber_type === 'single_mode' ? 'Single Mode' : 'Multi Mode' }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Connector A</label>
              <p class="mt-1 text-sm">{{ selectedPatchCord.connector_type_a }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Connector B</label>
              <p class="mt-1 text-sm">{{ selectedPatchCord.connector_type_b }}</p>
            </div>
          </div>
        </div>

        <!-- FS.com Product -->
        <div v-if="selectedPatchCord.fs_com_product" class="border-t pt-4">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">FS.com Product</h4>
          <div>
            <p class="text-sm font-medium">{{ selectedPatchCord.fs_com_product.name }}</p>
            <p class="text-xs text-gray-500">SKU: {{ selectedPatchCord.fs_com_product.sku }}</p>
          </div>
        </div>

        <!-- Status & Dates -->
        <div class="border-t pt-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <p class="mt-1">
                <span
                  :class="[
                    'px-2 py-1 text-xs rounded-full',
                    selectedPatchCord.status === 'in_stock' ? 'bg-blue-100 text-blue-800' :
                    selectedPatchCord.status === 'deployed' ? 'bg-green-100 text-green-800' :
                    selectedPatchCord.status === 'faulty' ? 'bg-red-100 text-red-800' :
                    'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ statusLabels[selectedPatchCord.status] }}
                </span>
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Purchase Date</label>
              <p class="mt-1 text-sm">
                {{ selectedPatchCord.purchase_date || '-' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedPatchCord.notes" class="border-t pt-4">
          <label class="block text-sm font-medium text-gray-700">Notes</label>
          <p class="mt-1 text-sm text-gray-600">{{ selectedPatchCord.notes }}</p>
        </div>

        <!-- Metadata -->
        <div class="border-t pt-4 text-xs text-gray-500">
          <p>Created: {{ new Date(selectedPatchCord.created_at).toLocaleString() }}</p>
          <p>Updated: {{ new Date(selectedPatchCord.updated_at).toLocaleString() }}</p>
        </div>
      </div>

      <template #footer>
        <button
          @click="closeViewModal"
          class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          Close
        </button>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
<Modal
    v-model="showDeleteModal"
    title="Delete Patch Cord"
    confirmText="Delete"
    confirmVariant="danger"
    @close="closeDeleteModal"
    @confirm="confirmDelete"
  >
      <p class="text-gray-600">Are you sure you want to delete this patch cord?</p>
      <div v-if="selectedPatchCord" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm font-medium">{{ selectedPatchCord.barcode }}</p>
        <p class="text-sm text-gray-600">
          {{ selectedPatchCord.length }}m -
          {{ selectedPatchCord.connector_type_a }} → {{ selectedPatchCord.connector_type_b }}
        </p>
      </div>
      <p class="mt-4 text-sm text-red-600">
        This action cannot be undone.
      </p>
    </Modal>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Modal from '@/components/common/Modal.vue'

const props = defineProps({
  patchCords: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['edit', 'delete', 'refresh'])

const showViewModal = ref(false)
const showDeleteModal = ref(false)
const selectedPatchCord = ref(null)

const statusLabels = {
  'in_stock': 'In Stock',
  'deployed': 'Deployed',
  'faulty': 'Faulty',
  'retired': 'Retired'
}

const columns = [
  { key: 'barcode', label: 'Code', sortable: true },
  { key: 'length', label: 'Longueur', sortable: true },
  { key: 'connectors', label: 'Connecteurs', sortable: false },
  { key: 'fiber_type', label: 'Type de fibre', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'product', label: 'Produit FS.com', sortable: false },
  { key: 'purchase_date', label: 'Date Achat',type: 'date', sortable: true }
]

// View patch cord
const viewPatchCord = (patchCord) => {
  selectedPatchCord.value = patchCord
  showViewModal.value = true
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedPatchCord.value = null
}

// Edit patch cord
const editPatchCord = (patchCord) => {
  emit('edit', patchCord)
}

// Delete patch cord
const deletePatchCord = (patchCord) => {
  selectedPatchCord.value = patchCord
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedPatchCord.value = null
}

const confirmDelete = () => {
  emit('delete', selectedPatchCord.value)
  closeDeleteModal()
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
</script>