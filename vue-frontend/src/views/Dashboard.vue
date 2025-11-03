<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Tableau de bord</h1>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total GBICs</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
              {{ loading ? '...' : stats.gbics?.total || 0 }}
            </p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Switches</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
              {{ loading ? '...' : stats.switches?.total || 0 }}
            </p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
            </svg>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Jarretières</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
              {{ loading ? '...' : stats.patch_cords?.total || 0 }}
            </p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Affectations actives</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
              {{ loading ? '...' : stats.assignments?.active || 0 }}
            </p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Welcome message -->
    <div class="card">
      <h2 class="text-xl font-bold text-gray-900 mb-4">
        Bienvenue, {{ authStore.user?.name }} !
      </h2>
      <p class="text-gray-600 mb-4">
        Vous êtes connecté en tant que <span class="font-medium">{{ authStore.userRole }}</span>.
      </p>
      <p class="text-gray-600">
        Utilisez la barre latérale pour naviguer dans l'application. Vous pouvez gérer les GBICs, switches, jarretières optiques,
        suivre les mouvements de stock et gérer les affectations.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const authStore = useAuthStore()
const loading = ref(false)
const stats = ref({
  gbics: { total: 0, in_stock: 0, assigned: 0, faulty: 0 },
  switches: { total: 0, deployed: 0, in_stock: 0, maintenance: 0 },
  patch_cords: { total: 0, in_stock: 0, deployed: 0, faulty: 0 },
  assignments: { active: 0, total: 0 }
})

// Load dashboard stats
const loadStats = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadStats()
})
</script>
