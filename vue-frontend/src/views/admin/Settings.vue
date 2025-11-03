<template>
  <div class="settings-page">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
      <p class="text-sm text-gray-500 mt-1">Manage system configuration and reference data</p>
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
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
            </svg>
            {{ tab.name }}
          </button>
        </nav>
      </div>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-lg shadow">
      <!-- Switch Models Tab -->
      <div v-show="activeTab === 'switch-models'" class="p-6">
        <SwitchModelsManager @notification="showNotification" />
      </div>

      <!-- Sites Tab -->
      <div v-show="activeTab === 'sites'" class="p-6">
        <SitesManager @notification="showNotification" />
      </div>

      <!-- Racks Tab -->
      <div v-show="activeTab === 'racks'" class="p-6">
        <RacksManager @notification="showNotification" />
      </div>

      <!-- Bays Tab -->
      <div v-show="activeTab === 'bays'" class="p-6">
        <BaysManager @notification="showNotification" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Alert from '@/components/common/Alert.vue'
import SwitchModelsManager from '@/components/settings/SwitchModelsManager.vue'
import SitesManager from '@/components/settings/SitesManager.vue'
import RacksManager from '@/components/settings/RacksManager.vue'
import BaysManager from '@/components/settings/BaysManager.vue'

const activeTab = ref('switch-models')
const showAlert = ref(false)
const alertType = ref('success')
const alertTitle = ref('')
const alertMessage = ref('')

const tabs = [
  {
    id: 'switch-models',
    name: 'Switch Models',
    icon: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z'
  },
  {
    id: 'sites',
    name: 'Sites',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
  },
  {
    id: 'racks',
    name: 'Racks',
    icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01'
  },
  {
    id: 'bays',
    name: 'Bays',
    icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'
  }
]

const showNotification = ({ type, title, message }) => {
  alertType.value = type
  alertTitle.value = title
  alertMessage.value = message
  showAlert.value = true
}
</script>

<style scoped>
.settings-page {
  margin: 0 auto;
  padding: 1.5rem;
}
</style>
