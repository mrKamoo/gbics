<template>
  <div class="users-page">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
      <p class="text-sm text-gray-500 mt-1">Manage users, approvals and roles</p>
    </div>

    <!-- Alert Messages -->
    <Alert
      v-model="showAlert"
      :type="alertType"
      :title="alertTitle"
      :message="alertMessage"
      :timeout="5000"
    />

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="border-b border-gray-200">
        <nav class="flex -mb-px">
          <button
            @click="activeTab = 'pending'"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'pending'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Pending Approval
            <span v-if="pendingCount > 0" class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
              {{ pendingCount }}
            </span>
          </button>
          <button
            @click="activeTab = 'all'"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'all'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            All Users
          </button>
        </nav>
      </div>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-lg shadow">
      <!-- Pending Approval Tab -->
      <div v-show="activeTab === 'pending'" class="p-6">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Users Pending Approval</h2>
          <p class="text-sm text-gray-500 mt-1">Review and approve new user registrations</p>
        </div>

        <DataTable
          :columns="pendingColumns"
          :data="pendingUsers"
          :loading="loading"
          :actions="{ view: false, edit: false, delete: false }"
          search-placeholder="Search pending users..."
          :empty-text="'No users pending approval'"
        >
          <!-- Custom actions for pending users -->
          <template #actions="{ item }">
            <button
              @click="approveUser(item)"
              class="text-green-600 hover:text-green-900 mr-3"
              title="Approve"
            >
              <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>
            <button
              @click="rejectUser(item)"
              class="text-red-600 hover:text-red-900"
              title="Reject"
            >
              <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>
          </template>

          <!-- Custom cell for registration date -->
          <template #cell-created_at="{ value }">
            <span class="text-sm text-gray-900">{{ formatDateTime(value) }}</span>
          </template>
        </DataTable>
      </div>

      <!-- All Users Tab -->
      <div v-show="activeTab === 'all'" class="p-6">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">All Users</h2>
            <p class="text-sm text-gray-500 mt-1">Manage user accounts and permissions</p>
          </div>
        </div>

        <DataTable
          :columns="allUsersColumns"
          :data="approvedUsers"
          :loading="loading"
          :actions="{ view: true, edit: true, delete: true }"
          search-placeholder="Search users..."
          @view="viewUser"
          @edit="editUser"
          @delete="confirmDeleteUser"
        >
          <!-- Custom cell for status -->
          <template #cell-status="{ item }">
            <span v-if="item.is_approved" class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
              Active
            </span>
            <span v-else class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
              Pending
            </span>
          </template>

          <!-- Custom cell for roles -->
          <template #cell-roles="{ value }">
            <div v-if="value && value.length" class="flex flex-wrap gap-1">
              <span
                v-for="(role, index) in value"
                :key="index"
                class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
              >
                {{ typeof role === 'string' ? role : role.name }}
              </span>
            </div>
            <span v-else class="text-gray-400 text-sm">No role</span>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Edit User Modal -->
    <Modal
      v-model="showEditModal"
      :title="`Edit User - ${selectedUser?.name || ''}`"
      size="lg"
      hide-footer
    >
      <form @submit.prevent="handleUpdateUser" class="space-y-4">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Name
          </label>
          <input
            v-model="editForm.name"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            readonly
          />
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email
          </label>
          <input
            v-model="editForm.email"
            type="email"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            readonly
          />
        </div>

        <!-- Role Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Role <span class="text-red-500">*</span>
          </label>
          <select
            v-model="editForm.role"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          >
            <option value="">-- Select a role --</option>
            <option value="super_admin">Super Admin</option>
            <option value="admin">Admin</option>
            <option value="technician">Technician</option>
            <option value="reader">Reader</option>
          </select>
          <p class="mt-1 text-xs text-gray-500">
            Assign a role to define user permissions
          </p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t">
          <button
            type="button"
            @click="closeEditModal"
            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:bg-blue-300"
          >
            {{ submitting ? 'Updating...' : 'Update Role' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- View User Modal -->
    <Modal
      v-model="showViewModal"
      :title="`User Details - ${selectedUser?.name || ''}`"
      size="lg"
      hide-confirm-button
      cancel-text="Close"
    >
      <div v-if="selectedUser" class="space-y-4">
        <!-- User Information -->
        <div>
          <h4 class="font-semibold text-gray-900 mb-2">User Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Name</p>
              <p class="text-sm font-medium">{{ selectedUser.name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Email</p>
              <p class="text-sm font-medium">{{ selectedUser.email }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Status</p>
              <span v-if="selectedUser.is_approved" class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                Active
              </span>
              <span v-else class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                Pending
              </span>
            </div>
            <div>
              <p class="text-sm text-gray-500">Roles</p>
              <div v-if="selectedUser.roles && selectedUser.roles.length" class="flex flex-wrap gap-1 mt-1">
                <span
                  v-for="(role, index) in selectedUser.roles"
                  :key="index"
                  class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                >
                  {{ typeof role === 'string' ? role : role.name }}
                </span>
              </div>
              <p v-else class="text-sm text-gray-400">No role assigned</p>
            </div>
          </div>
        </div>

        <!-- Approval Information -->
        <div v-if="selectedUser.is_approved">
          <h4 class="font-semibold text-gray-900 mb-2">Approval Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Approved At</p>
              <p class="text-sm font-medium">{{ formatDateTime(selectedUser.approved_at) }}</p>
            </div>
            <div v-if="selectedUser.approved_by_user">
              <p class="text-sm text-gray-500">Approved By</p>
              <p class="text-sm font-medium">{{ selectedUser.approved_by_user.name }}</p>
            </div>
          </div>
        </div>

        <!-- Timestamps -->
        <div class="text-xs text-gray-500 pt-4 border-t">
          <p>Registered: {{ formatDateTime(selectedUser.created_at) }}</p>
          <p>Last updated: {{ formatDateTime(selectedUser.updated_at) }}</p>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="showDeleteModal"
      title="Delete User"
      confirm-text="Delete"
      confirm-variant="danger"
      @confirm="handleDeleteUser"
    >
      <p class="text-gray-600">Are you sure you want to delete this user?</p>
      <div v-if="selectedUser" class="mt-4 p-4 bg-gray-50 rounded-md">
        <p class="text-sm"><strong>{{ selectedUser.name }}</strong></p>
        <p class="text-sm text-gray-600">{{ selectedUser.email }}</p>
      </div>
      <p class="mt-4 text-sm text-red-600">
        This action cannot be undone.
      </p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Modal from '@/components/common/Modal.vue'
import Alert from '@/components/common/Alert.vue'
import api from '@/services/api'

const activeTab = ref('pending')
const loading = ref(false)
const users = ref([])
const selectedUser = ref(null)
const showEditModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const submitting = ref(false)
const showAlert = ref(false)
const alertType = ref('success')
const alertTitle = ref('')
const alertMessage = ref('')

const editForm = ref({
  name: '',
  email: '',
  role: ''
})

// Computed
const pendingUsers = computed(() => users.value.filter(u => !u.is_approved))
const approvedUsers = computed(() => users.value.filter(u => u.is_approved))
const pendingCount = computed(() => pendingUsers.value.length)

// Columns
const pendingColumns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'created_at', label: 'Registered', sortable: true }
]

const allUsersColumns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'roles', label: 'Roles', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'created_at', label: 'Registered', sortable: true, type: 'date' }
]

// Load users
const loadUsers = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/users')
    users.value = response.data.data || response.data
  } catch (error) {
    showNotification('error', 'Error', 'Failed to load users')
  } finally {
    loading.value = false
  }
}

