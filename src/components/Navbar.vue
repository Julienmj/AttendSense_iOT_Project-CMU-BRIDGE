<template>
  <nav class="border-b border-gray-200 bg-white/90 backdrop-blur sticky top-0 z-50 card-shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <svg class="w-8 h-8 text-primary" viewBox="0 0 32 32" fill="currentColor">
            <path d="M16 2C8.268 2 2 8.268 2 16s6.268 14 14 14 14-6.268 14-14S23.732 2 16 2zm0 2c6.627 0 12 5.373 12 12s-5.373 12-12 12S4 22.627 4 16 9.373 4 16 4z"/>
            <path d="M12 10h8v2h-8zm0 4h8v2h-8zm0 4h8v2h-8z"/>
            <circle cx="10" cy="11" r="1"/>
            <circle cx="10" cy="15" r="1"/>
            <circle cx="10" cy="19" r="1"/>
          </svg>
          <span class="font-sans text-primary text-lg font-bold tracking-tight">AttendSense</span>
        </div>
        <div class="w-2.5 h-2.5 rounded-full bg-primary pulse-dot"></div>
      </div>
      <div class="flex gap-1">
        <RouterLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 text-text-secondary hover:text-text-primary hover:bg-tertiary"
          active-class="text-primary bg-tertiary border-l-2 border-primary"
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </div>
  </nav>
</template>

<script setup>
const links = [
  { to: '/', label: 'Dashboard' },
  { to: '/classes', label: 'Classes' },
  { to: '/sessions', label: 'Sessions' },
  { to: '/reports', label: 'Reports' },
]
</script>
