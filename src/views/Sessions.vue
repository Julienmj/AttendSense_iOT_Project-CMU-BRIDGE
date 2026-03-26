<template>
  <div class="fade-in">
    <!-- Header -->
    <div class="mb-8">
      <p class="font-sans text-primary text-xs font-semibold tracking-wider mb-2">SESSION MANAGEMENT</p>
      <h1 class="text-3xl font-bold text-text-primary leading-tight">Attendance <span class="text-primary">Sessions</span></h1>
      <p class="text-text-secondary mt-2 max-w-lg text-sm">
        Create sessions and scan attendance for registered classes.
      </p>
    </div>

    <!-- Create Session Form -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Create New Session</h2>
      <form @submit.prevent="createSession" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Select Class</label>
          <select
            v-model="newSession.classId"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">Choose a class</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.name }} ({{ cls.courseCode }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Date</label>
          <input
            v-model="newSession.date"
            type="date"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Start Time</label>
          <input
            v-model="newSession.startTime"
            type="time"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Check-in Window (minutes)</label>
          <input
            v-model.number="newSession.checkinWindow"
            type="number"
            min="5"
            max="60"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="15"
          />
        </div>
        <div class="md:col-span-2">
          <button
            type="submit"
            class="px-6 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors"
          >
            Create Session
          </button>
        </div>
      </form>
    </div>

    <!-- Attendance Scanner -->
    <div v-if="activeSession" class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">
          Attendance Scanner - {{ getClassName(activeSession.classId) }}
        </h2>
        <div class="flex items-center gap-3">
          <div class="w-2 h-2 rounded-full bg-success pulse-dot"></div>
          <span class="text-sm font-medium text-success">SCANNING ACTIVE</span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-text-secondary mb-1">Session Time</p>
          <p class="text-lg font-semibold text-text-primary">{{ activeSession.startTime }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-text-secondary mb-1">Time Remaining</p>
          <p class="text-lg font-semibold text-primary">{{ timeRemaining }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-text-secondary mb-1">Students Detected</p>
          <p class="text-lg font-semibold text-success">{{ detectedStudents.length }} / {{ totalClassStudents }}</p>
        </div>
      </div>

      <!-- Detected Students -->
      <div class="mb-4">
        <h3 class="font-medium text-text-primary mb-3">Detected Students</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <div v-for="student in detectedStudents" :key="student.id" class="flex justify-between items-center bg-success/10 border border-success/20 rounded-lg p-3">
            <div>
              <p class="font-medium text-text-primary">{{ student.name }}</p>
              <p class="text-sm text-text-secondary">{{ student.studentId }}</p>
            </div>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/20 text-success">
              Present
            </span>
          </div>
        </div>
      </div>

      <button
        @click="endSession(activeSession.id)"
        class="px-4 py-2 bg-danger text-white rounded-lg font-medium hover:bg-red-600 transition-colors"
      >
        End Session
      </button>
    </div>

    <!-- Sessions List -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">Session History</h2>
        <div class="flex gap-2">
          <select v-model="filterStatus" class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="all">All Sessions</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
          </select>
        </div>
      </div>
      
      <div class="space-y-3">
        <div v-for="session in filteredSessions" :key="session.id" class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-3">
            <div>
              <h3 class="font-semibold text-text-primary">{{ getClassName(session.classId) }}</h3>
              <p class="text-sm text-text-secondary">
                {{ formatDate(session.date) }} at {{ session.startTime }}
              </p>
            </div>
            <span 
              class="px-3 py-1 text-xs font-medium rounded-full"
              :class="getSessionStatusClass(session.status)"
            >
              {{ session.status }}
            </span>
          </div>
          
          <div class="grid grid-cols-3 gap-4 text-sm mb-3">
            <div>
              <span class="text-text-secondary">Present:</span>
              <span class="ml-2 font-medium text-success">{{ session.presentCount }}</span>
            </div>
            <div>
              <span class="text-text-secondary">Absent:</span>
              <span class="ml-2 font-medium text-danger">{{ session.totalStudents - session.presentCount }}</span>
            </div>
            <div>
              <span class="text-text-secondary">Rate:</span>
              <span class="ml-2 font-medium text-primary">{{ Math.round((session.presentCount / session.totalStudents) * 100) }}%</span>
            </div>
          </div>
          
          <div class="flex gap-2">
            <button
              v-if="session.status === 'upcoming'"
              @click="startSession(session.id)"
              class="px-4 py-1 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
            >
              Start Scan
            </button>
            <button
              @click="viewAttendance(session.id)"
              class="px-4 py-1 text-sm border border-gray-300 text-text-primary rounded-lg hover:bg-gray-50 transition-colors"
            >
              View Details
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'

const newSession = reactive({
  classId: '',
  date: '',
  startTime: '',
  checkinWindow: 15
})

const filterStatus = ref('all')
const activeSession = ref(null)
const detectedStudents = ref([])
const timeRemaining = ref('15:00')
let scanInterval = null

const classes = ref([
  { id: 1, name: 'Computer Science 101', courseCode: 'CS101', students: [
    { id: 1, name: 'Alice Johnson', studentId: 'CS001', macAddress: 'A4:C1:38:2B:5E:9F' },
    { id: 2, name: 'Bob Smith', studentId: 'CS002', macAddress: 'B5:D2:49:3C:6F:0A' }
  ]},
  { id: 2, name: 'Data Structures', courseCode: 'CS201', students: [
    { id: 3, name: 'Charlie Brown', studentId: 'CS003', macAddress: 'C6:E3:5A:4D:7G:1B' }
  ]},
  { id: 3, name: 'Algorithms', courseCode: 'CS301', students: [] }
])

const sessions = ref([
  {
    id: 1,
    classId: 1,
    date: '2024-03-26',
    startTime: '09:00',
    checkinWindow: 15,
    status: 'active',
    presentCount: 2,
    totalStudents: 2
  },
  {
    id: 2,
    classId: 2,
    date: '2024-03-26',
    startTime: '14:00',
    checkinWindow: 10,
    status: 'upcoming',
    presentCount: 0,
    totalStudents: 1
  }
])

const filteredSessions = computed(() => {
  if (filterStatus.value === 'all') return sessions.value
  return sessions.value.filter(session => session.status === filterStatus.value)
})

const totalClassStudents = computed(() => {
  if (!activeSession.value) return 0
  const cls = classes.value.find(c => c.id === activeSession.value.classId)
  return cls?.students?.length || 0
})

function getClassName(classId) {
  const cls = classes.value.find(c => c.id === classId)
  return cls ? cls.name : 'Unknown Class'
}

function createSession() {
  const cls = classes.value.find(c => c.id === newSession.classId)
  const session = {
    id: Date.now(),
    ...newSession,
    status: 'upcoming',
    presentCount: 0,
    totalStudents: cls?.students?.length || 0
  }
  sessions.value.push(session)
  
  newSession.classId = ''
  newSession.date = ''
  newSession.startTime = ''
  newSession.checkinWindow = 15
}

function startSession(sessionId) {
  const session = sessions.value.find(s => s.id === sessionId)
  if (session) {
    session.status = 'active'
    activeSession.value = session
    detectedStudents.value = []
    startScanning()
  }
}

function endSession(sessionId) {
  const session = sessions.value.find(s => s.id === sessionId)
  if (session) {
    session.status = 'completed'
    session.presentCount = detectedStudents.value.length
    stopScanning()
    activeSession.value = null
  }
}

function startScanning() {
  // Simulate Bluetooth scanning
  let minutes = 15
  scanInterval = setInterval(() => {
    if (minutes <= 0) {
      stopScanning()
      return
    }
    
    timeRemaining.value = `${minutes}:00`
    minutes--
    
    // Simulate detecting a student
    if (activeSession.value && Math.random() > 0.7) {
      const cls = classes.value.find(c => c.id === activeSession.value.classId)
      const availableStudents = cls?.students?.filter(s => 
        !detectedStudents.value.some(d => d.id === s.id)
      ) || []
      
      if (availableStudents.length > 0) {
        const randomStudent = availableStudents[Math.floor(Math.random() * availableStudents.length)]
        detectedStudents.value.push(randomStudent)
      }
    }
  }, 3000)
}

function stopScanning() {
  if (scanInterval) {
    clearInterval(scanInterval)
    scanInterval = null
  }
}

function viewAttendance(sessionId) {
  console.log('View attendance for session:', sessionId)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function getSessionStatusClass(status) {
  switch (status) {
    case 'active':
      return 'bg-primary/20 text-primary'
    case 'upcoming':
      return 'bg-warning/20 text-warning'
    case 'completed':
      return 'bg-success/20 text-success'
    default:
      return 'bg-gray-200 text-gray-600'
  }
}

onMounted(() => {
  // Set today's date as default
  const today = new Date().toISOString().split('T')[0]
  newSession.date = today
})

onUnmounted(() => {
  stopScanning()
})
</script>
