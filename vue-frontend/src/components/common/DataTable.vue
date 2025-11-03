<template>
  <div class="data-table">
    <!-- Search Bar -->
    <div v-if="searchable" class="p-6">
      <input
        v-model="searchQuery"
        type="text"
        :placeholder="searchPlaceholder"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      />
    </div>

    <!-- Table -->
    <div class="overflow-x-auto" :class="{ 'border-t border-gray-200': searchable }">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              @click="column.sortable !== false && sort(column.key)"
            >
              <div class="flex items-center justify-between">
                <span>{{ column.label }}</span>
                <span v-if="column.sortable !== false && sortBy === column.key" class="ml-2">
                  <svg
                    v-if="sortOrder === 'asc'"
                    class="w-4 h-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                    />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    />
                  </svg>
                </span>
              </div>
            </th>
            <th
              v-if="actions"
              class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading" class="text-center">
            <td :colspan="columns.length + (actions ? 1 : 0)" class="px-6 py-12">
              <div class="flex justify-center items-center">
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
            </td>
          </tr>
          <tr v-else-if="filteredData.length === 0" class="text-center">
            <td :colspan="columns.length + (actions ? 1 : 0)" class="px-6 py-12 text-gray-500">
              {{ emptyText }}
            </td>
          </tr>
          <tr
            v-else
            v-for="(item, index) in paginatedData"
            :key="item.id || index"
            class="hover:bg-gray-50 transition-colors"
          >
            <td v-for="column in columns" :key="column.key" class="px-6 py-4 whitespace-nowrap">
              <slot :name="`cell-${column.key}`" :item="item" :value="getNestedValue(item, column.key)">
                <span v-if="column.type === 'badge'" :class="getBadgeClass(getNestedValue(item, column.key), column.badgeColors)">
                  {{ column.format ? column.format(getNestedValue(item, column.key)) : getNestedValue(item, column.key) }}
                </span>
                <span v-else-if="column.type === 'date'" class="text-sm text-gray-900">
                  {{ formatDate(getNestedValue(item, column.key)) }}
                </span>
                <span v-else class="text-sm text-gray-900">
                  {{ column.format ? column.format(getNestedValue(item, column.key)) : getNestedValue(item, column.key) }}
                </span>
              </slot>
            </td>
            <td v-if="actions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <slot name="actions" :item="item">
                <button
                  v-if="actions.view"
                  @click="$emit('view', item)"
                  class="text-blue-600 hover:text-blue-900 mr-3"
                  title="View"
                >
                  <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>
                <button
                  v-if="actions.edit"
                  @click="$emit('edit', item)"
                  class="text-green-600 hover:text-green-900 mr-3"
                  title="Edit"
                >
                  <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                </button>
                <button
                  v-if="actions.delete"
                  @click="$emit('delete', item)"
                  class="text-red-600 hover:text-red-900"
                  title="Delete"
                >
                  <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="paginate && filteredData.length > perPage" class="px-6 py-4 flex items-center justify-between border-t border-gray-200">
      <div class="text-sm text-gray-700">
        Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, filteredData.length) }} of {{ filteredData.length }} results
      </div>
      <div class="flex gap-2">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="px-3 py-1 border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Previous
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="currentPage = page"
          :class="[
            'px-3 py-1 border rounded-md',
            currentPage === page
              ? 'bg-blue-600 text-white border-blue-600'
              : 'border-gray-300 hover:bg-gray-50'
          ]"
        >
          {{ page }}
        </button>
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  searchable: {
    type: Boolean,
    default: true
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  paginate: {
    type: Boolean,
    default: true
  },
  perPage: {
    type: Number,
    default: 10
  },
  actions: {
    type: Object,
    default: null
  },
  emptyText: {
    type: String,
    default: 'No data available'
  }
})

defineEmits(['view', 'edit', 'delete'])

const searchQuery = ref('')
const sortBy = ref('')
const sortOrder = ref('asc')
const currentPage = ref(1)

// Get nested value from object
const getNestedValue = (obj, path) => {
  return path.split('.').reduce((acc, part) => acc && acc[part], obj)
}

// Filtered data based on search
const filteredData = computed(() => {
  if (!searchQuery.value) return props.data

  const query = searchQuery.value.toLowerCase()
  return props.data.filter((item) => {
    return props.columns.some((column) => {
      const value = getNestedValue(item, column.key)
      return value && String(value).toLowerCase().includes(query)
    })
  })
})

// Sort data
const sortedData = computed(() => {
  if (!sortBy.value) return filteredData.value

  return [...filteredData.value].sort((a, b) => {
    const aVal = getNestedValue(a, sortBy.value)
    const bVal = getNestedValue(b, sortBy.value)

    if (aVal === bVal) return 0

    const comparison = aVal > bVal ? 1 : -1
    return sortOrder.value === 'asc' ? comparison : -comparison
  })
})

// Paginated data
const totalPages = computed(() => Math.ceil(filteredData.value.length / props.perPage))

const paginatedData = computed(() => {
  if (!props.paginate) return sortedData.value

  const start = (currentPage.value - 1) * props.perPage
  const end = start + props.perPage
  return sortedData.value.slice(start, end)
})

// Visible pages for pagination
const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)

  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Sort function
const sort = (key) => {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = key
    sortOrder.value = 'asc'
  }
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Get badge class based on value
const getBadgeClass = (value, colors = {}) => {
  const baseClass = 'px-2 py-1 text-xs font-semibold rounded-full'
  const colorMap = {
    success: 'bg-green-100 text-green-800',
    info: 'bg-blue-100 text-blue-800',
    warning: 'bg-yellow-100 text-yellow-800',
    danger: 'bg-red-100 text-red-800',
    secondary: 'bg-gray-100 text-gray-800'
  }

  const color = colors[value] || 'secondary'
  return `${baseClass} ${colorMap[color]}`
}

// Reset to page 1 when search or data changes
watch([searchQuery, () => props.data], () => {
  currentPage.value = 1
})
</script>
