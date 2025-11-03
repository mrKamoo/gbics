<template>
  <div class="switch-port-view">
    <!-- Switch Info Header -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">
            {{ switchItem.hostname || switchItem.serial_number }}
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            {{ switchItem.switch_model?.manufacturer }} {{ switchItem.switch_model?.model }}
            - {{ switchItem.switch_model?.port_count }} Ports
          </p>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-500">Location</p>
          <p class="text-sm font-medium text-gray-900">
            {{ getLocationString() }}
          </p>
        </div>
      </div>
    </div>

    <!-- Port Statistics -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-blue-100 rounded-md p-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-xs text-gray-500">Total Ports</p>
            <p class="text-lg font-semibold text-gray-900">{{ portStats.total }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-green-100 rounded-md p-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-xs text-gray-500">Available</p>
            <p class="text-lg font-semibold text-gray-900">{{ portStats.available }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-purple-100 rounded-md p-2">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-xs text-gray-500">Assigned</p>
            <p class="text-lg font-semibold text-gray-900">{{ portStats.assigned }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-yellow-100 rounded-md p-2">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-xs text-gray-500">Utilization</p>
            <p class="text-lg font-semibold text-gray-900">{{ portStats.utilization }}%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Port Grid -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-4">
        <h4 class="text-sm font-semibold text-gray-900">Port Status</h4>
        <div class="flex gap-4 text-xs">
          <div class="flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full mr-1"></span>
            <span class="text-gray-600">Available</span>
          </div>
          <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full mr-1"></span>
            <span class="text-gray-600">Assigned</span>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <svg
          class="animate-spin h-8 w-8 text-blue-600"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
      </div>

      <!-- Port Grid -->
      <div v-else class="grid grid-cols-8 gap-2">
        <button
          v-for="port in ports"
          :key="port.port_number"
          @click="selectPort(port)"
          :class="[
            'relative p-3 rounded-lg border-2 transition-all hover:shadow-md',
            getPortClass(port),
            selectedPort?.port_number === port.port_number ? 'ring-2 ring-offset-2 ring-blue-500' : ''
          ]"
          :title="getPortTooltip(port)"
        >
          <div class="text-xs font-semibold text-center">
            {{ port.port_number }}
          </div>
          <div v-if="port.assignment" class="absolute top-0 right-0 w-2 h-2 bg-blue-600 rounded-full"></div>
        </button>
      </div>
    </div>

    <!-- Port Details Panel -->
    <transition name="slide-up">
      <div v-if="selectedPort" class="mt-6 bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
          <h4 class="text-lg font-semibold text-gray-900">
            Port {{ selectedPort.port_number }} Details
          </h4>
          <button
            @click="selectedPort = null"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Available Port -->
        <div v-if="!selectedPort.assignment" class="space-y-4">
          <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span class="text-sm font-medium text-green-800">Port Available</span>
            </div>
            <p class="text-sm text-green-700 mt-2">
              This port is currently not assigned and available for use.
            </p>
          </div>

          <button
            @click="assignToPort"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Assign Asset to this Port
          </button>
        </div>

        <!-- Assigned Port -->
        <div v-else class="space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="text-sm font-medium text-blue-800">Port Assigned</span>
              </div>
              <span class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded">
                {{ selectedPort.assignment.assignable_type }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
              <div>
                <p class="text-xs text-blue-700">Asset Type</p>
                <p class="text-sm font-medium text-blue-900">{{ selectedPort.assignment.assignable_type }}</p>
              </div>
              <div>
                <p class="text-xs text-blue-700">Assigned At</p>
                <p class="text-sm font-medium text-blue-900">{{ formatDateTime(selectedPort.assignment.assigned_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-blue-700">Assigned By</p>
                <p class="text-sm font-medium text-blue-900">{{ selectedPort.assignment.assigned_by_user?.name || 'N/A' }}</p>
              </div>
            </div>

            <div v-if="selectedPort.assignment.notes" class="mt-4 pt-4 border-t border-blue-200">
              <p class="text-xs text-blue-700">Notes</p>
              <p class="text-sm text-blue-900 mt-1">{{ selectedPort.assignment.notes }}</p>
            </div>
          </div>

          <div class="flex gap-3">
            <button
              @click="viewAssignment"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              View Assignment Details
            </button>
            <button
              @click="unassignPort"
              class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
            >
              Unassign Port
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const props = defineProps({
  switchItem: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['assign', 'unassign', 'view-assignment'])

const loading = ref(false)
const ports = ref([])
const selectedPort = ref(null)

// Port statistics
const portStats = computed(() => {
  const total = ports.value.length
  const assigned = ports.value.filter(p => p.assignment).length
  const available = total - assigned
  const utilization = total > 0 ? Math.round((assigned / total) * 100) : 0

  return { total, assigned, available, utilization }
})

// Get location string
const getLocationString = () => {
  if (!props.switchItem.bay) return 'Not assigned'

  const site = props.switchItem.bay.rack?.site?.name || 'Unknown'
  const rack = props.switchItem.bay.rack?.name || 'Unknown'
  const bay = props.switchItem.bay.position || 'Unknown'

  return `${site} / ${rack} / ${bay}U`
}

// Get port class based on status
const getPortClass = (port) => {
  if (port.assignment) {
    return 'bg-blue-50 border-blue-300 text-blue-900 hover:bg-blue-100'
  }
  return 'bg-green-50 border-green-300 text-green-900 hover:bg-green-100'
}

// Get port tooltip
const getPortTooltip = (port) => {
  if (port.assignment) {
    return `Port ${port.port_number} - Assigned (${port.assignment.assignable_type})`
  }
  return `Port ${port.port_number} - Available`
}

// Load port status
const loadPorts = async () => {
  loading.value = true
  try {
    const response = await api.get(`/switches/${props.switchItem.id}/ports`)
    ports.value = response.data.ports || []
  } catch (error) {
    console.error('Failed to load ports:', error)
    // Fallback: Generate ports based on model port count
    const portCount = props.switchItem.switch_model?.port_count || 24
    ports.value = Array.from({ length: portCount }, (_, i) => ({
      port_number: i + 1,
      assignment: null
    }))
  } finally {
    loading.value = false
  }
}

// Select a port
const selectPort = (port) => {
  selectedPort.value = port
}

// Assign asset to port
const assignToPort = () => {
  emit('assign', {
    switchId: props.switchItem.id,
    portNumber: selectedPort.value.port_number
  })
}

// Unassign port
const unassignPort = () => {
  emit('unassign', {
    assignmentId: selectedPort.value.assignment.id,
    portNumber: selectedPort.value.port_number
  })
  selectedPort.value = null
}

// View assignment details
const viewAssignment = () => {
  emit('view-assignment', selectedPort.value.assignment)
}

// Format date time
const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Refresh ports (exposed for parent component)
const refresh = () => {
  loadPorts()
}

// Expose refresh method
defineExpose({ refresh })

onMounted(() => {
  loadPorts()
})
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
