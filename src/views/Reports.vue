<template>
  <div class="fade-in">
    <div class="mb-8">
      <p class="font-sans text-primary text-xs font-semibold tracking-wider mb-2">ATTENDANCE REPORTS</p>
      <h1 class="text-3xl font-bold text-text-primary leading-tight">Class <span class="text-primary">Reports</span></h1>
      <p class="text-text-secondary mt-2 max-w-lg text-sm">View attendance summaries and export reports for your classes.</p>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Generate Report</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Select Class</label>
          <select v-model="filters.class_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">All Classes</option>
            <option v-for="cls in store.classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">From Date</label>
          <input v-model="filters.date_from" type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">To Date</label>
          <input v-model="filters.date_to" type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
        <div class="flex items-end">
          <button @click="loadReport" :disabled="loading"
            class="w-full px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors disabled:opacity-50">
            {{ loading ? 'Loading...' : 'Generate' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Total Classes</p>
        <p class="text-2xl font-bold text-primary">{{ summary.total_classes || 0 }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Total Students</p>
        <p class="text-2xl font-bold text-text-primary">{{ summary.total_students || 0 }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Today's Sessions</p>
        <p class="text-2xl font-bold text-text-primary">{{ summary.today_sessions || 0 }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Weekly Attendance</p>
        <p class="text-2xl font-bold text-primary">{{ summary.weekly_attendance_rate || 0 }}%</p>
      </div>
    </div>

    <!-- Class Performance -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow mb-8">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">Class Performance</h2>
        <button @click="exportCSV"
          class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
          Export CSV
        </button>
      </div>

      <div v-if="classPerformance.length === 0" class="text-text-secondary text-sm py-4 text-center">No data available.</div>
      <div class="space-y-3">
        <div v-for="cls in classPerformance" :key="cls.id"
             class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-center mb-3">
            <div>
              <h3 class="font-semibold text-text-primary">{{ cls.name }}</h3>
              <p class="text-sm text-text-secondary">{{ cls.course_code }}</p>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold" :class="rateClass(cls.avg_attendance_rate)">
                {{ cls.avg_attendance_rate || 0 }}%
              </p>
              <p class="text-xs text-text-secondary">{{ cls.total_sessions }} sessions</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-text-secondary">Students:</span>
              <span class="ml-2 font-medium text-text-primary">{{ cls.total_students }}</span></div>
            <div><span class="text-text-secondary">Sessions:</span>
              <span class="ml-2 font-medium text-text-primary">{{ cls.total_sessions }}</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Session Attendance</h2>
      <div v-if="attendanceReport.length === 0" class="text-text-secondary text-sm py-4 text-center">
        No attendance records. Click Generate to load data.
      </div>
      <div class="overflow-x-auto">
        <table v-if="attendanceReport.length > 0" class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Class</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Session</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Date</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Present</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Absent</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Rate</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in attendanceReport" :key="r.id" class="border-b border-gray-100 hover:bg-gray-50">
              <td class="py-3 px-4 text-sm text-text-primary font-medium">{{ r.class_name }}</td>
              <td class="py-3 px-4 text-sm text-text-secondary">{{ r.name }}</td>
              <td class="py-3 px-4 text-sm text-text-secondary">{{ formatDate(r.date) }}</td>
              <td class="py-3 px-4 text-sm text-success font-medium">{{ r.present_count }}</td>
              <td class="py-3 px-4 text-sm text-danger font-medium">{{ r.absent_count }}</td>
              <td class="py-3 px-4 text-sm font-medium" :class="rateClass(r.attendance_rate)">{{ r.attendance_rate }}%</td>
              <td class="py-3 px-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="{ completed: 'bg-success/20 text-success', active: 'bg-primary/20 text-primary',
                             upcoming: 'bg-warning/20 text-warning' }[r.status] || 'bg-gray-200 text-gray-600'">
                  {{ r.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAttendanceStore } from '../stores/attendance.js'
import { reportsAPI } from '../services/api.js'

const store = useAttendanceStore()
const loading = ref(false)
const summary = ref({})
const classPerformance = ref([])
const attendanceReport = ref([])

const filters = reactive({ class_id: '', date_from: '', date_to: '' })

onMounted(async () => {
  await store.loadClasses()
  await Promise.all([loadSummary(), loadClassPerformance(), loadReport()])
})

async function loadSummary() {
  try { summary.value = await reportsAPI.getSummary() } catch {}
}

async function loadClassPerformance() {
  try { classPerformance.value = await reportsAPI.getClassPerformance() } catch {}
}

async function loadReport() {
  loading.value = true
  try {
    const params = {}
    if (filters.class_id) params.class_id = filters.class_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    attendanceReport.value = await reportsAPI.getAttendance(params)
    await loadSummary()
    await loadClassPerformance()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function exportCSV() {
  const params = {}
  if (filters.class_id) params.class_id = filters.class_id
  if (filters.date_from) params.date_from = filters.date_from
  if (filters.date_to) params.date_to = filters.date_to
  reportsAPI.getAttendance({ ...params, format: 'csv' })
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function rateClass(rate) {
  if (rate >= 80) return 'text-success'
  if (rate >= 60) return 'text-warning'
  return 'text-danger'
}
</script>
