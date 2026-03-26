<template>
  <div class="fade-in">
    <!-- Header -->
    <div class="mb-8">
      <p class="font-sans text-primary text-xs font-semibold tracking-wider mb-2">ATTENDANCE REPORTS</p>
      <h1 class="text-3xl font-bold text-text-primary leading-tight">Class <span class="text-primary">Reports</span></h1>
      <p class="text-text-secondary mt-2 max-w-lg text-sm">
        View attendance summaries and export reports for your classes.
      </p>
    </div>

    <!-- Report Filters -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Generate Report</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Select Class</label>
          <select v-model="filters.classId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="all">All Classes</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Date Range</label>
          <select v-model="filters.dateRange" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Report Type</label>
          <select v-model="filters.reportType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="summary">Summary</option>
            <option value="detailed">Detailed</option>
          </select>
        </div>
        <div class="flex items-end">
          <button
            @click="generateReport"
            class="w-full px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors"
          >
            Generate
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Overall Rate</p>
        <p class="text-2xl font-bold text-primary">87.5%</p>
        <p class="text-xs text-success mt-1">↑ 2.3% from last week</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Total Sessions</p>
        <p class="text-2xl font-bold text-text-primary">24</p>
        <p class="text-xs text-text-secondary mt-1">This month</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Active Students</p>
        <p class="text-2xl font-bold text-text-primary">48</p>
        <p class="text-xs text-text-secondary mt-1">Registered</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 card-shadow">
        <p class="text-sm text-text-secondary mb-1">Absent Today</p>
        <p class="text-2xl font-bold text-danger">6</p>
        <p class="text-xs text-success mt-1">↓ 3 from yesterday</p>
      </div>
    </div>

    <!-- Class Performance -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow mb-8">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">Class Performance</h2>
        <button
          @click="exportCSV"
          class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
        >
          Export CSV
        </button>
      </div>
      
      <div class="space-y-3">
        <div v-for="cls in classPerformance" :key="cls.id" class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-center mb-3">
            <div>
              <h3 class="font-semibold text-text-primary">{{ cls.name }}</h3>
              <p class="text-sm text-text-secondary">{{ cls.courseCode }}</p>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold" :class="cls.rate >= 80 ? 'text-success' : cls.rate >= 60 ? 'text-warning' : 'text-danger'">
                {{ cls.rate }}%
              </p>
              <p class="text-xs text-text-secondary">{{ cls.present }}/{{ cls.total }} present</p>
            </div>
          </div>
          
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-text-secondary">Sessions:</span>
              <span class="ml-2 font-medium text-text-primary">{{ cls.sessions }}</span>
            </div>
            <div>
              <span class="text-text-secondary">Avg Rate:</span>
              <span class="ml-2 font-medium text-primary">{{ cls.avgRate }}%</span>
            </div>
            <div>
              <span class="text-text-secondary">Trend:</span>
              <span class="ml-2 font-medium text-success">{{ cls.trend }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Attendance Table -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Recent Attendance</h2>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Class</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Date</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Present</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Absent</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Rate</th>
              <th class="text-left py-3 px-4 text-sm font-medium text-text-primary">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in recentAttendance" :key="record.id" class="border-b border-gray-100 hover:bg-gray-50">
              <td class="py-3 px-4 text-sm text-text-primary font-medium">{{ record.className }}</td>
              <td class="py-3 px-4 text-sm text-text-secondary">{{ record.date }}</td>
              <td class="py-3 px-4 text-sm text-success font-medium">{{ record.present }}</td>
              <td class="py-3 px-4 text-sm text-danger font-medium">{{ record.absent }}</td>
              <td class="py-3 px-4 text-sm font-medium" :class="getRateClass(record.rate)">
                {{ record.rate }}%
              </td>
              <td class="py-3 px-4">
                <span 
                  class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="getStatusClass(record.status)"
                >
                  {{ record.status }}
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
import { ref, reactive } from 'vue'

const filters = reactive({
  classId: 'all',
  dateRange: 'week',
  reportType: 'summary'
})

const classes = ref([
  { id: 1, name: 'Computer Science 101', courseCode: 'CS101' },
  { id: 2, name: 'Data Structures', courseCode: 'CS201' },
  { id: 3, name: 'Algorithms', courseCode: 'CS301' },
  { id: 4, name: 'Web Development', courseCode: 'CS102' }
])

const classPerformance = ref([
  { id: 1, name: 'Computer Science 101', courseCode: 'CS101', rate: 92, present: 23, total: 25, sessions: 8, avgRate: 89, trend: '↑ 3%' },
  { id: 2, name: 'Data Structures', courseCode: 'CS201', rate: 78, present: 21, total: 27, sessions: 6, avgRate: 82, trend: '↓ 2%' },
  { id: 3, name: 'Algorithms', courseCode: 'CS301', rate: 85, present: 17, total: 20, sessions: 7, avgRate: 86, trend: '→ 0%' },
  { id: 4, name: 'Web Development', courseCode: 'CS102', rate: 95, present: 26, total: 28, sessions: 9, avgRate: 93, trend: '↑ 5%' }
])

const recentAttendance = ref([
  { id: 1, className: 'Computer Science 101', date: 'Mar 26, 2024', present: 23, absent: 2, rate: 92, status: 'completed' },
  { id: 2, className: 'Data Structures', date: 'Mar 26, 2024', present: 21, absent: 6, rate: 78, status: 'completed' },
  { id: 3, className: 'Algorithms', date: 'Mar 25, 2024', present: 17, absent: 3, rate: 85, status: 'completed' },
  { id: 4, className: 'Web Development', date: 'Mar 25, 2024', present: 26, absent: 2, rate: 95, status: 'completed' },
  { id: 5, className: 'Computer Science 101', date: 'Mar 24, 2024', present: 22, absent: 3, rate: 88, status: 'completed' }
])

function generateReport() {
  console.log('Generating report with filters:', filters)
}

function exportCSV() {
  console.log('Exporting CSV report')
}

function getRateClass(rate) {
  if (rate >= 80) return 'text-success'
  if (rate >= 60) return 'text-warning'
  return 'text-danger'
}

function getStatusClass(status) {
  switch (status) {
    case 'completed':
      return 'bg-success/20 text-success'
    case 'active':
      return 'bg-primary/20 text-primary'
    case 'cancelled':
      return 'bg-danger/20 text-danger'
    default:
      return 'bg-gray-200 text-gray-600'
  }
}
</script>