// Approve user
const approveUser = async (user) => {
  try {
    await api.post(`/admin/users/${user.id}/approve`)
    showNotification('success', 'Success', `User ${user.name} approved successfully`)
    loadUsers()
  } catch (error) {
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to approve user')
  }
}

// Reject user
const rejectUser = async (user) => {
  if (!confirm(`Are you sure you want to reject ${user.name}?`)) return

  try {
    await api.post(`/admin/users/${user.id}/reject`)
    showNotification('success', 'Success', `User ${user.name} rejected`)
    loadUsers()
  } catch (error) {
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to reject user')
  }
}

// View user
const viewUser = (user) => {
  selectedUser.value = user
  showViewModal.value = true
}

// Edit user
const editUser = (user) => {
  selectedUser.value = user
  editForm.value = {
    name: user.name,
    email: user.email,
    role: Array.isArray(user.roles) && user.roles.length > 0
      ? (typeof user.roles[0] === 'string' ? user.roles[0] : user.roles[0].name)
      : ''
  }
  showEditModal.value = true
}

// Close edit modal
const closeEditModal = () => {
  showEditModal.value = false
  selectedUser.value = null
}

// Update user role
const handleUpdateUser = async () => {
  submitting.value = true
  try {
    await api.put(`/admin/users/${selectedUser.value.id}/role`, {
      role: editForm.value.role
    })
    showNotification('success', 'Success', 'User role updated successfully')
    closeEditModal()
    loadUsers()
  } catch (error) {
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to update user role')
  } finally {
    submitting.value = false
  }
}

// Confirm delete
const confirmDeleteUser = (user) => {
  selectedUser.value = user
  showDeleteModal.value = true
}

// Delete user
const handleDeleteUser = async () => {
  try {
    await api.delete(`/admin/users/${selectedUser.value.id}`)
    showNotification('success', 'Success', 'User deleted successfully')
    showDeleteModal.value = false
    loadUsers()
  } catch (error) {
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to delete user')
  }
}

// Format date time
const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Show notification
const showNotification = (type, title, message) => {
  alertType.value = type
  alertTitle.value = title
  alertMessage.value = message
  showAlert.value = true
}

onMounted(() => {
  loadUsers()
})
</script>

<style scoped>
.users-page {
  margin: 0 auto;
  padding: 1.5rem;
}
</style>
