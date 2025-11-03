<template>
  <header class="bg-white shadow-sm sticky top-0 z-40">
    <div class="flex items-center justify-between px-4 py-3">
      <!-- Left side -->
      <div class="flex items-center space-x-4">
        <button
          @click="$emit('toggle-sidebar')"
          class="lg:hidden p-2 rounded-lg hover:bg-gray-100"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <h1 class="text-xl font-bold text-primary-600">
          {{ appName }}
        </h1>
      </div>

      <!-- Right side -->
      <div class="flex items-center space-x-4">
        <!-- User menu -->
        <div class="relative">
          <button
            @click="userMenuOpen = !userMenuOpen"
            class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100"
          >
            <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
              {{ userInitials }}
            </div>
            <span class="hidden md:block text-sm font-medium">{{ authStore.user?.name }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Dropdown -->
          <div
            v-show="userMenuOpen"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50"
            @click.away="userMenuOpen = false"
          >
            <div class="px-4 py-2 border-b border-gray-200">
              <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
              <p class="text-xs text-gray-500">{{ authStore.userRole }}</p>
            </div>
            <button
              @click="handleLogout"
              class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              Sign out
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const userMenuOpen = ref(false)
const appName = import.meta.env.VITE_APP_NAME

const userInitials = computed(() => {
  const name = authStore.user?.name || ''
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
})

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>
