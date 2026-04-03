<template>
  <div class="fade-in">
    <div class="mb-8">
      <p class="font-sans text-primary text-xs font-semibold tracking-wider mb-2">CLASS MANAGEMENT</p>
      <h1 class="text-3xl font-bold text-text-primary leading-tight">Classes & <span class="text-primary">Students</span></h1>
      <p class="text-text-secondary mt-2 max-w-lg text-sm">Create classes and register students using their NRF Connect device names for Bluetooth attendance tracking.</p>
      
      <!-- NRF Connect Instructions -->
      <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h3 class="font-medium text-blue-900 mb-2">📱 How to setup NRF Connect:</h3>
        <ol class="text-sm text-blue-800 space-y-1">
          <li><strong>1.</strong> Download "nRF Connect for Mobile" app</li>
          <li><strong>2.</strong> Open app → Go to "Advertiser" tab</li>
          <li><strong>3.</strong> Create new advertiser with unique name (e.g., "StudentAlice")</li>
          <li><strong>4.</strong> Start advertising to broadcast your device name</li>
          <li><strong>5.</strong> Register this exact name below</li>
        </ol>
      </div>
    </div>

    <!-- Error/Success Banner -->
    <div v-if="message" :class="messageType === 'error' ? 'bg-red-50 border-red-200 text-danger' : 'bg-green-50 border-green-200 text-success'"
         class="border rounded-lg px-4 py-3 mb-4 text-sm flex justify-between items-center">
      {{ message }}
      <button @click="message = ''" class="ml-4 font-bold">×</button>
    </div>

    <!-- Create Class Form -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Create New Class</h2>
      <form @submit.prevent="createClass" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Class Name</label>
          <input v-model="newClass.name" type="text" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="e.g., Computer Science 101" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Course Code</label>
          <input v-model="newClass.course_code" type="text" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="e.g., CS101" />
        </div>
        <div class="flex items-end">
          <button type="submit" :disabled="loading"
            class="w-full px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors disabled:opacity-50">
            {{ loading ? 'Creating...' : 'Create Class' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Classes List -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Registered Classes</h2>
      <div v-if="store.classes.length === 0" class="text-text-secondary text-sm py-4 text-center">No classes yet. Create one above.</div>
      <div class="space-y-3">
        <div v-for="cls in store.classes" :key="cls.id" class="border border-gray-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <div>
              <h3 class="font-semibold text-text-primary">{{ cls.name }}</h3>
              <p class="text-sm text-text-secondary">{{ cls.course_code }}</p>
            </div>
            <div class="flex gap-2">
              <button @click="selectClass(cls)"
                class="px-3 py-1 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                Register Students
              </button>
              <button @click="deleteClass(cls.id)"
                class="px-3 py-1 text-sm border border-gray-300 text-text-primary rounded-lg hover:bg-gray-50 transition-colors">
                Delete
              </button>
            </div>
          </div>
          <div class="flex justify-between items-center text-sm">
            <span class="text-text-secondary">{{ cls.student_count || 0 }} students registered</span>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/20 text-success">Active</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Student Registration Panel -->
    <div v-if="selectedClass" class="bg-white border border-gray-200 rounded-xl p-6 card-shadow">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">Register Students — {{ selectedClass.name }}</h2>
        <button @click="selectedClass = null" class="text-text-secondary hover:text-text-primary text-sm">Close ×</button>
      </div>

      <form @submit.prevent="addStudent" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Student Name</label>
          <input v-model="newStudent.name" type="text" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="Full name" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Student ID</label>
          <input v-model="newStudent.student_id" type="text" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="e.g., STU001" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Email</label>
          <input v-model="newStudent.email" type="email"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="student@email.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Phone</label>
          <input v-model="newStudent.phone" type="tel"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="+1234567890" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">NRF Connect Device Name</label>
          <input v-model="newStudent.device_name" type="text" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="e.g., StudentAlice" />
          <p class="text-xs text-text-secondary mt-1">Set this name in NRF Connect app → Advertiser</p>
        </div>
        <div class="lg:col-span-3">
          <button type="submit" :disabled="studentLoading"
            class="px-6 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors disabled:opacity-50">
            {{ studentLoading ? 'Registering...' : 'Register Student' }}
          </button>
        </div>
      </form>

      <!-- Students Table -->
      <div class="border-t pt-4">
        <h3 class="font-medium text-text-primary mb-3">Registered Students ({{ classStudents.length }})</h3>
        <div v-if="classStudents.length === 0" class="text-text-secondary text-sm py-2">No students yet.</div>
        <div class="overflow-x-auto">
          <table v-if="classStudents.length > 0" class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">Name</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">ID</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">Email</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">NRF Device Name</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in classStudents" :key="student.id" class="border-b border-gray-100">
                <td class="py-2 px-3 text-sm text-text-primary">{{ student.name }}</td>
                <td class="py-2 px-3 text-sm text-text-secondary">{{ student.student_id }}</td>
                <td class="py-2 px-3 text-sm text-text-secondary">{{ student.email }}</td>
                <td class="py-2 px-3 text-sm text-text-secondary">{{ student.device_name }}</td>
                <td class="py-2 px-3">
                  <button @click="removeStudent(student.id)"
                    class="text-danger hover:text-red-600 text-sm font-medium">Remove</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAttendanceStore } from '../stores/attendance.js'

const store = useAttendanceStore()
const loading = ref(false)
const studentLoading = ref(false)
const message = ref('')
const messageType = ref('success')
const selectedClass = ref(null)

const newClass = reactive({ name: '', course_code: '' })
const newStudent = reactive({ name: '', student_id: '', email: '', phone: '', device_name: '' })

const classStudents = computed(() => {
  if (!selectedClass.value) return []
  const cls = store.classes.find(c => c.id === selectedClass.value.id)
  return cls?.students || []
})

onMounted(() => store.loadClasses())

function showMsg(text, type = 'success') {
  message.value = text
  messageType.value = type
  setTimeout(() => message.value = '', 4000)
}

async function createClass() {
  loading.value = true
  try {
    await store.addClass({ name: newClass.name, course_code: newClass.course_code })
    newClass.name = ''; newClass.course_code = ''
    showMsg('Class created successfully')
  } catch (e) {
    showMsg(e.message, 'error')
  } finally {
    loading.value = false
  }
}

async function selectClass(cls) {
  selectedClass.value = cls
  await store.loadClassStudents(cls.id)
}

async function deleteClass(id) {
  if (!confirm('Delete this class and all its students?')) return
  try {
    await store.deleteClass(id)
    if (selectedClass.value?.id === id) selectedClass.value = null
    showMsg('Class deleted')
  } catch (e) {
    showMsg(e.message, 'error')
  }
}

async function addStudent() {
  if (!selectedClass.value) return
  studentLoading.value = true
  try {
    await store.addStudentToClass(selectedClass.value.id, { ...newStudent })
    Object.assign(newStudent, { name: '', student_id: '', email: '', phone: '', device_name: '' })
    showMsg('Student registered')
  } catch (e) {
    showMsg(e.message, 'error')
  } finally {
    studentLoading.value = false
  }
}

async function removeStudent(studentId) {
  if (!confirm('Remove this student?')) return
  try {
    await store.removeStudentFromClass(selectedClass.value.id, studentId)
    showMsg('Student removed')
  } catch (e) {
    showMsg(e.message, 'error')
  }
}
</script>
