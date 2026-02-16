<template>
  <div class="flex h-screen bg-gray-100">
    <Sidebar />

    <div class="flex flex-col flex-1">
      <Topbar />
      <main class="p-6 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import Sidebar from '@/components/layout/Sidebar.vue'
import Topbar from '@/components/layout/Topbar.vue'
</script>