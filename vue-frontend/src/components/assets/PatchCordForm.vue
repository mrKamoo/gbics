<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Barcode -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Barcode <span class="text-red-500">*</span>
      </label>
      <div class="flex gap-2">
        <input
          v-model="form.barcode"
          type="text"
          required
          class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          placeholder="PC-XXXXX"
        />
        <button
          type="button"
          @click="generateBarcode"
          class="px-4 py-2 text-sm text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100"
        >
          Auto-generate
        </button>
      </div>
      <p class="mt-1 text-xs text-gray-500">
        Unique identifier for the patch cord
      </p>
    </div>

    <!-- Serial Number -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Serial Number
      </label>
      <input
        v-model="form.serial_number"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
        placeholder="Optional manufacturer serial number"
      />
    </div>

    <!-- FS.com Product -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        FS.com Product
      </label>
      <select
        v-model="form.fs_com_product_id"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
      >
        <option value="">-- Select a product (optional) --</option>
        <option
          v-for="product in fsComProducts"
          :key="product.id"
          :value="product.id"
        >
          {{ product.name }} - {{ product.sku }}
        </option>
      </select>
      <p class="mt-1 text-xs text-gray-500">
        Link to FS.com catalog product
      </p>
    </div>

    <!-- Technical Specifications -->
    <div class="border-t pt-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Technical Specifications</h3>

      <!-- Length -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Length (meters) <span class="text-red-500">*</span>
        </label>
        <input
          v-model.number="form.length"
          type="number"
          step="0.1"
          min="0.1"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          placeholder="e.g., 3.0"
        />
      </div>

      <!-- Fiber Type -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Fiber Type <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.fiber_type"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
        >
          <option value="">-- Select fiber type --</option>
          <option value="single_mode">Single Mode (OS2)</option>
          <option value="multi_mode">Multi Mode (OM3/OM4)</option>
        </select>
      </div>

      <!-- Connector Types -->
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Connector Type A <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.connector_type_a"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          >
            <option value="">-- Select --</option>
            <option value="LC">LC</option>
            <option value="SC">SC</option>
            <option value="ST">ST</option>
            <option value="FC">FC</option>
            <option value="MTP/MPO">MTP/MPO</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Connector Type B <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.connector_type_b"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          >
            <option value="">-- Select --</option>
            <option value="LC">LC</option>
            <option value="SC">SC</option>
            <option value="ST">ST</option>
            <option value="FC">FC</option>
            <option value="MTP/MPO">MTP/MPO</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Status & Purchase Info -->
    <div class="border-t pt-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Status & Purchase</h3>

      <!-- Status -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Status <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.status"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
        >
          <option value="in_stock">In Stock</option>
          <option value="deployed">Deployed</option>
          <option value="faulty">Faulty</option>
          <option value="retired">Retired</option>
        </select>
      </div>

      <!-- Purchase Date -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Purchase Date
        </label>
        <input
          v-model="form.purchase_date"
          type="date"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
        />
      </div>
    </div>

    <!-- Notes -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Notes
      </label>
      <textarea
        v-model="form.notes"
        rows="3"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
        placeholder="Additional information..."
      ></textarea>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-3 pt-4 border-t">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="loading"
        class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:bg-blue-300"
      >
        {{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Create') }} Patch Cord
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/services/api'

const props = defineProps({
  patchCord: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['submit', 'cancel'])

const isEdit = ref(false)
const fsComProducts = ref([])

const form = ref({
  barcode: '',
  serial_number: '',
  fs_com_product_id: '',
  length: null,
  fiber_type: '',
  connector_type_a: '',
  connector_type_b: '',
  status: 'in_stock',
  purchase_date: '',
  notes: ''
})

// Load FS.com products
const loadFsComProducts = async () => {
  try {
    const response = await api.get('/catalog/fscom', {
      params: { category: 'patch_cord' }
    })
    fsComProducts.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load FS.com products:', error)
  }
}

// Generate barcode
const generateBarcode = () => {
  const random = Math.floor(Math.random() * 100000).toString().padStart(5, '0')
  form.value.barcode = `PC-${random}`
}

// Handle submit
const handleSubmit = () => {
  emit('submit', { ...form.value })
}

// Watch for patchCord prop changes
watch(() => props.patchCord, (newVal) => {
  if (newVal) {
    isEdit.value = true
    form.value = {
      barcode: newVal.barcode || '',
      serial_number: newVal.serial_number || '',
      fs_com_product_id: newVal.fs_com_product_id || '',
      length: newVal.length || null,
      fiber_type: newVal.fiber_type || '',
      connector_type_a: newVal.connector_type_a || '',
      connector_type_b: newVal.connector_type_b || '',
      status: newVal.status || 'in_stock',
      purchase_date: newVal.purchase_date || '',
      notes: newVal.notes || ''
    }
  } else {
    isEdit.value = false
    form.value = {
      barcode: '',
      serial_number: '',
      fs_com_product_id: '',
      length: null,
      fiber_type: '',
      connector_type_a: '',
      connector_type_b: '',
      status: 'in_stock',
      purchase_date: '',
      notes: ''
    }
  }
}, { immediate: true })

onMounted(() => {
  loadFsComProducts()
})
</script>
