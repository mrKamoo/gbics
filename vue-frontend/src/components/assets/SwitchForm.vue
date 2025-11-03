<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Alert for errors -->
    <Alert
      v-model="showAlert"
      :type="alertType"
      :message="alertMessage"
      :timeout="5000"
    />

    <!-- Switch Model Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Switch Model <span class="text-red-500">*</span>
      </label>
      <select
        v-model="formData.switch_model_id"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        :class="{ 'border-red-500': errors.switch_model_id }"
      >
        <option :value="null">-- Select a model --</option>
        <option
          v-for="model in switchModels"
          :key="model.id"
          :value="model.id"
        >
          {{ model.manufacturer }} {{ model.model }} ({{ model.port_count }} ports)
        </option>
      </select>
      <p v-if="errors.switch_model_id" class="mt-1 text-sm text-red-600">
        {{ errors.switch_model_id }}
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

    <!-- Hostname -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Hostname
      </label>
      <input
        v-model="formData.hostname"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="e.g., switch-core-01"
      />
      <p class="mt-1 text-xs text-gray-500">
        Network hostname for this switch
      </p>
    </div>

    <!-- Asset Tag -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Asset Tag
      </label>
      <input
        v-model="formData.asset_tag"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="e.g., AST-SW-001"
      />
      <p class="mt-1 text-xs text-gray-500">
        Internal asset tracking number
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
        <option value="deployed">Deployed</option>
        <option value="maintenance">Maintenance</option>
        <option value="retired">Retired</option>
      </select>
    </div>

    <!-- Location Section -->
    <div class="pt-4 border-t">
      <h4 class="text-sm font-semibold text-gray-900 mb-4">Location (Optional)</h4>

      <!-- Site Selection -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Site
        </label>
        <select
          v-model="selectedSiteId"
          @change="onSiteChange"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option :value="null">-- Select a site --</option>
          <option
            v-for="site in sites"
            :key="site.id"
            :value="site.id"
          >
            {{ site.name }} ({{ site.city || 'N/A' }})
          </option>
        </select>
      </div>

      <!-- Rack Selection -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Rack
        </label>
        <select
          v-model="selectedRackId"
          @change="onRackChange"
          :disabled="!selectedSiteId"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
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

      <!-- Bay Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Bay
        </label>
        <select
          v-model="formData.bay_id"
          :disabled="!selectedRackId"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
        >
          <option :value="null">-- Select a bay --</option>
          <option
            v-for="bay in filteredBays"
            :key="bay.id"
            :value="bay.id"
          >
            Position {{ bay.position }}U{{ bay.name ? ` - ${bay.name}` : '' }}
          </option>
        </select>
        <p class="mt-1 text-xs text-gray-500">
          Physical location of the switch
        </p>
      </div>
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
          {{ isEdit ? 'Update Switch' : 'Create Switch' }}
        </span>
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import Alert from '@/components/common/Alert.vue'
import api from '@/services/api'

const props = defineProps({
  switchItem: {
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
const switchModels = ref([])
const sites = ref([])
const racks = ref([])
const bays = ref([])
const selectedSiteId = ref(null)
const selectedRackId = ref(null)

const formData = reactive({
  switch_model_id: null,
  bay_id: null,
  serial_number: '',
  barcode: '',
  hostname: '',
  asset_tag: '',
  status: 'in_stock',
  purchase_date: null,
  warranty_end: null,
  notes: ''
})

const errors = reactive({
  switch_model_id: '',
  serial_number: '',
  barcode: ''
})

// Filtered racks based on selected site
const filteredRacks = computed(() => {
  if (!selectedSiteId.value) return []
  return racks.value.filter(rack => rack.site_id === selectedSiteId.value)
})

// Filtered bays based on selected rack
const filteredBays = computed(() => {
  if (!selectedRackId.value) return []
  return bays.value.filter(bay => bay.rack_id === selectedRackId.value)
})

// Load switch models
const loadSwitchModels = async () => {
  try {
    const response = await api.get('/switch-models')
    switchModels.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load switch models:', error)
  }
}

// Load sites
const loadSites = async () => {
  try {
    const response = await api.get('/sites')
    sites.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load sites:', error)
  }
}

// Load racks
const loadRacks = async () => {
  try {
    const response = await api.get('/racks')
    racks.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load racks:', error)
  }
}

// Load bays
const loadBays = async () => {
  try {
    const response = await api.get('/bays')
    bays.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load bays:', error)
  }
}

// Handle site change
const onSiteChange = () => {
  selectedRackId.value = null
  formData.bay_id = null
}

// Handle rack change
const onRackChange = () => {
  formData.bay_id = null
}

// Generate random barcode
const generateBarcode = () => {
  const prefix = 'SW'
  const timestamp = Date.now().toString(36).toUpperCase()
  const random = Math.random().toString(36).substring(2, 8).toUpperCase()
  formData.barcode = `${prefix}-${timestamp}-${random}`
}

// Validate form
const validateForm = () => {
  let isValid = true
  errors.switch_model_id = ''
  errors.serial_number = ''
  errors.barcode = ''

  if (!formData.switch_model_id) {
    errors.switch_model_id = 'Switch model is required'
    isValid = false
  }

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
      bay_id: formData.bay_id || undefined,
      hostname: formData.hostname || undefined,
      asset_tag: formData.asset_tag || undefined,
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

// Initialize form with switch data if editing
watch(
  () => props.switchItem,
  async (newSwitch) => {
    if (newSwitch && props.isEdit) {
      Object.assign(formData, {
        switch_model_id: newSwitch.switch_model_id || null,
        bay_id: newSwitch.bay_id || null,
        serial_number: newSwitch.serial_number || '',
        barcode: newSwitch.barcode || '',
        hostname: newSwitch.hostname || '',
        asset_tag: newSwitch.asset_tag || '',
        status: newSwitch.status || 'in_stock',
        purchase_date: newSwitch.purchase_date || null,
        warranty_end: newSwitch.warranty_end || null,
        notes: newSwitch.notes || ''
      })

      // Set location hierarchy if bay is assigned
      if (newSwitch.bay_id && newSwitch.bay) {
        formData.bay_id = newSwitch.bay_id
        if (newSwitch.bay.rack) {
          selectedRackId.value = newSwitch.bay.rack.id
          if (newSwitch.bay.rack.site) {
            selectedSiteId.value = newSwitch.bay.rack.site.id
          }
        }
      }
    }
  },
  { immediate: true }
)

onMounted(async () => {
  await Promise.all([
    loadSwitchModels(),
    loadSites(),
    loadRacks(),
    loadBays()
  ])

  // Generate barcode for new switches
  if (!props.isEdit && !formData.barcode) {
    generateBarcode()
  }
})
</script>
