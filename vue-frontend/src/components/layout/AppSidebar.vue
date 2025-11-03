<template>
  <!-- Mobile overlay -->
  <div
    v-if="open"
    class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
    @click="$emit('close')"
  ></div>

  <!-- Sidebar -->
  <aside
    :class="[
      'fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform transition-transform duration-300',
      'lg:translate-x-0 lg:mt-16',
      open ? 'translate-x-0' : '-translate-x-full'
    ]"
  >
    <nav class="h-full overflow-y-auto py-4">
      <!-- Dashboard -->
      <router-link
        to="/"
        class="nav-link"
        active-class="nav-link-active"
        exact
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span>Tableau de bord</span>
      </router-link>

      <!-- Assets Section -->
      <div class="mt-6">
        <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
          Équipements
        </h3>

        <router-link to="/gbics" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
          </svg>
          <span>GBICs</span>
        </router-link>

        <router-link to="/switches" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
          </svg>
          <span>Switches</span>
        </router-link>

        <router-link to="/patch-cords" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <span>Jarretières</span>
        </router-link>
      </div>

      <!-- Inventory Section -->
      <div class="mt-6">
        <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
          Inventaire
        </h3>

        <router-link to="/inventory/stock" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <span>Vue d'ensemble</span>
        </router-link>

        <router-link to="/inventory/movements" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <span>Mouvements</span>
        </router-link>

        <router-link to="/inventory/assignments" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Affectations</span>
        </router-link>
      </div>

      <!-- Locations -->
      <div class="mt-6">
        <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
          Localisations
        </h3>

        <router-link to="/locations" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>Sites & Racks</span>
        </router-link>
      </div>

      <!-- Admin Section -->
      <div v-if="authStore.hasAnyRole(['super_admin', 'admin'])" class="mt-6">
        <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
          Administration
        </h3>

        <router-link to="/admin/users" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <span>Utilisateurs</span>
        </router-link>

        <router-link to="/admin/settings" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>Paramètres</span>
        </router-link>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

defineProps({
  open: Boolean
})

defineEmits(['close'])

const authStore = useAuthStore()
</script>

<style scoped>
.nav-link {
  @apply flex items-center space-x-3 px-4 py-2.5 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors;
}

.nav-link-active {
  @apply bg-primary-50 text-primary-600 border-r-4 border-primary-600;
}
</style>
