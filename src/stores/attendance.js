import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { classesAPI, sessionsAPI } from '../services/api.js'

export const useAttendanceStore = defineStore('attendance', () => {
  const classes = ref([])
  const sessions = ref([])
  const currentSession = ref(null)
  const isDetecting = ref(false)

  // Classes
  async function loadClasses() {
    try { classes.value = await classesAPI.getAll() }
    catch (e) { console.error('loadClasses:', e) }
  }

  async function addClass(data) {
    const created = await classesAPI.create(data)
    // Reload to get accurate student_count
    await loadClasses()
    return created
  }

  async function updateClass(id, data) {
    await classesAPI.update(id, data)
    await loadClasses()
  }

  async function deleteClass(id) {
    await classesAPI.delete(id)
    classes.value = classes.value.filter(c => c.id !== id)
  }

  // Students
  async function loadClassStudents(classId) {
    const students = await classesAPI.getStudents(classId)
    const cls = classes.value.find(c => c.id == classId)
    if (cls) cls.students = students
    return students
  }

  async function addStudentToClass(classId, data) {
    const student = await classesAPI.addStudent(classId, data)
    const cls = classes.value.find(c => c.id == classId)
    if (cls) {
      if (!cls.students) cls.students = []
      cls.students.push(student)
      cls.student_count = (cls.student_count || 0) + 1
    }
    return student
  }

  async function removeStudentFromClass(classId, studentId) {
    await classesAPI.removeStudent(classId, studentId)
    const cls = classes.value.find(c => c.id == classId)
    if (cls?.students) {
      cls.students = cls.students.filter(s => s.id !== studentId)
      cls.student_count = Math.max(0, (cls.student_count || 1) - 1)
    }
  }

  // Sessions
  async function loadSessions() {
    try { sessions.value = await sessionsAPI.getAll() }
    catch (e) { console.error('loadSessions:', e) }
  }

  async function createSession(data) {
    const session = await sessionsAPI.create(data)
    await loadSessions()
    return session
  }

  async function startSession(id) {
    await sessionsAPI.start(id)
    await loadSessions()
    currentSession.value = sessions.value.find(s => s.id == id) || null
    isDetecting.value = true
  }

  async function endSession(id) {
    await sessionsAPI.end(id)
    await loadSessions()
    if (currentSession.value?.id == id) {
      currentSession.value = null
      isDetecting.value = false
    }
  }

  // Computed
  const activeSessions = computed(() => sessions.value.filter(s => s.status === 'active'))

  const todaySessions = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return sessions.value.filter(s => s.date === today)
  })

  const totalStudents = computed(() =>
    classes.value.reduce((sum, c) => sum + (parseInt(c.student_count) || 0), 0)
  )

  return {
    classes, sessions, currentSession, isDetecting,
    loadClasses, addClass, updateClass, deleteClass,
    loadClassStudents, addStudentToClass, removeStudentFromClass,
    loadSessions, createSession, startSession, endSession,
    activeSessions, todaySessions, totalStudents,
  }
})
