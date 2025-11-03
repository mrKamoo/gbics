<template>
  <div class="min-h-screen bg-gray-50">
    <AppHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />

    <div class="flex">
      <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <main class="flex-1 p-6 lg:ml-64 transition-all duration-300">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'

const sidebarOpen = ref(false)
</script>
