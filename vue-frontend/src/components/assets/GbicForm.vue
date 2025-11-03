<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Alert for errors -->
    <Alert
      v-model="showAlert"
      :type="alertType"
      :message="alertMessage"
      :timeout="5000"
    />

    <!-- FS.com Product Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        FS.com Product (Optional)
      </label>
      <select
        v-model="formData.fs_com_product_id"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
        <option :value="null">-- Select a product --</option>
        <option
          v-for="product in fsComProducts"
          :key="product.id"
          :value="product.id"
        >
          {{ product.name }} ({{ product.sku }})
        </option>
      </select>
      <p class="mt-1 text-xs text-gray-500">
        Link this GBIC to a FS.com product from the catalog
      </p>
    </div>

    <!-- Serial Number -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Serial Number <span class="text-red-500">*</span>
      </label>
      <input
        v-model="formData.serial_number"
        type="text"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        :class="{ 'border-red-500': errors.serial_number }"
        placeholder="e.g., SN123456789"
      />
      <p v-if="errors.serial_number" class="mt-1 text-sm text-red-600">
        {{ errors.serial_number }}
      </p>
    </div>

    <!-- Barcode (auto-generated if not editing) -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Barcode <span class="text-red-500">*</span>
      </label>
      <div class="flex gap-2">
        <input
          v-model="formData.barcode"
          type="text"
          required
          :readonly="isEdit"
          class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{
            'border-red-500': errors.barcode,
            'bg-gray-100': isEdit
          }"
          placeholder="Will be auto-generated"
        />
        <button
          v-if="!isEdit"
          type="button"
          @click="generateBarcode"
          class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Generate
        </button>
      </div>
      <p v-if="errors.barcode" class="mt-1 text-sm text-red-600">
        {{ errors.barcode }}
      </p>
      <p v-else class="mt-1 text-xs text-gray-500">
        {{ isEdit ? 'Barcode cannot be changed after creation' : 'Auto-generated or enter manually' }}
      </p>
    </div>

    <!-- Status -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Status <span class="text-red-500">*</span>
      </label>
      <select
        v-model="formData.status"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
        <option value="in_stock">In Stock</option>
        <option value="assigned">Assigned</option>
        <option value="faulty">Faulty</option>
        <option value="retired">Retired</option>
      </select>
    </div>

    <!-- Purchase Date -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Purchase Date
      </label>
      <input
        v-model="formData.purchase_date"
        type="date"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      />
    </div>

    <!-- Warranty End Date -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Warranty End Date
      </label>
      <input
        v-model="formData.warranty_end"
        type="date"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      />
      <p class="mt-1 text-xs text-gray-500">
        Leave empty if no warranty or unknown
      </p>
    </div>

    <!-- Notes -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Notes
      </label>
      <textarea
        v-model="formData.notes"
        rows="4"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Additional notes or comments..."
      ></textarea>
    </div>

    <!-- Form Actions -->
    <div class="flex justify-end gap-3 pt-4 border-t">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="submitting"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-blue-300 disabled:cursor-not-allowed"
      >
        <span v-if="submitting">
          <svg class="inline animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Saving...
        </span>
        <span v-else>
          {{ isEdit ? 'Update GBIC' : 'Create GBIC' }}
        </span>
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import Alert from '@/components/common/Alert.vue'
import api from '@/services/api'

const props = defineProps({
  gbic: {
    type: Object,
    default: null
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['submit', 'cancel'])

const submitting = ref(false)
const showAlert = ref(false)
const alertType = ref('error')
const alertMessage = ref('')
const fsComProducts = ref([])

const formData = reactive({
  fs_com_product_id: null,
  serial_number: '',
  barcode: '',
  status: 'in_stock',
  purchase_date: null,
  warranty_end: null,
  notes: ''
})

const errors = reactive({
  serial_number: '',
  barcode: ''
})

// Load FS.com products
const loadFsComProducts = async () => {
  try {
    const response = await api.get('/catalog/fscom/products', {
      params: { category: 'gbic', per_page: 100 }
    })
    fsComProducts.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load FS.com products:', error)
  }
}

// Generate random barcode
const generateBarcode = () => {
  const prefix = 'GBIC'
  const timestamp = Date.now().toString(36).toUpperCase()
  const random = Math.random().toString(36).substring(2, 8).toUpperCase()
  formData.barcode = `${prefix}-${timestamp}-${random}`
}

// Validate form
const validateForm = () => {
  let isValid = true
  errors.serial_number = ''
  errors.barcode = ''

  if (!formData.serial_number || formData.serial_number.trim() === '') {
    errors.serial_number = 'Serial number is required'
    isValid = false
  }

  if (!formData.barcode || formData.barcode.trim() === '') {
    errors.barcode = 'Barcode is required'
    isValid = false
  }

  return isValid
}

// Handle form submission
const handleSubmit = async () => {
  if (!validateForm()) {
    showAlert.value = true
    alertType.value = 'error'
    alertMessage.value = 'Please fix the errors in the form'
    return
  }

  submitting.value = true

  try {
    // Clean data - remove null values for optional fields
    const dataToSubmit = {
      ...formData,
      fs_com_product_id: formData.fs_com_product_id || undefined,
      purchase_date: formData.purchase_date || undefined,
      warranty_end: formData.warranty_end || undefined,
      notes: formData.notes || undefined
    }

    emit('submit', dataToSubmit)
  } catch (error) {
    showAlert.value = true
    alertType.value = 'error'
    alertMessage.value = error.response?.data?.message || 'An error occurred while saving'
  } finally {
    submitting.value = false
  }
}

// Initialize form with GBIC data if editing
watch(
  () => props.gbic,
  (newGbic) => {
    if (newGbic && props.isEdit) {
      Object.assign(formData, {
        fs_com_product_id: newGbic.fs_com_product_id || null,
        serial_number: newGbic.serial_number || '',
        barcode: newGbic.barcode || '',
        status: newGbic.status || 'in_stock',
        purchase_date: newGbic.purchase_date || null,
        warranty_end: newGbic.warranty_end || null,
        notes: newGbic.notes || ''
      })
    }
  },
  { immediate: true }
)

onMounted(() => {
  loadFsComProducts()

  // Generate barcode for new GBICs
  if (!props.isEdit && !formData.barcode) {
    generateBarcode()
  }
})
</script>
