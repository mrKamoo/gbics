<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700 px-4">
    <div class="max-w-md w-full">
      <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
        <!-- Icon -->
        <div class="flex justify-center mb-6">
          <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-gray-900 mb-4">
          Account Pending Approval
        </h1>

        <!-- Message -->
        <p class="text-gray-600 mb-6">
          Your account has been created successfully and is waiting for administrator approval.
          You'll receive an email notification once your account has been approved.
        </p>

        <!-- User info -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
          <p class="text-sm text-gray-600 mb-1">Account details:</p>
          <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
          <p class="text-sm text-gray-600">{{ authStore.user?.email }}</p>
        </div>

        <!-- Logout button -->
        <button
          @click="handleLogout"
          class="btn btn-primary"
        >
          Sign out
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>
