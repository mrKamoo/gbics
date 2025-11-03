<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700 px-4">
    <div class="max-w-md w-full">
      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <!-- Logo/Title -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">{{ appName }}</h1>
          <p class="text-gray-600 mt-2">Create your account</p>
        </div>

        <!-- Success message -->
        <div v-if="success" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
          <p class="text-green-700 text-sm font-medium">Account created successfully!</p>
          <p class="text-green-600 text-sm mt-1">
            Your account is pending approval. You'll receive an email once an administrator approves your account.
          </p>
          <router-link to="/login" class="inline-block mt-3 text-sm text-primary-600 hover:text-primary-700 font-medium">
            Go to login →
          </router-link>
        </div>

        <!-- Error message -->
        <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {{ error }}
        </div>

        <!-- Form -->
        <form v-if="!success" @submit.prevent="handleRegister">
          <div class="space-y-4">
            <!-- Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                Full Name
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="input"
                placeholder="John Doe"
              />
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="input"
                placeholder="your@email.com"
              />
            </div>

            <!-- Password -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                Password
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                minlength="8"
                class="input"
                placeholder="••••••••"
              />
              <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
            </div>

            <!-- Password confirmation -->
            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                Confirm Password
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                class="input"
                placeholder="••••••••"
              />
            </div>

            <!-- Submit button -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading">Creating account...</span>
              <span v-else">Create account</span>
            </button>
          </div>
        </form>

        <!-- Login link -->
        <div v-if="!success" class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Already have an account?
            <router-link to="/login" class="text-primary-600 hover:text-primary-700 font-medium">
              Sign in
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const appName = import.meta.env.VITE_APP_NAME

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const loading = ref(false)
const error = ref(null)
const success = ref(false)

async function handleRegister() {
  // Validate passwords match
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match'
    return
  }

  loading.value = true
  error.value = null

  try {
    await authStore.register(form.value)
    success.value = true
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      error.value = Object.values(errors).flat().join(', ')
    } else {
      error.value = err.response?.data?.message || 'Failed to create account. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>
