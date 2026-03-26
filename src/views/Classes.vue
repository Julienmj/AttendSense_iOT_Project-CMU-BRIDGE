<template>
  <div class="fade-in">
    <!-- Header -->
    <div class="mb-8">
      <p class="font-sans text-primary text-xs font-semibold tracking-wider mb-2">CLASS MANAGEMENT</p>
      <h1 class="text-3xl font-bold text-text-primary leading-tight">Classes & <span class="text-primary">Students</span></h1>
      <p class="text-text-secondary mt-2 max-w-lg text-sm">
        Create classes and register students for Bluetooth-based attendance tracking.
      </p>
    </div>

    <!-- Create Class Form -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Create New Class</h2>
      <form @submit.prevent="createClass" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Class Name</label>
          <input
            v-model="newClass.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="e.g., Computer Science 101"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Course Code</label>
          <input
            v-model="newClass.courseCode"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="e.g., CS101"
          />
        </div>
        <div class="flex items-end">
          <button
            type="submit"
            class="w-full px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors"
          >
            Create Class
          </button>
        </div>
      </form>
    </div>

    <!-- Classes List -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Registered Classes</h2>
      <div class="space-y-3">
        <div v-for="classItem in classes" :key="classItem.id" class="border border-gray-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <div>
              <h3 class="font-semibold text-text-primary">{{ classItem.name }}</h3>
              <p class="text-sm text-text-secondary">{{ classItem.courseCode }}</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="selectClass(classItem)"
                class="px-3 py-1 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
              >
                Register Students
              </button>
              <button
                @click="deleteClass(classItem.id)"
                class="px-3 py-1 text-sm border border-gray-300 text-text-primary rounded-lg hover:bg-gray-50 transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
          <div class="flex justify-between items-center text-sm">
            <span class="text-text-secondary">{{ classItem.students?.length || 0 }} students registered</span>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/20 text-success">
              Active
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Student Registration (shown when class is selected) -->
    <div v-if="selectedClass" class="bg-white border border-gray-200 rounded-xl p-6 card-shadow">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">
          Register Students - {{ selectedClass.name }}
        </h2>
        <button
          @click="selectedClass = null"
          class="text-text-secondary hover:text-text-primary text-sm"
        >
          Close ×
        </button>
      </div>

      <!-- Register Student Form -->
      <form @submit.prevent="addStudent" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Student Name</label>
          <input
            v-model="newStudent.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="Enter student name"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Student ID</label>
          <input
            v-model="newStudent.studentId"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="Enter student ID"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Email</label>
          <input
            v-model="newStudent.email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="student@email.com"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Phone</label>
          <input
            v-model="newStudent.phone"
            type="tel"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="+1234567890"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Device Name</label>
          <input
            v-model="newStudent.deviceName"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="iPhone 13"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">MAC Address</label>
          <input
            v-model="newStudent.macAddress"
            type="text"
            required
            pattern="^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="XX:XX:XX:XX:XX:XX"
          />
        </div>
        <div class="lg:col-span-3">
          <button
            type="submit"
            class="px-6 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors"
          >
            Register Student
          </button>
        </div>
      </form>

      <!-- Class Students List -->
      <div class="border-t pt-4">
        <h3 class="font-medium text-text-primary mb-3">Registered Students ({{ selectedClass.students?.length || 0 }})</h3>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">Name</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">ID</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">Email</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">Device</th>
                <th class="text-left py-2 px-3 text-sm font-medium text-text-primary">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in selectedClass.students" :key="student.id" class="border-b border-gray-100">
                <td class="py-2 px-3 text-sm text-text-primary">{{ student.name }}</td>
                <td class="py-2 px-3 text-sm text-text-secondary">{{ student.studentId }}</td>
                <td class="py-2 px-3 text-sm text-text-secondary">{{ student.email }}</td>
                <td class="py-2 px-3 text-sm text-text-secondary">{{ student.deviceName }}</td>
                <td class="py-2 px-3">
                  <button
                    @click="removeStudent(student.id)"
                    class="text-danger hover:text-red-600 text-sm font-medium"
                  >
                    Remove
                  </button>
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
import { ref, reactive } from 'vue'

const newClass = reactive({
  name: '',
  courseCode: ''
})

const newStudent = reactive({
  name: '',
  studentId: '',
  email: '',
  phone: '',
  deviceName: '',
  macAddress: ''
})

const classes = ref([
  {
    id: 1,
    name: 'Computer Science 101',
    courseCode: 'CS101',
    students: [
      { id: 1, name: 'Alice Johnson', studentId: 'CS001', email: 'alice@university.edu', phone: '+1234567890', deviceName: 'iPhone 13', macAddress: 'A4:C1:38:2B:5E:9F' },
      { id: 2, name: 'Bob Smith', studentId: 'CS002', email: 'bob@university.edu', phone: '+1234567891', deviceName: 'Samsung Galaxy', macAddress: 'B5:D2:49:3C:6F:0A' }
    ]
  },
  {
    id: 2,
    name: 'Data Structures',
    courseCode: 'CS201',
    students: [
      { id: 3, name: 'Charlie Brown', studentId: 'CS003', email: 'charlie@university.edu', phone: '+1234567892', deviceName: 'Pixel 7', macAddress: 'C6:E3:5A:4D:7G:1B' }
    ]
  },
  {
    id: 3,
    name: 'Algorithms',
    courseCode: 'CS301',
    students: []
  }
])

const selectedClass = ref(null)

function createClass() {
  const classItem = {
    id: Date.now(),
    ...newClass,
    students: []
  }
  classes.value.push(classItem)
  
  newClass.name = ''
  newClass.courseCode = ''
}

function selectClass(classItem) {
  selectedClass.value = classItem
}

function deleteClass(id) {
  classes.value = classes.value.filter(c => c.id !== id)
  if (selectedClass.value?.id === id) {
    selectedClass.value = null
  }
}

function addStudent() {
  if (!selectedClass.value) return
  
  const student = {
    id: Date.now(),
    ...newStudent
  }
  
  selectedClass.value.students.push(student)
  
  // Reset form
  newStudent.name = ''
  newStudent.studentId = ''
  newStudent.email = ''
  newStudent.phone = ''
  newStudent.deviceName = ''
  newStudent.macAddress = ''
}

function removeStudent(studentId) {
  if (!selectedClass.value) return
  selectedClass.value.students = selectedClass.value.students.filter(s => s.id !== studentId)
}
</script>
