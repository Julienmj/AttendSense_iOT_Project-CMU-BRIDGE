<template>
  <div class="fade-in">
    <!-- Header -->
    <div class="mb-10">
      <div class="flex items-center gap-4 mb-4">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <svg class="w-10 h-10 text-primary" viewBox="0 0 32 32" fill="currentColor">
            <path d="M16 2C8.268 2 2 8.268 2 16s6.268 14 14 14 14-6.268 14-14S23.732 2 16 2zm0 2c6.627 0 12 5.373 12 12s-5.373 12-12 12S4 22.627 4 16 9.373 4 16 4z"/>
            <path d="M12 10h8v2h-8zm0 4h8v2h-8zm0 4h8v2h-8z"/>
            <circle cx="10" cy="11" r="1"/>
            <circle cx="10" cy="15" r="1"/>
            <circle cx="10" cy="19" r="1"/>
          </svg>
          <div>
            <p class="font-sans text-primary text-xs font-semibold tracking-wider">ATTENDANCE SYSTEM</p>
            <h1 class="text-4xl font-bold text-text-primary leading-tight">AttendSense <span class="text-primary">Dashboard</span></h1>
          </div>
        </div>
      </div>
      <p class="text-text-secondary max-w-lg text-sm leading-relaxed">
        Smart Bluetooth-based attendance tracking for educational institutions.
      </p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="font-sans text-xs text-text-secondary tracking-wider mb-1">TOTAL CLASSES</p>
        <p class="text-3xl font-bold text-primary">{{ classes.length }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="font-sans text-xs text-text-secondary tracking-wider mb-1">TOTAL STUDENTS</p>
        <p class="text-3xl font-bold text-text-primary">{{ totalStudents }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="font-sans text-xs text-text-secondary tracking-wider mb-1">ACTIVE SESSIONS</p>
        <p class="text-3xl font-bold text-success">{{ activeSessions }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="font-sans text-xs text-text-secondary tracking-wider mb-1">TODAY'S RATE</p>
        <p class="text-3xl font-bold text-primary">{{ todayRate }}%</p>
      </div>
    </div>

    <!-- Registered Classes -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow mb-8">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">Registered Classes</h2>
        <RouterLink 
          to="/classes" 
          class="text-primary hover:text-primary-dark text-sm font-medium"
        >
          Manage Classes →
        </RouterLink>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="classItem in classes" :key="classItem.id" class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
          <h3 class="font-semibold text-text-primary mb-2">{{ classItem.name }}</h3>
          <p class="text-sm text-text-secondary mb-3">{{ classItem.course_code }}</p>
          <div class="flex justify-between items-center text-sm">
            <span class="text-text-secondary">{{ classItem.student_count || 0 }} students</span>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/20 text-success">
              Active
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Navigation -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Quick Actions</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <RouterLink 
          to="/classes" 
          class="p-4 border border-gray-200 rounded-lg text-center hover:bg-gray-50 transition-colors"
        >
          <div class="w-8 h-8 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-2">
            <span class="text-primary font-semibold">C</span>
          </div>
          <p class="text-sm font-medium text-text-primary">Manage Classes</p>
        </RouterLink>
        
        <RouterLink 
          to="/sessions" 
          class="p-4 border border-gray-200 rounded-lg text-center hover:bg-gray-50 transition-colors"
        >
          <div class="w-8 h-8 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-2">
            <span class="text-primary font-semibold">S</span>
          </div>
          <p class="text-sm font-medium text-text-primary">Create Sessions</p>
        </RouterLink>
        
        <RouterLink 
          to="/reports" 
          class="p-4 border border-gray-200 rounded-lg text-center hover:bg-gray-50 transition-colors"
        >
          <div class="w-8 h-8 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-2">
            <span class="text-primary font-semibold">R</span>
          </div>
          <p class="text-sm font-medium text-text-primary">View Reports</p>
        </RouterLink>
        
        <div 
          class="p-4 border border-gray-200 rounded-lg text-center hover:bg-gray-50 transition-colors cursor-pointer"
        >
          <div class="w-8 h-8 bg-success/20 rounded-full flex items-center justify-center mx-auto mb-2">
            <span class="text-success font-semibold">A</span>
          </div>
          <p class="text-sm font-medium text-text-primary">Start Scan</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useAttendanceStore } from '../stores/attendance.js'

const attendanceStore = useAttendanceStore()

// Load data on component mount
onMounted(async () => {
  await Promise.all([
    attendanceStore.loadClasses(),
    attendanceStore.loadSessions()
  ])
})

const classes = computed(() => attendanceStore.classes)
const totalStudents = computed(() => attendanceStore.totalStudents)
const activeSessions = computed(() => attendanceStore.activeSessions.length)
const todayRate = computed(() => {
  const today = attendanceStore.todaySessions
  if (today.length === 0) return 0
  const totalPresent = today.reduce((sum, s) => sum + (parseInt(s.present_count) || 0), 0)
  const totalPossible = today.reduce((sum, s) => sum + (parseInt(s.total_students) || 0), 0)
  return totalPossible > 0 ? Math.round((totalPresent / totalPossible) * 100) : 0
})
</script>
