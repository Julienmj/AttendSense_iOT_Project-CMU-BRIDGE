<template>
  <div class="min-h-screen bg-gray-50 font-sans">
    <Navbar />
    <main class="max-w-6xl mx-auto px-6 py-10">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import Navbar from './components/Navbar.vue'
</script>
